<style type="text/css">
	.uploads-image-list{
		white-space:normal !important ;
		min-width: 800px;
	}
	.uploads-image-list > a{
		display: inline-block;
		border:solid 1px #fff;
		margin: 2px;
		width:205px;
		background-repeat: no-repeat;
		background-position: 5px 5px;
		background-size: 193px 193px;
		padding-top: 210px;
		padding-bottom: 10px;
		color:#333;
		
	}
	.uploads-image-list > a:hover{
		background-color:#e5f3ff;
		text-decoration: none;
		border:solid 1px #99d1ff;
	}
	.uploads-image-list > a > span{
		display: block;
		text-align: center;
		white-space: nowrap;
		overflow-x: hidden;
		text-overflow: ellipsis;
		padding:0px 15px;
	}
	.uploads-image-list > a > span.acc-imglist-d{
		font-size:0.9em;
		margin-top: 5px;
		color:#999;
	}
</style>
<div>
<?php
$statement_id=(int)$_POST['statement_id'];
$accepted_mimes=array("image/jpeg","image/gif","image/bmp","image/png");

$r=$sql->query("SELECT up_user,up_id,up_size,UNIX_TIMESTAMP(up_date) AS up_date,up_mime,up_name FROM uploads WHERE up_rel={$statement_id} AND up_pagefile=188 AND up_active=1 AND up_deleted=0;");
echo "<table class=\"bom-table\"><thead><tr class=\"special\"><td width=\"100%\">Displaying uploads for statement {$statement_id}</td><td>(".$sql->num_rows($r).") files</td></tr></thead><tbody>
		<tr><td colspan=\"2\" class=\"uploads-image-list\">
";

if($r){
	while($row=$sql->fetch_assoc($r)){
		$row['up_date']=date("Y-m-d", $row['up_date']);
		$row['up_size'] = number_format($row['up_size'] / 1024 / 1024,2,".",",")."MB";
		if(in_array($row['up_mime'], $accepted_mimes)){
			echo "<a href=\"download/?id={$row['up_id']}&pr=v\" target=\"_blank\" style=\"background-image:url('download/?id={$row['up_id']}&pr=t');\">";
		}else{
			echo "<a href=\"download/?id={$row['up_id']}\" target=\"_blank\" style=\"background-image:url('static/images/document.png');\">";
		}
		echo "<span>{$row['up_name']}</span><span class=\"acc-imglist-d\">{$row['up_size']} / {$row['up_date']}</span></a>";
	}

}
echo "</td></tr></div></tbody></table>";

?>
</div>