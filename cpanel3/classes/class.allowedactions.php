<?php
class AllowedActions {
	private $_user_persmission=null;
	private $_arraylist_persmission=null;
	private $_array_actionlist=array(
		'deny'=>true,
		'read'=>false,
		'add'=>false,
		'edit'=>false,
		'delete'=>false,
	);
	public function __construct($userpersmission=null, $arraylistpermissions=null){
		if($userpersmission!=null && $arraylistpermissions!=null){
			$this->Read($userpersmission,$arraylistpermissions);
		}
	}
	
	public function Read(int $userpersmission,array $arraylistpermissions) {
		if(!isset($arraylistpermissions[$userpersmission])  || $arraylistpermissions[$userpersmission]<0 || $arraylistpermissions[$userpersmission]>15 ){
			return false;
		}
		$temp=str_pad(decbin($arraylistpermissions[$userpersmission]),4,"0",STR_PAD_LEFT);
		$this->_array_actionlist['deny']=($arraylistpermissions[$userpersmission]==0)?true:false;
		$this->_array_actionlist['read']=((int)$temp[0]==1?true:false);
		$this->_array_actionlist['add']=((int)$temp[1]==1?true:false);
		$this->_array_actionlist['edit']=((int)$temp[2]==1?true:false);
		$this->_array_actionlist['delete']=((int)$temp[3]==1?true:false);
	}
	public function Translate(int $permission){
		if($permission<0 || $permission>15 ){
			return false;
		}
		$temp=str_pad(decbin($permission),4,"0",STR_PAD_LEFT);
		$this->_array_actionlist['deny']=$permission==0?true:false;
		$this->_array_actionlist['read']=((int)$temp[0]==1?true:false);
		$this->_array_actionlist['add']=((int)$temp[1]==1?true:false);
		$this->_array_actionlist['edit']=((int)$temp[2]==1?true:false);
		$this->_array_actionlist['delete']=((int)$temp[3]==1?true:false);
	}
	
	public function __get($name){
		if(array_key_exists($name,$this->_array_actionlist)){
			return $this->_array_actionlist[$name];
		}else{
			return null;
		}
	}
	public function __set($name,$value){
		if(array_key_exists($name,$this->_array_actionlist)){
			$this->_array_actionlist[$name]=(bool)$value;
		}else{
			return null;
		}
	}
}
?>