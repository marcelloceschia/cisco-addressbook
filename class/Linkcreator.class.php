<?php
class Linkcreator {
	private static $rewriteEnabled = null;
	private static $baseURI = null;
	private static $configFileName = ".config";
	
	
	private static function isRewriteEnabled(){
		self::$baseURI = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/";
	
		if (self::$rewriteEnabled != null){
			return (bool)self::$rewriteEnabled;
		} else {
		    return self::checkRewriteEnabled();
		}
	}
	
	private static function checkRewriteEnabled(){
		global $deviceMapping;
		self::$rewriteEnabled = false;
		
		$configFilePath = dirname($_SERVER['SCRIPT_FILENAME'])."/cache/".self::$configFileName;
		if ( file_exists( $configFilePath )){
			$data = unserialize( file_get_contents( $configFilePath ) );
			self::$rewriteEnabled = $data['rewriteEnabled'];
			return self::$rewriteEnabled;
		} else {
			$fh = fopen($configFilePath, 'w');
			fclose($fh);
			file_put_contents( $configFilePath, serialize( array("rewriteEnabled" => false) ) );
		}
		
	
		$allow_url_fopen =  (bool)ini_get("allow_url_fopen");
		if($allow_url_fopen){
			$keys = array_keys($deviceMapping);
			$url = "http://127.0.0.1/".dirname($_SERVER['SCRIPT_NAME'])."/".$keys[0]."/showIndex";
			$fp = fopen($url, 'r');
			if (strpos($http_response_header[0], '404') !== false){
				self::$rewriteEnabled = false;
			} else {
				self::$rewriteEnabled = true;
			}
		}
		
		file_put_contents( $configFilePath, serialize( array("rewriteEnabled" => self::$rewriteEnabled) ) );
		return self::$rewriteEnabled;
	}
	
	
	public static function getIndexLink( $deviceName ){
		if( self::isRewriteEnabled() ){
			return self::$baseURI.$deviceName."/showIndex";
		} else {
			return self::$baseURI."?name=".$deviceName;
		}
	}
	
	public static function getListLink( $deviceName, $offset = 0 ){
		if( self::isRewriteEnabled() ){
			return self::$baseURI.$deviceName."/listAll" . ($offset > 0 ? "/".$offset : "");
		} else {
			return self::$baseURI."?name=".$deviceName."&amp;action=listAll" . ($offset > 0 ? "&amp;entry=".$offset : "");
		}
	}
	
	public static function getSearchLink( $deviceName ){
		if( self::isRewriteEnabled() ){
			return self::$baseURI.$deviceName."/search";
		} else {
			return self::$baseURI."?name=".$deviceName."&amp;action=search";
		}
	}
	
	public static function getEntryLink( $deviceName, $entryId ){
		if( self::isRewriteEnabled() ){
			return self::$baseURI.$deviceName."/showEntry/".$entryId;
		} else {
			return self::$baseURI."?name=".$deviceName."&amp;action=showEntry&amp;entry=".$entryId;
		}
	}
}
?>
