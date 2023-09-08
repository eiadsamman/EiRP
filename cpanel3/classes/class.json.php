<?php
class CPANEL_JSON {
	public function output($result,$message,$focus=null,$extra=null){
		echo "{";
		echo "\"result\":".($result==true?"true":"false")."";
		echo ",\"message\":\"".addslashes($message)."\"";
		echo $focus!=null?",\"focus\":\"$focus\"":",\"focus\":false";
		if($extra !=null && is_array($extra)){
			foreach($extra as $k=>$v){echo ",\"$k\":\"$v\"";}
		}
		echo "}";
		exit;
	}
}
?>