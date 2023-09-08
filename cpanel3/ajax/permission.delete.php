<?php 
	if(!isset($_POST['pr_id'])){exit;}
	include_once "../include/header.php";
	
	$_POST['pr_id']=(int)$_POST['pr_id'];
	$sql->autocommit(false);
	$r=true;
	if($r)$r&=$sql->query("DELETE FROM permissions WHERE per_id={$_POST['pr_id']};");
	if($r)$r&=$sql->query("DELETE FROM pagefile_permissions WHERE pfp_per_id={$_POST['pr_id']};");
	
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