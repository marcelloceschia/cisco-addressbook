<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__)."/includes/class");



class Exchange implements Backend{
	private $username = '';
	private $password = '';
	
	private $client;

	function __construct() {
		
	}

	function __destruct() {
		
	}
	
	function setUsername($username){
		$this->username = $username;
	}
	
	function setPassword($password){
		$this->password = $password;
	}
	
	function createClient(){
		$this->client = new ExchangeNTLMSoapClient(dirname(__FILE__)."/services.wsdl");
		//$this->client = new ExchangeNTLMSoapClient("https://exchange.de/EWS/Exchange.asmx", array("login" => $this->username, "password" => $this->password));
		$this->client->setUsername($this->username);
		$this->client->setPassword($this->password);
	}


	function getEntries($offset, $limit = null){
		$list = array();
		$max = isset($limit)?$limit:10000;
		
		
		$this->createClient();
		
		$FindItem->Traversal = 'Shallow';
		$FindItem->ItemShape->BaseShape = 'AllProperties';
		//$FindItem->ParentFolderIds->FolderId->Id = $tois_folder->FolderId->Id;
		$FindItem->ParentFolderIds->DistinguishedFolderId->Id = 'contacts';
		$result = $this->client->FindItem($FindItem);

		$contacts = $result->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Contact;

		for($i = $offset; $i < count($contacts) && $i < ($offset+$max) ;$i++){
			if($contacts[$i]->PhoneNumbers->Entry != null){
				$item = new PhonebookEntry($this->generateID($contacts[$i]) , $contacts[$i]->DisplayName);
				array_push($list, $item);
			}
		}
		return $list;
	}

	private function createEntry($contact){
		$entries = array();

		if(isset($contact->PhoneNumbers) && isset($contact->PhoneNumbers->Entry)){
		
			if(is_array($contact->PhoneNumbers->Entry)){
				for ($i = 0; $i < count($contact->PhoneNumbers->Entry); $i++) {
					
					if($contact->PhoneNumbers->Entry[$i]->Key != ""){
						$entry = new DirectoryEntry( $contact->PhoneNumbers->Entry[$i]->Key, $contact->PhoneNumbers->Entry[$i]->Key,  $contact->PhoneNumbers->Entry[$i]->_) ;
						$entries[] = $entry;
					}
				}
			}else{
				$entry = new DirectoryEntry( $contact->PhoneNumbers->Entry->Key, $contact->PhoneNumbers->Entry->Key,  $contact->PhoneNumbers->Entry->_);
				$entries[] = $entry;
			}
		  
		}
		
		return $entries;
	}

	private function createMenuEntry($contact){

		$item = new MenuItem( $contact->ItemId->ChangeKey , $contact->DisplayName );
		return $item;
	}

	function getEntry($id){
		$this->createClient();
	
		$FindItem->Traversal = 'Shallow';
		$FindItem->ItemShape->BaseShape = 'AllProperties';
		//$FindItem->ParentFolderIds->FolderId->Id = $tois_folder->FolderId->Id;
		$FindItem->ParentFolderIds->DistinguishedFolderId->Id = 'contacts';
		$result = $this->client->FindItem($FindItem);
		
		$row = null;

		$contacts = $result->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Contact;
		foreach($contacts as $contact){
			
			if($this->generateID($contact) == $id){
				 $row = $contact;
				 break;
			}			
		}
		
		$directory = new PhonebookContact( $contact->ItemId->ChangeKey , $contact->DisplayName );
		
		if(isset($contact->PhoneNumbers) && isset($contact->PhoneNumbers->Entry)){
		
			if(is_array($contact->PhoneNumbers->Entry)){
				for ($i = 0; $i <= count($contact->PhoneNumbers->Entry); $i++) {
					
					if($contact->PhoneNumbers->Entry[$i]->Key != "" && $contact->PhoneNumbers->Entry[$i]->_ != ""){
						$directory->addContactEntry($contact->PhoneNumbers->Entry[$i]->Key, $contact->PhoneNumbers->Entry[$i]->_);
					}
				}
			}else{
				$directory->addContactEntry($contact->PhoneNumbers->Entry->Key, $contact->PhoneNumbers->Entry->_);
			}
		  
		}
		return $directory;
		
	}
	
	public function search($lastname = null, $firstname = null){
		$list = array();
		$max = isset($limit)?$limit:10000;
		$offset = 0;
		
		
		$this->createClient();
		
		$FindItem->Traversal = 'Shallow';
		$FindItem->ItemShape->BaseShape = 'AllProperties';
		//$FindItem->ParentFolderIds->FolderId->Id = $tois_folder->FolderId->Id;
		$FindItem->ParentFolderIds->DistinguishedFolderId->Id = 'contacts';
		$result = $this->client->FindItem($FindItem);

		$contacts = $result->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Contact;
		
		for($i = $offset; $i < count($contacts) && $i < ($offset+$max) ;$i++){
			$cFirstName = isset($contacts[$i]->CompleteName->FirstName) ? $contacts[$i]->CompleteName->FirstName : "";
			$cLastName = isset($contacts[$i]->CompleteName->LastName) ? $contacts[$i]->CompleteName->LastName : "";

			if((isset($lastname) && StrStartsWith(strtolower($cLastName), strtolower($lastname))) || (isset($firstname) && StrStartsWith(strtolower($cFirstName), strtolower($firstname))) ){
 				$item = new PhonebookEntry( $this->generateID($contacts[$i]) , $contacts[$i]->DisplayName);
 				array_push($list, $item);
			}
		}
		return $list;
	}
	
	public function reverseLookup($number){
		$item = null;
	
		/* normalize number format */
		$numberSearchingFor = numberingNormal($number);
		
		
		$this->createClient();
		
		$FindItem->Traversal = 'Shallow';
		$FindItem->ItemShape->BaseShape = 'AllProperties';
		//$FindItem->ParentFolderIds->FolderId->Id = $tois_folder->FolderId->Id;
		$FindItem->ParentFolderIds->DistinguishedFolderId->Id = 'contacts';
		$result = $this->client->FindItem($FindItem);

		$contacts = $result->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Contact;
		

		for($i = $offset; $i < count($contacts); $i++){
			if(is_array($contacts[$i]->PhoneNumbers->Entry)){
				for ($j = 0; $j <= count($contacts[$i]->PhoneNumbers->Entry); $j++) {
					if($contacts[$i]->PhoneNumbers->Entry[$j]->_ != "" && (numberingNormal($contacts[$i]->PhoneNumbers->Entry[$j]->_) == numberingNormal($numberSearchingFor) ) ){
					
						$item = new PhonebookContact( $this->generateID($contacts[$i]) , $contacts[$i]->DisplayName);
						
						for ($j = 0; $j <= count($contacts[$i]->PhoneNumbers->Entry); $j++) {
							if($contacts[$i]->PhoneNumbers->Entry[$j]->Key != ""){
								$item->addContactEntry($contacts[$i]->PhoneNumbers->Entry[$j]->Key, $contacts[$i]->PhoneNumbers->Entry[$j]->_);
							}
						}
						return $item;
					}
				}
				
			}else{
				if(numberingNormal($contacts[$i]->PhoneNumbers->Entry->_) == numberingNormal($numberSearchingFor) ){
					$item = new PhonebookContact( $this->generateID($contacts[$i]) , $contacts[$i]->DisplayName);
					$item->addContactEntry($contacts[$i]->PhoneNumbers->Entry->Key, $contacts[$i]->PhoneNumbers->Entry->_);
					return $item;
				}
			}
		}
		return $item;
	}
	
	private function generateID($contact){
		
		return md5($contact->FileAs.$contact->DisplayName);
	}
}

function StrStartsWith($str, $search){

	if(!isset($search) ||$search == "")
		return 0;
	if(!isset($str) ||$str == "")
		return 0;

	return strpos(strtoupper(utf8_decode($str)), strtoupper($search)) === 0;
}
function numberingNormal($number){
	$result = $number;
	$result = str_replace("+", "00", $result);
	$result = str_replace(array(" ", "(", ")"), "", $result);
	
	return $result;
}
?>
