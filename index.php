<?php
$baseURI = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/";
$deviceMapping = array();
$enableCache = false;
$deviceCharset= "utf-8";

include("config.php");
require_once( "class/CiscoAddressbook.class.php");
require_once( "class/Backend.class.php");
require_once( "class/Phonebook.class.php");
require_once( "class/Language.class.php");
require_once( "class/vCard/vCard.php");

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__)."/class/");
require_once("Zend/Loader.php");
require_once("Zend/Cache/Manager.php");

function getIncludePathArray($path = null){
	if (null === $path) {
		$path = get_include_path();
	}

	if (PATH_SEPARATOR == ':') {
		$paths = preg_split('#:(?!//)#', $path);
	} else {
		$paths = explode(PATH_SEPARATOR, $path);
	}
	return $paths;
}

function __autoload($class_name) {
	global $backend;
	//$path =  preg_split( PATH_SEPARATOR, get_include_path() );
	$path = getIncludePathArray(get_include_path());
	foreach($path as $p){
		if(file_exists( $p."/".$class_name.".class.php" )){
			require_once( $p."/".$class_name.".class.php" );
		}
	}
}
if(isset($_SERVER['HTTP_ACCEPT_CHARSET'])){
	$values = explode( ';', $_SERVER['HTTP_ACCEPT_CHARSET'] );
	$values = explode( ',', $values[0] );
	
	$deviceCharset = $values[0];
}

/* caching part */
mb_internal_encoding("UTF-8");
$manager = new Zend_Cache_Manager;
$pbCacheSettings = array(
          'frontend' => array(
                  'name' => 'Output',
                  'options' => array(
                          'automatic_serialization' => TRUE,
                          'lifetime' => 86400
                  ),
          ),
          'backend' => array(
                  'name' => 'File',
                  'options' => array(
                          'cache_dir' => dirname(__FILE__)."/cache/",
                  ),
          )
);

/* check if cache directory is writeable */
if (!is_writable(dirname(__FILE__)."/cache/")) {
    $ciscoIPPhoneText = new CiscoIPPhoneText("Configuration", "The cache directory must be writeable");
    $xml = $ciscoIPPhoneText->toXML();
    echo $xml;
    die();
}
/* done - caching */


//echo Linkcreator::getIndexLink("SEP123456");

$manager->setCacheTemplate('pbcache', $pbCacheSettings);
$pbcache = $manager->getCache('pbcache');

$backend = null;
$lang = Language::getLocal();

$id = isset($_REQUEST['id'])?$_REQUEST['id']:null;
$do = isset($_REQUEST['action'])?$_REQUEST['action']:null;

/* if cache available use cached version and exit */
if ($enableCache && $data = $pbcache->start(md5( serialize($_REQUEST) ), false, false)) {
        header("Content-Type: text/xml; charset=".$deviceCharset);
        echo mb_convert_encoding(encode($data) , $deviceCharset, "UTF-8");
        die();
} 

/* use device mapping to set account info */
header("Content-Type: text/xml; charset=".$deviceCharset);

if(isset($_REQUEST['name']) && isset( $deviceMapping[ $_REQUEST['name'] ] )){
	
	$deviceName = $_REQUEST['name'];
	$backendName = $deviceMapping[ $_REQUEST['name'] ]['backend'];
	$backendName = strtolower($backendName);
	set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__)."/backend/".$backendName."/");
	
	try{
		$reflect = new ReflectionClass( $backendName );
	} catch(ReflectionException $re){
		$ciscoIPPhoneText = new CiscoIPPhoneText("missing backend", "The configured backend ".$backendName." does not exist.");
		$xml = $ciscoIPPhoneText->toXML();
		echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
		die();
	}
	
	/* check if we have a configuration for this */
	if($reflect == null){
		$ciscoIPPhoneText = new CiscoIPPhoneText("missing backend", "The configured backend ".$backendName." does not exist.");
		$xml = $ciscoIPPhoneText->toXML();
		echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
		die();
	}
	
	$backend = $reflect->newInstance();
	$backend->setUsername($deviceMapping[ $_REQUEST['name'] ]['username'] );
	$backend->setPassword($deviceMapping[ $_REQUEST['name'] ]['password'] );

	/* check if we like to force language */
	if(isset($deviceMapping[ $_REQUEST['name'] ]['language'])){
		unset($lang);
		$lang = Language::getLocal($deviceMapping[ $_REQUEST['name'] ]['language']);
	}
	/* done - force language*/
	
	/* set all other properties by checking if the bean setter exists */
	$elseConfig = array_diff_key( $deviceMapping[ $_REQUEST['name'] ], array("username" => null, "password" => null, "backend" => null, "language" => null));
	foreach($elseConfig  as $property => $value){
		$methodName = "set".$property;
		try{
			if (!$reflect->hasMethod($methodName) ){
				continue;
			}
			$method = $reflect->getMethod("set".$property);
			$method->invoke($backend, $value);
		} catch(ReflectionException $e){}
	}
	/* done */
}else{
	/* default values */
	$deviceName = "SEPxxxx";
	
	$ciscoIPPhoneText = new CiscoIPPhoneText("missing device mapping", "This device does not have a valide configuration");
	$xml = $ciscoIPPhoneText->toXML();
	echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
	die();
}

switch($do){
	case "showEntry":
		try {
			$entry = isset($_REQUEST['entry'])?$_REQUEST['entry']:null;
			$phonebookContactEntry = $backend->getEntry($entry);
			if(isset($phonebookContactEntry)){
				$contactEntries = array();
				
				foreach($phonebookContactEntry->getContactEntry() as $value){
					$e = new DirectoryEntry($value['type'], $value['type'], phoneNummerCorrect($value['value']) );
					array_push($contactEntries, $e);
				}
				
				$ciscoIPPhoneDirectory = new CiscoIPPhoneDirectory($contactEntries);
				if(strlen($phonebookContactEntry->getName()) > 32){
					$ciscoIPPhoneDirectory->setTitle(substr($phonebookContactEntry->getName(),0,29));
				}else{
					$ciscoIPPhoneDirectory->setTitle($phonebookContactEntry->getName());
				}
				//$ciscoIPPhoneDirectory->setPrompt($phonebookContactEntry->getName());

				$ciscoIPPhoneDirectory->addSoftkey(new CiscoIPPhoneSoftkey($lang->getEntry('#010#'), "SoftKey:Dial", 1 ) );
				$ciscoIPPhoneDirectory->addSoftkey(new CiscoIPPhoneSoftkey($lang->getEntry('#011#'), "SoftKey:Exit", 2 ) );
				$ciscoIPPhoneDirectory->addSoftkey(new CiscoIPPhoneSoftkey($lang->getEntry('#012#'), "Init:Services", 3 ) );
				
				$xml = $ciscoIPPhoneDirectory->toXML();
			}
		} catch (AuthenticationException $ae) {
			$ciscoIPPhoneText = new CiscoIPPhoneText($lang->getEntry('#014#'), $lang->getEntry('#015#'));
			$xml = $ciscoIPPhoneText->toXML();
		}
		
		echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
	break;

	default:
	case "showIndex":
		$indexEntries = new CiscoIPPhoneMenu();
		$indexEntries->setURL($baseURI);
		$indexEntries->addDirectoryEntry( new MenuItem("001", $lang->getEntry('#003#'),  Linkcreator::getListLink($deviceName) ) );
		$indexEntries->addDirectoryEntry( new MenuItem("002", $lang->getEntry('#004#'),  Linkcreator::getSearchLink($deviceName) ) );

		$xml = $indexEntries->toXML();
		echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
	break;
	
	case "listAll":
		$offset = isset($_REQUEST['offset'])?$_REQUEST['offset']:0;
		$limit = isset($_REQUEST['limit'])?$_REQUEST['limit']:15;
//		$entries = $backend->getEntries($offset, $limit);
		
		try {
			$entries = $backend->getEntries(0);
		
			$listEntries = new CiscoIPPhoneMenu();
			$listEntries->setURL($baseURI);

			for($i = $offset; $i < count($entries) && $i < ($offset+$limit) ;$i++){
				$entry = $entries[$i];
				
				$e = new MenuItem($entry->getId(), $entry->getName(), Linkcreator::getEntryLink($deviceName, $entry->getId()) );
				$listEntries->addDirectoryEntry($e);
			}
			
			if( $offset > 0 ){
				$listEntries->addSoftkey(new CiscoIPPhoneSoftkey("Prev", Linkcreator::getListLink($deviceName, (($offset-$limit) > 0 ? ($offset-$limit): 0)  ), 1 ) );
			}
			
			/* we have more entries */
			if( count($entries) > ($offset+$limit) ){
				$listEntries->addSoftkey(new CiscoIPPhoneSoftkey("Next", Linkcreator::getListLink($deviceName, ($offset+$limit)), 2 ) );
			}
			
			$listEntries->addSoftkey(new CiscoIPPhoneSoftkey($lang->getEntry('#013#'), "SoftKey:Select", 3 ) );
			$listEntries->addSoftkey(new CiscoIPPhoneSoftkey($lang->getEntry('#011#'), "SoftKey:Exit", 4 ) );
			
			$xml = $listEntries->toXML();
			
		} catch (AuthenticationException $ae) {
			$ciscoIPPhoneText = new CiscoIPPhoneText($lang->getEntry('#014#'), $lang->getEntry('#015#'));
			$xml = $ciscoIPPhoneText->toXML();
		}

		echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
	break;
	
	case "search":
		try {
			if(!isset($_REQUEST['firstname']) && !isset($_REQUEST['lastname']) ){
			      $searchInput = new CiscoIPPhoneInput("search person", "");
			      $searchInput->setURL(Linkcreator::getSearchLink($deviceName));
			      $searchInput->addInputItem( new CiscoIPPhoneInputItem($lang->getEntry('#001#'), "firstname") );
			      $searchInput->addInputItem( new CiscoIPPhoneInputItem($lang->getEntry('#002#'), "lastname") );
			      $xml = $searchInput->toXML();
			}else{
			      $firstname = isset($_REQUEST['firstname']) ? utf8_decode(urldecode($_REQUEST['firstname'])):null;
			      $lastname = isset($_REQUEST['lastname'])   ? utf8_decode(urldecode($_REQUEST['lastname'])):null;
			      $entries = $backend->search($lastname, $firstname);
			      $listEntries = new CiscoIPPhoneMenu();
			      $listEntries->setURL($baseURI);
			      
			      foreach($entries as $entry){
				      $e = new MenuItem($entry->getId(), $entry->getName(), Linkcreator::getEntryLink($deviceName, $entry->getId()) );
				      $listEntries->addDirectoryEntry($e);
			      }
			      $xml = $listEntries->toXML();
			}
		} catch (AuthenticationException $ae) {
			$ciscoIPPhoneText = new CiscoIPPhoneText($lang->getEntry('#014#'), $lang->getEntry('#015#'));
			$xml = $ciscoIPPhoneText->toXML();
		}
		echo mb_convert_encoding(encode($xml) , $deviceCharset, "UTF-8");
	break;
	
	case "reverse":

		$number = $_REQUEST['entry'];
		$phonebookContactEntry = $backend->reverseLookup($number);
// 		print_r($phonebookContactEntry);
//              echo mb_convert_encoding(encode($xml) , "ISO-8859-1", "UTF-8");

		echo $xml;
	break;
}

if ($enableCache){
	$pbcache->end();
}

function encode($str){
	return str_replace(array("ß","Ä", "Ö", "Ü","ä","ö","ü"), array("&#223;","&#196;","&#214;","&#220;","&#228;","&#246;","&#252;"), $str);  
}

function phoneNummerCorrect($str){
	return str_replace(array("+"), array("00"), $str);  
}
?>
