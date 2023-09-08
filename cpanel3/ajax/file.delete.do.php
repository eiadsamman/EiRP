<?php 

	include_once "../include/header.php";
	
	if(!isset($_POST['pf_id'])){exit;}
	$json_output=new CPANEL_JSON();
	
	$cascade_delete	=isset($_POST['file_delete_method'])?true:false;
	$_fd_id			=(int)$_POST['pf_id'];
	$fd_id			=null;
	$fd_parent		=null;
	$fd_parent_dir	=null;

	if($r=$sql->query("SELECT trd_id,trd_parent,trd_directory FROM pagefile WHERE trd_id='$_fd_id';")){
		if($row=$sql->fetch_assoc($r)){
			$fd_id		=$row['trd_id'];
			$fd_parent	=$row['trd_parent'];
		}
	}
	if($fd_parent==0){
		$fd_parent_dir="";
	}else{
		$r=$sql->query("SELECT trd_directory FROM pagefile WHERE trd_id=$fd_parent;");
		if($r && $row=$sql->fetch_assoc($r)){
			$fd_parent_dir=$row['trd_directory'];
		}
	}
	
	if($fd_id==null)$json_output->output(false,"Invalid page directory");

	$sql->autocommit(false);
	
	function delete_cascade($sql,$id,$preres=true){
		$r=$preres;
		$result=$sql->query("SELECT trd_id,trd_directory FROM pagefile WHERE trd_parent='$id';");
		if($result){
			while($row=$sql->fetch_assoc($result)){
				
				$r&=$sql->query("DELETE FROM pagefile WHERE trd_id='{$row['trd_id']}';");
				$r&=$sql->query("DELETE FROM pagefile_language WHERE pfl_trd_id='{$row['trd_id']}';");
				$r&=$sql->query("DELETE FROM pagefile_permissions WHERE pfp_trd_id='{$row['trd_id']}';");
				if(!$r){
					return false;
				}else{
					delete_cascade($sql,$row['trd_id'],$preres);
				}
			}
			$sql->free_result($result);
		}
		return $r;
	}

	$result=true;
	if($cascade_delete){
		$result=delete_cascade($sql,$fd_id);
	}else{
		$result=$sql->query("UPDATE pagefile SET trd_parent='$fd_parent' WHERE trd_parent='$fd_id';");
	}
	
	if(!$result){
		$sql->rollback();
		$json_output->output(false,"Deleting page directory children failed");
	}
	
	$result&=$sql->query("DELETE FROM pagefile WHERE trd_id='$fd_id';");
	$result&=$sql->query("DELETE FROM pagefile_language WHERE pfl_trd_id='$fd_id';");
	$result&=$sql->query("DELETE FROM pagefile_permissions WHERE pfp_trd_id='$fd_id';");
	if(!$result){
		$sql->rollback();
		$json_output->output(false,"Deleting page directory failed");
		exit;
	}else{
		$sql->commit();
		$json_output->output(true,"Page directory delete successfully",null,
			array(
				"directory"=>$fd_parent_dir,
				"line"=>(isset($_POST['line']) && $_POST['line']!="0"?"$fd_id":"0")
				)
			);
		exit;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
?>