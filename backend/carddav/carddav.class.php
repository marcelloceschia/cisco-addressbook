<?php
/**
 * exchange 2003 backend for cisco xml addressbook
 *
 * @author marcello ceschia
 */


class CardDAV implements Backend{
	private $username = "";
	private $password;
	private $useragent = "cisco-addressbook/carddav";
	private $version = "0.1";
	
	private $client;
	private $uri = null;
	
	function __construct() {}

	function __destruct() {
		
	}
	
	function setUsername($username){
		$this->username = $username;
	}
	
	function setPassword($password){
		$this->password = $password;
	}
	
	function setURI($uri){
		$this->uri = $uri;
	}


	function getEntries($offset, $limit = null){
$xmldata = <<<XMLDATA
<?xml version="1.0" encoding="utf-8" ?>
<D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:carddav">
	<D:prop>
		<D:getetag/>
	    <C:address-data>
	      <C:prop name="UID"/>
	      <C:prop name="FN"/>
	    </C:address-data>
	</D:prop>
</D:propfind>
XMLDATA;
		/* get entries with FN */
		$response = $this->query($this->uri, 'PROPFIND', $xmldata, "text/xml; charset=\"utf-8\"");

		if($response !== false){
			return $this->parseListResponse($response);
		} 

		return $response;
	}


	function getEntry($id){
$xmlsearch = <<<XMLDATA
<?xml version="1.0" encoding="utf-8" ?>
<C:addressbook-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:carddav">
  <D:prop>
    <D:getetag/>
    <C:address-data>
      <C:prop name="UID"/>
      <C:prop name="FN"/>
    </C:address-data>
  </D:prop>
  <C:filter>
    <C:prop-filter name="UID">
      <C:text-match collation="i;unicode-casemap" match-type="equals">%search%</C:text-match>
    </C:prop-filter>
  </C:filter>
</C:addressbook-query>
XMLDATA;

		$data = str_replace("%search%", $id, $xmlsearch);

		/* get entries with FN */
		$response = $this->query($this->uri, 'REPORT', $data, "text/xml; charset=\"utf-8\"", array('Depth: 1') );
		if($response !== false){
			return $this->parseEntryResponse($response);
		}

		return $list;
		
	}

	public function search($lastname = null, $firstname = null){
		$list = array();
		
$xmlsearch = <<<XMLDATA
<?xml version="1.0" encoding="utf-8" ?>
<C:addressbook-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:carddav">
  <D:prop>
    <D:getetag/>
    <C:address-data>
      <C:prop name="UID"/>
      <C:prop name="FN"/>
    </C:address-data>
  </D:prop>
  <C:filter>
    <C:prop-filter name="FN">
      <C:text-match collation="i;unicode-casemap" match-type="starts-with">%search%</C:text-match>
    </C:prop-filter>
  </C:filter>
</C:addressbook-query>
XMLDATA;

		$data = str_replace("%search%", $firstname, $xmlsearch);

		/* get entries with FN */
		$response = $this->query($this->uri, 'REPORT', $data, "text/xml; charset=\"utf-8\"");
		if($response !== false){
			return $this->parseListResponse($response);
		}
		

		return $list;
	}

	public function reverseLookup($number){
		$list = array();
		$xmlsearch = <<<XMLDATA
<?xml version="1.0" encoding="utf-8" ?>
<C:addressbook-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:carddav">
  <D:prop>
    <D:getetag/>
    <C:address-data>
      <C:prop name="UID"/>
      <C:prop name="FN"/>
      <C:prop name="TEL"/>
    </C:address-data>
  </D:prop>
  <C:filter>
    <C:filter test="TEL">
      <C:text-match collation="i;unicode-casemap" match-type="contains">%search%</C:text-match>
    </C:prop-filter>
  </C:filter>
</C:addressbook-query>
XMLDATA;

		$data = str_replace("%search%", str_replace(" ", "", $number), $xmlsearch);

		/* get entries with FN */
		$response = $this->query($this->uri, 'REPORT', $data, "text/xml; charset=\"utf-8\"", array('Depth: 1') );
		if($response !== false){
			return $this->parseListResponse($response);
		}
		

		return $list;
	}
	

	/**
	 * quries the CardDAV-Server via curl and returns the response
	 * 
	 * @param string $method HTTP-Method like (OPTIONS, GET, HEAD, POST, PUT, DELETE, TRACE, COPY, MOVE)
	 * @param string $content content for CardDAV-Queries
	 * @param string $content_type set content-type
	 * @return string CardDAV xml-response
	 */
	private function query($url, $method, $content = null, $content_type = null, $headers = null){
		if($url === null){
			throw new Exception("No uri specified for carddav backend! Please add an uri entry to the device mapping configuration.");
		}
	
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent."/".$this->version);

		if ($content !== null){
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		}

		if ($content_type !== null){
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: '.$content_type));
		}
		if ($headers !== null){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		}

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);


		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if (in_array($http_code, array(200, 207))){
			return $response;
		}else{
			return false;
		}
	}

	/**
	 * simplify CardDAV xml-response
	 *
	 * @param string $response CardDAV xml-response
	 * @return string simplified CardDAV xml-response
	 */
	private function parseListResponse($response, $include_vcards = true){
		$list = array(); /* result list */
// 		echo $response;

		$xml = new SimpleXMLElement($response);
		
// 		$result = array();
// 		$uid = array();
		
		$xml->registerXPathNamespace('c', 'urn:ietf:params:xml:ns:carddav');
		$xml->registerXPathNamespace('d', 'DAV:');

		foreach($xml->xpath('//d:response/d:propstat/d:prop/c:address-data') as $data) {
		
			$vCard = new vCard(false, (string)$data);

// 			preg_match_all("/FN(;(?<PARAM>[^=]+=[^=]+))?:(?<CONTENT>[^\\n]*)/", (string)$data, $result);
// 			preg_match_all("/UID:([^\\n]*)/s", (string)$data, $uid);
// 			
// 			$name = isset($result['CONTENT'][0]) ? (string)$result['CONTENT'][0] : "";
// 			if (strlen($name) == 0){ continue; }
			
			
			$name = implode(",", $vCard->FN);
			if (strlen($name) == 0){ continue; }
			
			@$item = new PhonebookEntry($vCard->UID[0], $name);//$this->createPhonebookEntry($contacts[$i]);
			array_push($list, $item);
		}


		return $list;
	}
	
	/**
	 * simplify CardDAV xml-response
	 *
	 * @param string $response CardDAV xml-response
	 * @return string simplified CardDAV xml-response
	 */
	private function parseEntryResponse($response, $include_vcards = true){
		$list = array(); /* result list */
// 		echo $response;

		$xml = new SimpleXMLElement($response);
		
		$result = array();
		$uid = array();
		$tels = array();
		
		$xml->registerXPathNamespace('c', 'urn:ietf:params:xml:ns:carddav');
		$xml->registerXPathNamespace('d', 'DAV:');

		foreach($xml->xpath('//d:response/d:propstat/d:prop/c:address-data') as $data) {
		
			$vCard = new vCard(false, (string)$data);
// 			print_r($vCard);
// 			preg_match_all("/FN(;(?<PARAM>[^=]+=[^=]+))?:(?<CONTENT>[^\\n]*)/s", (string)$data, $result);
// 			preg_match_all("/UID:([^\\n]*)/s", (string)$data, $uid);
// 			//preg_match_all('/^TEL;TYPE=(.[^:]*):(.*)$/msU', (string)$data, $tels);
// 			preg_match_all('/TEL(;(?<PARAM>[^=]+=[^=]+))*(;TYPE=(?<TYPE>.[^:]*))*:(?<CONTENT>[^\\n]*)$/msU', (string)$data, $tels);
	
// 			$name = isset($result['CONTENT'][0]) ? (string)$result['CONTENT'][0] : "";
// 			if (strlen($name) == 0){ continue; }

			$name = implode(",", $vCard->FN);
			if (strlen($name) == 0){ continue; }
			
			$directory = new PhonebookContact( $vCard->UID[0] , $name);
			for($i=0; $i < count($vCard->TEL); $i++){	
				$directory->addContactEntry($vCard->TEL[$i]['Type'][0], (string) $vCard->TEL[$i]['Value']);
			}
		}
		return $directory;
	}
}

?>
