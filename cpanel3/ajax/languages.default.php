<?php
include_once "../include/header.php";
if(isset($_POST['ln_id'])){
	$id=(int)$_POST['ln_id'];
	if($sql->query("UPDATE languages SET lng_default=IF(lng_id={$_POST['ln_id']},1,0)")){
		echo "1";
	}else{
		echo "0";
	}
}
?>