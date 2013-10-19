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
		"#004#" => "Search",
		"#010#" => "Dial",
		"#011#" => "Back",
		"#012#" => "Close",
		"#013#" => "Show",
		"#014#" => "Authentication Problem",
		"#015#" => "Please check your authentication settings",
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
		"#010#" => "Wählen",
		"#011#" => "Zurück",
		"#012#" => "Schließen",
		"#013#" => "Anzeigen",
		"#014#" => "Authentication Problem",
		"#015#" => "Please check your authentication settings",
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
		"#010#" => "Bellen",
		"#011#" => "Teruggaan",
		"#012#" => "Fermer",
		"#013#" => "Tonen",
		"#014#" => "Authentication Problem",
		"#015#" => "Please check your authentication settings",
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
		"#010#" => "Appeler",
		"#011#" => "Reculer",
		"#012#" => "Fermer",
		"#013#" => "Montrer",
		"#014#" => "Authentication Problem",
		"#015#" => "Please check your authentication settings",
	);

	public function  __construct() { 
		$this->entries = array_merge($this->entries, self::$local);
	}
}

?>
