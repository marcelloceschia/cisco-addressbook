<?php
function xmlNameEncoding($str){
	$str = str_replace("&", "&amp;", $str);
	
	return $str;
}
class XMLEntry{
	function xmlNameEncoding($str){
        	$str = str_replace("&", "&amp;", $str);

	        return $str;
	}
}

class DirectoryEntry extends XMLEntry{
	private $name;
	private $type;
	private $value;


	function __construct($name, $type, $value) {
		$this->name = $name;
		$this->type = $type;
		$this->value = $value;
	}


	function setName($name){
		$this->name = $name;
	}

	function setType($type){
		$this->type = $type;
	}


	function toXML(){
		$xml = "";
		$xml .= "<DirectoryEntry>\n";
		$xml .= "\t\t<Name>".$this->xmlNameEncoding($this->name)."</Name>\n";
		$xml .= "\t\t<Telephone type=\"".$this->type."\">".$this->value."</Telephone>\n";
		$xml .= "\t</DirectoryEntry>\n";
		return $xml;
	}

}

class DirectoryContactEntry{
	private $type;
	private $value;

	function __construct($type, $value) {
		$this->type = $type;
		$this->value = $value;
	}


	function toXML(){
		$xml = "";
		$xml .= "<Telephone type=\"".$this->type."\">".$this->value."</Telephone>\n";
		return $xml;
	}
}

class MenuItem{
	private $id;
	private $name;
	private $url;
	private $queryParams = array();


	function __construct($id, $name,$url = null) {
		$this->id = $id;
		$this->name = $name;
		$this->url = $url;
	}

	function addQueryParam($name, $value) {
		$this->queryParams[$name] = $value;
	}
	
	function setQueryParams($params) {
		$this->queryParams = $params;
	}
	
	function setURL($url) {
		$this->url = $url;
	}


	function toXML(){
	
		$name = xmlNameEncoding($this->name);
		
		$xml = "";
		$xml .= "<MenuItem>\n";
		$xml .= "\t\t<Name>".$name."</Name>\n";
		$xml .= "\t\t<URL>".$this->url;

		if(count($this->queryParams) > 0){
			$i = 0;
			foreach($this->queryParams as $p => $v){
				$xml .= ($i > 0) ? "&amp;" : "?";
				$xml .= $p."=".$v;
				$i++;
			}
		}
		$xml .= "</URL>\n";
		$xml .= "\t</MenuItem>\n";

		return $xml;
	}
}

class CiscoIPPhoneDirectory extends XMLEntry{
	private $title = "IP Telephony Directory";
	private $prompt = "";
	private $url;
	private $entries = array();
	private $softkeys = array();


	function __construct($entries) {
		$this->entries = $entries;
	}

	function addDirectoryEntry($entry){
		array_push($this->entries, $entry);
	}

	function setTitle($title){
		$this->title = $title;
	}
	
	function setURL($url) {
		$this->url = $url;
	}
	
	function setPrompt($prompt) {
		$this->prompt = $prompt;
	}

	function addSoftkey($softKey) {
                array_push($this->softkeys, $softKey);
        }

	function toXML(){
		$xml = "";
		$xml .= "<CiscoIPPhoneDirectory>\n";
		$xml .= "\t<Title>".$this->xmlNameEncoding($this->title)."</Title>\n";
		$xml .= "\t<Prompt>".$this->prompt."</Prompt>\n";
		foreach($this->entries as $entry){
			$xml .= "\t".$entry->toXML();
		}
		foreach($this->softkeys as $entry){
                        $xml .= "\t".$entry->toXML();
                }
		$xml .= "</CiscoIPPhoneDirectory>\n";
		return $xml;
	}
}

class CiscoIPPhoneMenu extends XMLEntry{
	private $title = "IP Telephony Directory";
	private $prompt = "";
	private $url;
	private $queryParams = array();
	private $entries = array();
	private $softkeys = array();


	function __construct($entries = array()) {
		$this->entries = $entries;
	}

	function addDirectoryEntry($entry){
		array_push($this->entries, $entry);
	}
	
	function setURL($url) {
		$this->url = $url;
	}
	
	function addQueryParam($name, $value) {
		$this->queryParams[$name] = $value;
	}
	
	function setPrompt($prompt) {
		$this->prompt = $prompt;
	}

	function addSoftkey($softKey) {
		array_push($this->softkeys, $softKey);
	}

	function toXML(){
		$xml = "";
		$xml .= "<CiscoIPPhoneMenu>\n";
		$xml .= "\t<Title>".$this->xmlNameEncoding($this->title)."</Title>\n";
		$xml .= "\t<Prompt>".$this->prompt."</Prompt>\n";
		foreach($this->entries as $entry){
			$xml .= "\t".$entry->toXML();
		}
		foreach($this->softkeys as $entry){
			$xml .= "\t".$entry->toXML();
		}
		$xml .= "</CiscoIPPhoneMenu>\n";
		return $xml;
	}
}

class CiscoIPPhoneInput extends XMLEntry{
	private $title = "IP Telephony Directory";
	private $prompt = "";
	private $url;
	private $entries = array();
	
	function __construct($title, $prompt) {
		$this->title = $title;
		$this->prompt = $prompt;
	}
	
	function setURL($url) {
		$this->url = $url;
	}
	
	function addInputItem($item) {
		array_push($this->entries, $item);
	}
	
	function toXML(){
		$xml = "";
		$xml .= "<CiscoIPPhoneInput>\n";
		$xml .= "\t<Title>".$this->xmlNameEncoding($this->title)."</Title>\n";
		$xml .= "\t<Prompt>".$this->prompt."</Prompt>\n";
		$xml .= "\t\t<URL>".$this->url."</URL>\n";
		foreach($this->entries as $entry){
			$xml .= "\t".$entry->toXML();
		}
		$xml .= "</CiscoIPPhoneInput>\n";
		return $xml;
	}
  
}

class CiscoIPPhoneInputItem{
	private $displayName;
	private $queryStringParam;
	private $inputFlags = "A";
	private $defaultValue;
	
	function __construct($displayName, $queryStringParam) {
		$this->displayName = $displayName;
		$this->queryStringParam = $queryStringParam;
	}
	
	function toXML(){
		$xml = "";
		$xml .= "<InputItem>\n";
		
		$xml .= "\t<DisplayName>".$this->displayName."</DisplayName>\n";
		$xml .= "\t<QueryStringParam>".$this->queryStringParam."</QueryStringParam>\n";
		$xml .= "\t<InputFlags>".$this->inputFlags."</InputFlags>\n";
		$xml .= "\t<DefaultValue>".$this->defaultValue."</DefaultValue>\n";

		$xml .= "</InputItem>\n";
		return $xml;
	}
  
}


class CiscoIPPhoneSoftkey {
	private $name = "";
	private $url;
	private $position = 1;
	
	function __construct($name, $url, $position) {
		$this->name = $name;
		$this->url = $url;
		$this->position = $position;
	}
	
	function toXML(){
		$xml = "";
		$xml .= "\t<SoftKeyItem>\n";
		$xml .= "\t\t<Name>".$this->name."</Name>\n";
		$xml .= "\t\t<URL>".$this->url."</URL>\n";
		$xml .= "\t\t<Position>".$this->position."</Position>\n";
		$xml .= "\t</SoftKeyItem>\n";
		return $xml;
	}
}


class CiscoIPPhoneText{
	private $title = "IP Telephony Directory";
	private $prompt = "";
	private $text;
	
	function __construct($title, $text, $prompt= null) {
		$this->title = $title;
		$this->text = $text;
		$this->prompt = $prompt;
	}
	
	function setPrompt($prompt) {
		$this->prompt = $prompt;
	}
	
	function toXML(){
		$xml = "";
		$xml .= "<CiscoIPPhoneText>\n";
		$xml .= "\t<Title>".$this->title."</Title>\n";
		$xml .= "\t<Text>".$this->text."</Text>\n";
		if(isset($this->prompt)){
			$xml .= "\t<Prompt>".$this->position."</Prompt>\n";
		}
		$xml .= "</CiscoIPPhoneText>\n";
		return $xml;
	}
}
?>
