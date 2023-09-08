<?php 
	if(!isset($_POST['ln_id'])){exit;}
	include_once "../include/header.php";
	
	$_POST['ln_id']=(int)$_POST['ln_id'];
	$sql->autocommit(false);
	$r=true;
	if($r)$r&=$sql->query("DELETE languages,pagefile_language FROM languages LEFT JOIN pagefile_language ON pfl_lng_id = lng_id  WHERE lng_id={$_POST['ln_id']}");
	
	if($r){
		$sql->commit();
		echo "1";
		exit;
	}else{
		$sql->rollback();
		echo "0";
		exit;
	}
?>