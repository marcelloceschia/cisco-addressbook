<?php
/**
 * exchange 2003 backend for cisco xml addressbook
 *
 * @author marcello ceschia
 */


class Exchange2003 implements Backend{
	private $username = "";
	private $password;
	
	private $client;
	private $serverURI;
	private static $xmldata = <<<XMLDATA
				<?xml version="1.0"?>
                <g:searchrequest xmlns:g="DAV:">
                    <g:sql>
                        SELECT
                            "urn:schemas:contacts:sn", 
							"urn:schemas:contacts:givenName",
                            "urn:schemas:contacts:email1", 
							"urn:schemas:contacts:telephoneNumber", 
                            "urn:schemas:contacts:bday", 
							"urn:schemas:contacts:nickname",
                            "urn:schemas:contacts:o", 
							"urn:schemas:contacts:profession"
                        FROM
                            Scope('SHALLOW TRAVERSAL OF "%s/exchange/%s/contacts"')
                        WHERE
                            "urn:schemas:contacts:givenName" LIKE '%s%%'
                        OR
                            "urn:schemas:contacts:sn" LIKE '%s%%'
                    </g:sql>
                </g:searchrequest>
XMLDATA;


	
	function __construct() {
		include(dirname(__FILE__)."/"."config.php");
		$this->serverURI = "http://".$exchangeServer;
		
		Zend_Loader::loadClass('Zend_Http_Client');
		$this->client = new Zend_Http_Client();
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
		$list = array();
		
	
		$this->client->setUri($this->serverURI."/exchange/".$this->username);
		$this->client->setConfig(array('maxredirects' => 0,'timeout'=> 30));
		$this->client->setAuth($this->username, $this->password, Zend_Http_Client::AUTH_BASIC);
		
		
		$xml = sprintf(self::$xmldata, $this->serverURI, $this->username, "", "");
		$response = $this->client->setRawData($xml, 'text/xml')->request('SEARCH');
		
		if($response->getStatus() != 200){
			return $list;
		}

		$xml = simplexml_load_string($response->getBody());
		print_r($xml);
		die();
		  
		  
		$item = new PhonebookEntry($this->generateID($xml) , (string)$xml->name->fullName);
		array_push($list, $item);

		return $list;
	}


	function getEntry($id){
		
	
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

	public function search($lastname, $firstname){
		$list = array();
		
	
		$this->client->setUri($this->serverURI."/exchange/".$this->username);
		$this->client->setConfig(array('maxredirects' => 0,'timeout'=> 30));
		$this->client->setAuth($this->username, $this->password, Zend_Http_Client::AUTH_BASIC);
		
		
		$xml = sprintf(self::$xmldata, $this->serverURI, $this->username, $firstname, $lastname);
		
		$response = $this->client->setRawData($xml, 'text/xml')->request('SEARCH');
		
		if($response->getStatus() != 200){
			return $list;
		}

		$xml = simplexml_load_string($response->getBody());
		print_r($xml);
		die();
		
		$item = new PhonebookEntry($this->generateID($xml) , (string)$xml->name->fullName);
		array_push($list, $item);

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