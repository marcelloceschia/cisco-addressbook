<?php

class Egroupware implements Backend{
	private $username = "";
	private $password;

	private $mysqlLink;
	private $databaseUsername = "root";
	private $databasePassword = "";
	private $databaseName = "egroupware";
	private $databaseHost = "localhost";
	
	/* read contact entries */
	private	$entryDef = array("tel_work" => "work", 
				  "tel_cell" => "mobile", 
				  "tel_fax" => "fax", 
				  "tel_assistent" => "Assistent",
				  "tel_car" => "car",
				  "tel_pager" => "pager",
				  "tel_home" => "home",
				  "tel_fax_home" => "fax_home",
				  "tel_cell_private" => "private");


	function __construct() {
		$this->mysqlLink = mysql_connect($this->databaseHost, $this->databaseUsername, $this->databasePassword);
		mysql_select_db($this->databaseName, $this->mysqlLink);
	}

	function __destruct() {
		mysql_close($this->mysqlLink);
	}
	
	
	function setUsername($username){
		$this->username = $username;
	}
	
	function setPassword($password){
		$this->password = $password;
	}


	function getEntries($offset, $limit = null){
		$list = array();
		$query = "SELECT DISTINCT n_fn, contact_uid FROM egw_addressbook WHERE contact_owner = '0' OR contact_owner = (SELECT account_id FROM egw_accounts WHERE account_lid = '".mysql_real_escape_string($this->username)."') ORDER BY n_family, n_given";
		
		$result = mysql_query($query, $this->mysqlLink);

		while ($row = mysql_fetch_array($result, MYSQL_BOTH)) {
			
			$item = new PhonebookEntry($row['contact_uid'], $row['n_fn']);//$this->createPhonebookEntry($contacts[$i]);
			array_push($list, $item);
			
		}
		mysql_free_result($result);

		return $list;
	}

// 	private function createEntry($row){
// 		$entries = array();
// 		
// 
// 		/* read contact entries */
// 		$entryDef = array("tel_work" => "work", 
// 				  "tel_cell" => "mobile", 
// 				  "tel_fax" => "fax", 
// 				  "tel_assistent" => "Assistent",
// 				  "tel_car" => "car",
// 				  "tel_pager" => "pager",
// 				  "tel_home" => "home",
// 				  "tel_fax_home" => "fax_home",
// 				  "tel_cell_private" => "private");
// 
// 		foreach($entryDef as $column => $name){
// 			if(isset($row[$column]) && !empty($row[$column])){
// 				$entry = new DirectoryEntry( $name, $name,  utf8_decode($row[$column]) );
// 				array_push($entries, $entry);
// 			}
// 		}
// 		
// 		return $entries;
// 	}


	function getEntry($id){
		$sql = "SELECT * FROM egw_addressbook WHERE (contact_owner = '0' OR contact_owner = (SELECT account_id FROM egw_accounts WHERE account_lid = '".mysql_real_escape_string($this->username)."')) AND contact_uid = '".mysql_real_escape_string($id)."'";
		
		
		$result = mysql_query($sql, $this->mysqlLink);
		$row = mysql_fetch_array($result, MYSQL_BOTH);

		$directory = new PhonebookContact( $id , $row['n_fn']);
		
		foreach($this->entryDef as $column => $name){
			if(isset($row[$column]) && !empty($row[$column])){
				$directory->addContactEntry($name, utf8_decode($row[$column]));
			}
		}
		
		return $directory;
		
	}

	public function search($lastname, $firstname){
		$list = array();
		
		
		$query = "SELECT DISTINCT n_fn, contact_uid FROM egw_addressbook WHERE (contact_owner = '0' OR contact_owner = (SELECT account_id FROM egw_accounts WHERE account_lid = '".mysql_real_escape_string($this->username)."'))";
		
		if(isset($lastname)){
			$query .= " AND upper(n_family) LIKE upper('".mysql_real_escape_string($lastname)."%')";
		}
		if(isset($firstname)){
			$query .= " AND upper(n_given) LIKE upper('".mysql_real_escape_string($firstname)."%')";
		}
		$query .= " ORDER BY n_family, n_given";


		$result = mysql_query($query, $this->mysqlLink);
		while ($row = mysql_fetch_array($result, MYSQL_BOTH)) {

			$item = new PhonebookEntry($row['contact_uid'], $row['n_fn']);//$this->createPhonebookEntry($contacts[$i]);
			array_push($list, $item);
			
		}
		mysql_free_result($result);
		return $list;
	}

	public function reverseLookup($number){
		
		$number = mysql_real_escape_string($number);
		
		$query = "SELECT DISTINCT * FROM egw_addressbook WHERE (contact_owner = '0' OR contact_owner = (SELECT account_id FROM egw_accounts WHERE account_lid = '".mysql_real_escape_string($this->username)."'))";
		$query .= " AND ( ";
		$query .= " tel_work = '".$number."'";
		$query .= " OR tel_cell  = '".$number."'";
		$query .= " OR tel_fax   = '".$number."'";
		$query .= " OR tel_assistent = '".$number."'";
		$query .= " OR tel_car   = '".$number."'";
		$query .= " OR tel_pager  = '".$number."'";
		$query .= " OR tel_home  = '".$number."'";
		$query .= " OR tel_fax_home  = '".$number."'";
		$query .= " OR tel_cell_private  = '".$number."'";
		$query .= " OR tel_other  = '".$number."'";
		$query .= " OR tel_prefer  = '".$number."'";
		$query .= " ) ";
		
		
		$query .= " LIMIT 1";

		$result = mysql_query($query, $this->mysqlLink);
		$row = mysql_fetch_array($result, MYSQL_BOTH);
		mysql_free_result($result);
		return $row;
	}
}

?>
