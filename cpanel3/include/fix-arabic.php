<?php
function replaceARABIC($str){
	$str=str_replace(array("أ","إ","آ","ا"),"[أإاآ]+",$str);
	$str=str_replace(array("ة","ه"),"[ةه]+",$str);
	$str=str_replace(array("ؤ","و"),"[وؤ]+",$str);
	return $str;
}
?>