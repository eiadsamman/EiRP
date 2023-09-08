<?php 
include_once "../include/header.php";
$id=(int)$_POST['id'];
if($sql->query("UPDATE pagefile SET trd_visible=if(trd_visible = 1,0,1) WHERE trd_id='$id';")){
	if($r=$sql->query("SELECT trd_visible FROM pagefile WHERE trd_id='$id';")){
		if($row=$sql->fetch_assoc($r)){
			echo $row['trd_visible'];
		}
	}else{
		echo "3";
	}
}else{
	echo "3";
}
?>