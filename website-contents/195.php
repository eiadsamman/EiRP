<!DOCTYPE html>
<html>
<head>
	<title>Machines label printer</title>
	<style type="text/css" media="print,screen">
		@page {
			size:80mm 40mm landsacpe !important;
			margin:0;
		}
		@media print{
			@page {
				size:80mm 40mm landsacpe !important;
				margin:0;
				padding: 0;
			}
			html,body{
				width: 80mm !important;
				height: 40mm !important;
				margin: 0;
				padding: 0;
			}
		}
		@media screen{
			html,body{
				width: 80mm;
				height: 40mm;
				margin: 0;
				padding: 0;
			}
		}
		.lbl{
			display: block;
			font-family: verdana;
			width:80mm;
			height: 42mm;
			margin: 0;
			text-align: center;
			vertical-align: middle;
			padding-top: 18mm;
			font-size: 1.7cm;
			page-break-after: always;
			background-image: url("/eiad/EE-logo.png");
			background-position: 15px 175px;
			background-size: auto 40px;
			background-repeat: no-repeat;
			position: relative;
		}
		.lbl:before{
			display: block;
			position: absolute;
			content: " ";
			border:solid 1px #000;
			top:5px;
			left:5px;
			bottom:5px;
			right:5px;
			border-radius: 6px;
		}
	</style>
</head>
<body><?php

if(!isset($_POST['data'])){exit;}

$r=explode("\r\n",$_POST['data']);
foreach($r as $v){
	if(trim($v)==""){continue;}
	echo "<div class=\"lbl\">$v</div>";
}


?></body>
</html>