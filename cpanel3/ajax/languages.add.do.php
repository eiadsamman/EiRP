<?php
	if(!isset($_POST['__method'],$_POST['__id'],$_POST['__lng-name'],$_POST['__lng-symbol'],$_POST['__lng-direction'],$_POST['__lng-icon'],$_POST['__lng-css'])){exit;}
	if(!in_array($_POST['__method'],array("add","edit"))){exit;}
	include_once "../include/header.php";
	$json_output=new CPANEL_JSON();
	
	$lng_id=($_POST['__method']=='add'?"NULL":(int)$_POST['__id']);
	$_POST['__lng-name']=$sql->escape(trim(addslashes($_POST['__lng-name'])));
	$_POST['__lng-icon']=$sql->escape(trim(addslashes($_POST['__lng-icon'])));$_POST['__lng-icon']=$_POST['__lng-icon']==""?"NULL":"'".$_POST['__lng-icon']."'";
	$_POST['__lng-symbol']=$sql->escape(trim(addslashes($_POST['__lng-symbol'])));$_POST['__lng-symbol']=$_POST['__lng-symbol']==""?"NULL":"'".$_POST['__lng-symbol']."'";
	$_POST['__lng-css']=$sql->escape(trim(addslashes($_POST['__lng-css'])));$_POST['__lng-css']=$_POST['__lng-css']==""?"NULL":"'".$_POST['__lng-css']."'";
	$_POST['__lng-direction']=(int)$_POST['__lng-direction'];
	$_POST['__lng-default']=isset($_POST['__lng-default'])?1:0;
	
	if($lng_id==0 && $_POST['__method']=="edit"){
		$json_output->output(false,"Invalid language ID");
	}
	if($_POST['__lng-name']==""){
		$json_output->output(false,"Language name is required");
	}
	if($_POST['__lng-default']==1){
		$sql->query("UPDATE languages SET lng_default=0");
	}	
	$q=sprintf("
		INSERT INTO languages (lng_id,lng_name,lng_symbol,lng_default,lng_direction,lng_icon,lng_css) 
		VALUES (%1\$d,%2\$s,%3\$s,%4\$d,%5\$d,%6\$s,%7\$s) 
		ON DUPLICATE KEY UPDATE lng_name=%2\$s,lng_symbol=%3\$s,lng_default=%4\$d,lng_direction=%5\$d,lng_icon=%6\$s,lng_css=%7\$s;",
		$lng_id,
		"'{$_POST['__lng-name']}'",
		"{$_POST['__lng-symbol']}",
		"{$_POST['__lng-default']}",
		"{$_POST['__lng-direction']}",
		"{$_POST['__lng-icon']}",
		"{$_POST['__lng-css']}"
	);
	$r=$sql->query($q);
	if($r){
		if($_POST['__method']=='edit'){
			$json_output->output(true,"Language edited successfully");
		}else{
			$json_output->output(true,"Language added successfully");
		}
		exit;
	}else{
		if($_POST['__method']=='edit'){
			$json_output->output(false,"Editing Language failed");
		}else{
			$json_output->output(false,"Adding new Language failed");
		}
		exit;
	}
?>