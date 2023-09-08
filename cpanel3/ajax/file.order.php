<?php
	include_once "../include/header.php";
	if(!isset($_POST['order'])){exit;}
	$r=true;
	$sql->autocommit(false);
	foreach ($_POST['order'] as $position => $item){
		$r&=$sql->query("UPDATE pagefile SET trd_zorder='$position' WHERE trd_id='$item';");
		if(!$r){break;}
	}
	if($r){
		$sql->commit();
		echo "1";
	}else{
		$sql->rollback();
		echo "0";
	}
?>