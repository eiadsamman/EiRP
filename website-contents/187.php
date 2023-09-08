<?php
set_time_limit(60*2);
$_GET['id']=(int)$_GET['id'];

$accepted_mimes=array("image/jpeg","image/gif","image/bmp","image/png");

$file=false;
if($r=$sql->query(sprintf("
	SELECT 
		up_id,up_name,up_size,pfp_value,up_mime
	FROM 
		uploads
			JOIN pagefile_permissions ON pfp_trd_id=up_pagefile 
	WHERE 
		up_id='%d';",$_GET['id']))){
	if($row=$sql->fetch_assoc($r)){
		if((int)$row['pfp_value']<=0){
			header(HTTP403);exit;
		}else{
			$file=array($row['up_id'],$row['up_name'],$row['up_size'],$row['up_mime']);
		}
	}
}

if(in_array($file[3], $accepted_mimes)){
	$_GET['pr']=(isset($_GET['pr']) && in_array($_GET['pr'], array("v","t"))?"_".$_GET['pr']:false);
	$file_dir=$_SERVER['FILE_SYSTEM_ROOT'] . "uploads" . DIRECTORY_SEPARATOR . $file[0] . $_GET['pr'];
}else{
	$file_dir=$_SERVER['FILE_SYSTEM_ROOT'] . "uploads" . DIRECTORY_SEPARATOR . $file[0];
}


if(!$file || !is_file($file_dir)){
	header(HTTP404);exit;
}


header("Accept-Ranges: bytes");
header("Content-Transfer-Encoding: binary");
header("Content-Type: {$file[3]}");

header("Cache-Control: max-age=".(60*60*24*30));
header("Cache-Control: public");
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60*60*24*30) . ' GMT');
header("Pragma: cache");


header("Content-Description: File Transfer");
header("Content-disposition: inline; filename={$file[1]}");
header('Content-Length: '.filesize($file_dir));
//header("Content-Disposition:attachment;filename='{$file[1]}'");

flush();
readfile($file_dir);

exit;
?>