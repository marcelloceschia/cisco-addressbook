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
	
	private $client;


	
	function __construct() {
		Zend_Loader::loadClass('Zend_Gdata');
		Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
		Zend_Loader::loadClass('Zend_Http_Client');
		Zend_Loader::loadClass('Zend_Gdata_Query');
		Zend_Loader::loadClass('Zend_Gdata_Feed');
	}

	function __destruct() {
		
	}
	
	
	function setUsername($username){
		$this->username = $username;
	}
	
	function setPassword($password){
		$this->password = $password;
	}


	function getEntries($offset, $limit = null){

		$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'cp');
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
		  $item = new PhonebookEntry($this->generateID($xml) , (string)$xml->name->fullName);

		  $key = array();
		  if(((string)$xml->name->familyName) != null){ $key[] = (string)$xml->name->familyName; }
		  if(((string)$xml->name->givenName) != null){ $key[] = (string)$xml->name->givenName; }

		  if(count($key) > 0){
			$keyStr = implode(",", $key);
			$keyStr = $i++;
			$list[$keyStr] = $item;
		  }

		}
		
		ksort($list);
		return $list;
	}

	function getEntry($id){
		$directory = null;
	
		$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'cp');
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
		
		
		$this->client = Zend_Gdata_ClientLogin::getHttpClient($this->username, $this->password, 'cp');
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
