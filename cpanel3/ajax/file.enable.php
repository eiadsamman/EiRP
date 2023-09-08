<?php 
include_once "../include/header.php";
$id=(int)$_POST['id'];
if($sql->query("UPDATE pagefile SET trd_enable=if(trd_enable = 1,0,1) WHERE trd_id='$id';")){
	if($r=$sql->query("SELECT trd_enable FROM pagefile WHERE trd_id='$id';")){
		if($row=$sql->fetch_assoc($r)){
			echo $row['trd_enable'];
		}
	}else{
		echo "3";
	}
}else{
	echo "3";
}
?>