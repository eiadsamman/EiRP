<?php
class CustomDateTimeZone extends Settings{
	private $_timezone;
	private $_default_timezone;
    public function __construct() {
		$this->_default_timezone=date_default_timezone_get();
	}
	public function SetDateTimeZone(){
		$this->_timezone=null;
		$timezone=null;
		if(func_num_args()>=1){
			if(is_string(func_get_arg(0))){
				$timezone=func_get_arg(0);
			}
		}
		if(is_null($timezone)){
			if(parent::$_settings_success!=true){return false;}
			$settings=parent::$_settings_list;
			if(!is_null($settings['site']['timezone']) && trim($settings['site']['timezone'])!=''){
				$timezone=$settings['site']['timezone'];
			}
		}
		if(is_null($timezone)){
			$this->_timezone=null;
			return false;
		}else{
			if(@date_default_timezone_set($timezone)){
				$this->_timezone=$timezone;
				return true;
			}else{
				return false;
			}
		}
	}
	public function __get($name){
		if($name=='current'){
			return $this->_timezone;
		}elseif($name=='default'){
			return $this->_default_timezone;
		}else{
			return null;
		}
	}
	public function __set ($name,$value){
		return false;
	}
}
?>