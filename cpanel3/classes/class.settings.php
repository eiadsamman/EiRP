<?php
class Settings{
	private $_settings_array;
	protected static $_settings_list;
	protected static $_settings_file;
	protected static $_settings_list_xmlstyle;
	protected static $_settings_success=false;
	public function __construct(){
		$this->_settings_array=array();
	}
	public function settings_sql_lib(){
		return array("sqli");
	}

	public final function settings_setfile($settingsfile){
		$settingsfile=@realpath($settingsfile);
		if(@is_file($settingsfile)){
			self::$_settings_file = $settingsfile;
		}else{
			self::$_settings_file = null;
		}
	}
	public final function settings_fetch(){
		self::$_settings_success=false;
		if(is_null(self::$_settings_file)){
			return false;
		}
		$this->settings_reset_array();
		$dom=new DOMDocument('1.0');
		$temp=$this->_settings_array;
		if(@$dom->load(self::$_settings_file)){
			$xmlparser=$dom->documentElement;
			if($xmlparser->hasChildNodes()){
				foreach($xmlparser->childNodes as $setting){
					if($xmlparser->nodeType==XML_ELEMENT_NODE){
						if(array_key_exists($setting->nodeName,$temp)){
							if($setting->hasChildNodes()){
								foreach($setting->childNodes as $vars){
									if($vars->nodeType == XML_ELEMENT_NODE){
										if(array_key_exists($vars->nodeName,$temp[$setting->nodeName])){
											$temp_value=$vars->nodeValue;
											if($temp[$setting->nodeName][$vars->nodeName]['attrib']=="boolean"){
												$temp_value=in_array(trim(strtolower($temp_value)),array("true","1"))?true:false;
											}elseif($temp[$setting->nodeName][$vars->nodeName]['attrib']=="timezone"){
												$temp_value=trim($temp_value);
												$temp_value=in_array($temp_value,DateTimeZone::listIdentifiers())?$temp_value:"UTC";
											}
											$temp[$setting->nodeName][$vars->nodeName]['value']=$temp_value;
										}
									}
								}
							}
						}
					}	
				}
			}
			self::$_settings_success=true;
			self::$_settings_list_xmlstyle=$temp;
			self::settings_convert_array();
			return true;
		}else{
			return false;
		}
	}
	public final function settings_write($settings){
		if(is_null(self::$_settings_file)){return false;}
		$this->settings_fetch();
		$old_settings=$this->settings_read();
		
		$this->settings_reset_array();
		$settingsform=$this->_settings_array;
		$dom=new DOMDocument('1.0');
		$dom->encoding = 'UTF-8';
		$lvl1 = $dom->createElement("settings");
		$dom->appendChild($lvl1);
		$lvl1->appendChild( $dom->createTextNode("\n") );
		foreach($this->_settings_array as $__set=>$__setv){
			if(array_key_exists($__set,$settings)){
				$lvl2 = $dom->createElement((string)$__set);
				$lvl1->appendChild($lvl2);
				foreach($__setv as $__var=>$__varv){
					if(array_key_exists($__var,$settings[$__set])){
						$lvl2->appendChild( $dom->createTextNode("\n\t") );
						//Create settings variable
						$lvl3 = $dom->createElement((string)$__var);
						//Don't write on readonly fields
						if($settingsform[$__set][$__var]['attrib']!="readonly"){
							//Attribute manager
							if($settingsform[$__set][$__var]['attrib']=="boolean"){
								$settings[$__set][$__var]=trim(strtolower($settings[$__set][$__var]));
								$settings[$__set][$__var]=in_array($settings[$__set][$__var],array("true","1"))?"true":"false";
							
							}elseif($settingsform[$__set][$__var]['attrib']=="timezone"){
								$settings[$__set][$__var]=in_array(urldecode($settings[$__set][$__var]),DateTimeZone::listIdentifiers())?urldecode($settings[$__set][$__var]):"UTC";
							}elseif($settingsform[$__set][$__var]['attrib']=="VERSION"){
								$settings[$__set][$__var]=VERSION;
							}elseif($settingsform[$__set][$__var]['attrib']=="LICENSE"){
								$settings[$__set][$__var]=LICENSE;
							}
							//CDATA
							
							if( isset($settingsform[$__set][$__var]['TYPE']) && $settingsform[$__set][$__var]['TYPE']=="CDATA"){
								$lvl3->appendChild( $dom->createCDATASection(($settings[$__set][$__var])));
							}else{
								$lvl3->appendChild( $dom->createTextNode($settings[$__set][$__var]));
							}
						}
						$lvl2->appendChild($lvl3);
					}
				}
				$lvl2->appendChild( $dom->createTextNode("\n") );
				$lvl1->appendChild( $dom->createTextNode("\n") );
			}
		}
		if($dom->save(self::$_settings_file)){
			$this->settings_fetch();
			return true;
		}else{
			return false;
		}
	}
	
	public function settings_form(){
		return array(
			"cpanel"=>array(
				'version'		=>array('TYPE'=>'TEXTNODE','value'=>NULL,'attrib'=>'VERSION'),
				'license'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>'LICENSE'),
				'username'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'password'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'database_check'=>array('TYPE'=>'TEXTNODE','value'=>NULL,'attrib'=>'boolean'),
			),
			"database"=>array(
				'host'			=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'username'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'password'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>'password'),
				'name'			=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
			),
			"site"=>array(
				'site_version'	=>array('TYPE'=>'TEXTNODE','value'=>NULL,'attrib'=>''),
				'subdomain'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'forcehttps'	=>array('TYPE'=>'TEXTNODE','value'=>NULL,'attrib'=>'boolean'),
				'index'			=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'auther'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'title'			=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'keywords'		=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'description'	=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
				'timezone'		=>array('TYPE'=>'TEXTNODE','value'=>NULL,'attrib'=>'timezone'),
				'errorlog'		=>array('TYPE'=>'TEXTNODE','value'=>NULL,'attrib'=>'boolean'),
				'errorlogfile'	=>array('TYPE'=>'CDATA','value'=>NULL,'attrib'=>''),
			),
		);
	}
	private function settings_reset_array(){
		$this->_settings_array=$this->settings_form();
	}
	private static function settings_convert_array(){
		$temp=array();
		foreach(self::$_settings_list_xmlstyle as $k=>$v){
			$temp[$k]=array();
			foreach($v as $vk=>$vv){
				$temp[$k][$vk]=$vv['value'];
			}
		}
		self::$_settings_list=$temp;
	}
	public final function settings_read(){
		if(is_null(self::$_settings_list)){return false;}

		return self::$_settings_list;
	}
}
?>