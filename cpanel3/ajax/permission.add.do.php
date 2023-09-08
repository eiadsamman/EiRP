<?php 
	if(!isset($_POST['__method'],$_POST['__per-title'],$_POST['__per-description'])){exit;}
	include_once "../include/header.php";
	$json_output=new CPANEL_JSON();
	
	$per_id=($_POST['__method']=='add'?"NULL":(int)$_POST['__id']);
	$_POST['__per-title']=$sql->escape(addslashes($_POST['__per-title']));
	$_POST['__per-description']=$sql->escape(addslashes($_POST['__per-description']));
	$_POST['__per-order']=(int) $_POST['__per-order'];
	
	
	$q=sprintf("
		insert into permissions (per_id,per_title,per_description,per_order) values(%1\$s,%2\$s,%3\$s,%4\$d) 
		ON DUPLICATE KEY UPDATE per_title = %2\$s, per_description = %3\$s, per_order = %4\$d;",
		$per_id,
		"'{$_POST['__per-title']}'",
		"'{$_POST['__per-description']}'",
		$_POST['__per-order']
	);
	$r=$sql->query($q);
	
	if($r){
		if($_POST['__method']=='edit'){
			$json_output->output(true,"Permission edited successfully");
		}else{
			$json_output->output(true,"Permission added successfully");
		}
		exit;
	}else{
		if($_POST['__method']=='edit'){
			$json_output->output(false,"Editing permission failed");
		}else{
			$json_output->output(false,"Adding new permission failed");
		}
		exit;
	}
?>