<?php

class PhonebookEntry{
	private $id;
	private $name;


	function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}

	function getId(){
		return $this->id;
	}
	
	function getName(){
		return $this->name;
	}
}

class PhonebookContact{
	private $id;
	private $name;
	private $contactEntry = array();


	function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}

	function getId(){
		return $this->id;
	}
	
	function getName(){
		return $this->name;
	}
	
	function addContactEntry($type, $value){
		$this->contactEntry[$type] = $value;
	}
	
	function getContactEntry(){
		return $this->contactEntry;
	}
}
?>
