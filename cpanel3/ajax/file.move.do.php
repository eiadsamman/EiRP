<?php
	include_once "../include/header.php";
	if(!isset($_POST['new_pf_id'],$_POST['pf_id'],$_POST['line'])){exit;}
	$json_output=new CPANEL_JSON();
	
	$_POST['new_pf_id']	=(int)$_POST['new_pf_id'];
	$_POST['pf_id']		=(int)$_POST['pf_id'];
	$_POST['line']		=(int)$_POST['line'];
	
	$validate=array();
	if($r=$sql->query("SELECT trd_id,trd_directory,trd_parent FROM pagefile WHERE trd_id IN({$_POST['new_pf_id']},{$_POST['pf_id']});")){
		while($row=$sql->fetch_assoc($r)){
			$validate[$row['trd_id']]=array($row['trd_directory'],$row['trd_parent']);
		}
	}
	if(!isset($validate[$_POST['pf_id']])){$json_output->output(false,"Invalid source pagefile, pagefile might be moved or deleted");}
	if(!isset($validate[$_POST['new_pf_id']]) && $_POST['new_pf_id']!=0){$json_output->output(false,"Invalid destination pagefile, pagefile might be moved or deleted");}
	if($validate[$_POST['pf_id']][1]==$_POST['new_pf_id']){$json_output->output(false,"Destination pagefile is same as the current pagefile location");}
	
	$location=new Location();
	$location->location_build($_POST['new_pf_id'],true,true,"trd_id",1);
	if(array_key_exists($_POST['pf_id'],$location->location_trace())){
		$json_output->output(false,"Destination pagefile is a child of the source pagefile");
	}else{
		if($r=$sql->query("UPDATE pagefile SET trd_parent={$_POST['new_pf_id']} WHERE trd_id={$_POST['pf_id']};")){
			$json_output->output(true,"Pagefile moved successfully",null,
			array(
				"directory"=>$validate[$_POST['pf_id']][0],
				"line"=>$_POST['line']!=0?$_POST['pf_id']:0
				)
			);
		}else{
			$json_output->output(false,"Moveing pagefile failed");
		}
	}
?>