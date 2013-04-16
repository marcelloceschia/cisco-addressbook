<?php

class Language{
	
	private static $languageMapping = array(
		"de-DE" => "Language_DE",
		"de" => "Language_DE",
		"nl-NL" => "Language_NL",
		"nl" => "Language_NL",
		"fr-FR" => "Language_FR",
		"fr" => "Language_FR",
		"be-NL" => "Language_NL",
		"be-FR" => "Language_FR",
		"be" => "Language_FR",
	);

	protected $entries = array(
		"#001#" => "Firstname",
		"#002#" => "Lastname",
		"#003#" => "List all entries",
		"#004#" => "search",
	);

	public static function getLocal($lang = null){
		if(!isset($lang)){
			$lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		if(isset(self::$languageMapping[$lang])){
			try{
				$reflect = new ReflectionClass(self::$languageMapping[$lang]);
				return $reflect->newInstance();
			}catch(ReflectionException $re){
				
			}
		}
		return new Language();
	}

	public function getEntry($entry){
		return $this->entries[$entry];
	}
}

class Language_DE extends Language{
	private static $local = array(
		"#001#" => "Vorname",
		"#002#" => "Nachname",
 		"#003#" => "Alle Einträge anzeigen",
		"#004#" => "Suchen",
	);

	public function  __construct() { 
		$this->entries = array_merge($this->entries, self::$local);
	}
}

class Language_NL extends Language{
	private static $local = array(
		"#001#" => "Voornaam",
		"#002#" => "Achternaam",
 		"#003#" => "Alle regels tonen",
		"#004#" => "Zoeken",
	);

	public function  __construct() { 
		$this->entries = array_merge($this->entries, self::$local);
	}
}

class Language_FR extends Language{
	private static $local = array(
		"#001#" => "Prénom",
		"#002#" => "Nom de familie",
 		"#003#" => "Toutes les entrées",
		"#004#" => "Chercher",
	);

	public function  __construct() { 
		$this->entries = array_merge($this->entries, self::$local);
	}
}

?>
