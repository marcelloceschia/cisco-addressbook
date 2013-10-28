<?php
/**
 * google backend for cisco xml addressbook
 *
 * @see https://github.com/mhamzahkhan/google-contacts2ciscoxml
 * @author marcello ceschia
 */

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__)."/includes/class");
require_once("Zend/Loader.php");


class Google implements Backend{
	private $username = "";
	private $password;
	private $token = null;
	
	private $client;


	
	function __construct() {
		Zend_Loader::loadClass('Zend_Gdata');
		Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
		Zend_Loader::loadClass('Zend_Gdata_AuthSub');
		Zend_Loader::loadClass('Zend_Http_Client');
		Zend_Loader::loadClass('Zend_Gdata_Query');
		Zend_Loader::loadClass('Zend_Gdata_Feed');
	}

	function __destruct() {
		
	}
	
	function entrySort($a, $b) {
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
	
	private static function keySort($a, $b) {
	    if (strtolower($a) == strtolower($b)) {
		return 0;
	    }
	    return (strtolower($a) < strtolower($b)) ? -1 : 1;
	}
		
	
	function setUsername($username){
		$this->username = $username;
	}
	
	function setPassword($password){
		$this->password = $password;
	}

	function setToken($token){
		$this->token = $token;
	}


	function getEntries($offset, $limit = null){
		try {
			if ($this->token == null){
				$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'cp');
			}else {
				$this->client = Zend_Gdata_AuthSub::getHttpClient($this->token);
			}
			
// 			print_r($this->client);
			//$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'xapi', null, "cisco-addressbook", null, null, "https://www.google.com/accounts/ClientLogin", 'GOOGLE');
		} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
  
		} catch (Zend_Gdata_App_AuthException $ae) {
			throw new AuthenticationException($ae->exception());
		}
		
		$gdata = new Zend_Gdata($this->client);
		$gdata->setMajorProtocolVersion(3);
		
		$query = new Zend_Gdata_Query('http://www.google.com/m8/feeds/contacts/default/full');
		$query->setMaxResults("2147483647");
		$query->setParam('orderby', 'lastmodified');
		$query->setParam('sortorder', 'descending');
		$feed = $gdata->getFeed($query);
		$list = array();
		
		$i=0;
		foreach($feed as $entry){
		  
		  $xml = simplexml_load_string($entry->getXML());

		  $key = array();
		  if(((string)$xml->name->familyName) != null){ $key[] = (string)$xml->name->familyName; }
		  if(((string)$xml->name->givenName) != null){ $key[] = (string)$xml->name->givenName; }

		  if(count($key) > 0){
			$keyStr = implode(",", $key);
			$item = new PhonebookEntry($this->generateID($xml) , $keyStr);
			$list[$keyStr] = $item;
		  }

		}

		uksort($list, "Google::keySort");
		$i=0;
		$sortedResultList = array();
		foreach($list as $value){
			$sortedResultList[$i++] = $value;
		}
		return $sortedResultList;
	}

	function getEntry($id){
		$directory = null;
	
		try {
			$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'cp');
		} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
  
		} catch (Zend_Gdata_App_AuthException $ae) {
			throw new AuthenticationException($ae->exception());
		}
		$gdata = new Zend_Gdata($this->client);
		$gdata->setMajorProtocolVersion(3);
		
		$query = new Zend_Gdata_Query('http://www.google.com/m8/feeds/contacts/default/full');
		$query->setMaxResults("2147483647");
		$query->setParam('orderby', 'lastmodified');
		$query->setParam('sortorder', 'descending');
		$feed = $gdata->getFeed($query);
	
		$list = array();
		foreach($feed as $entry){
			$xml = simplexml_load_string($entry->getXML());
			if( $this->generateID($xml) ==  $id){
				$directory = new PhonebookContact( $id , (string)$xml->name->fullName);
				$i = 1;
				foreach ($xml->phoneNumber as $p) {
					$directory->addContactEntry("Phone ".$i++, (string) $p);
				}
				return $directory;
			}
		}
		
		return $directory;
		
	}

	public function search($lastname = null, $firstname = null){
		$list = array();
		
		try {
			$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'cp');
		} catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
  
		} catch (Zend_Gdata_App_AuthException $ae) {
			throw new AuthenticationException($ae->exception());
		}
		
		$gdata = new Zend_Gdata($this->client);
		$gdata->setMajorProtocolVersion(3);
		
		$query = new Zend_Gdata_Query('http://www.google.com/m8/feeds/contacts/default/full');
		$query->setMaxResults("2147483647");
		$query->setParam('orderby', 'lastmodified');
		$query->setParam('sortorder', 'descending');
		$feed = $gdata->getFeed($query);
		
		
		foreach($feed as $entry){
		  
			$xml = simplexml_load_string($entry->getXML());
			if((isset($lastname) && StrStartsWith(strtolower((string)$xml->name->familyName), strtolower($lastname))) || (isset($firstname) && StrStartsWith(strtolower((string)$xml->name->givenName), strtolower($firstname))) ){
			
 				$item = new PhonebookEntry( $this->generateID($xml) , (string)$xml->name->fullName);
 				array_push($list, $item);
			}
		}
		
		return $list;
	}

	public function reverseLookup($number){
		
	}
	
	
	private function generateID($contact){
		return md5($contact->name->fullName);
	}
}
function StrStartsWith($str, $search){

	if(!isset($search) ||$search == "")
		return 0;
	if(!isset($str) ||$str == "")
		return 0;

	return strpos(strtoupper(utf8_decode($str)), strtoupper($search)) === 0;
}
?>
