<?php if(!defined("GLOBALS")){die();}?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<base href="<?php echo "{$_SERVER['HTTP_SYSTEM_ROOT']}cpanel3/";?>" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta charset="utf-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $c__settings['site']['title'];?> - Control Panel</title>
	<link rel="stylesheet" type="text/css" media="screen" href="static/style/cpanel.style.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="static/style/cpanel.button-set.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="static/style/cpanel.bom-table.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="static/style/cpanel.popup.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="static/style/cpanel.messagesys.css" />
	
	<script type="text/javascript" src="static/jquery/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" src="static/jquery/easing.js"></script>
	<script type="text/javascript" src="static/jquery/jquery-ui.min.js"></script>
	<script type="text/javascript" src="static/jquery/msgsys-2.0.js"></script>
	<script type="text/javascript" src="static/jquery/popup-2.0.js"></script>
	
</head>
<body>
<?php if(isset($exclude_body) && $exclude_body===true){}else{?>
<div class="cpanel_bheader">
	<div></div>
	<div class="hover">
		<span><span style="font-family:theta;font-weight:normal;display:inline-block;margin-right:10px;position:relative;top:1px">&#xe648;</span>Menu</span>
		<div>
			<a href="m_pagefile/">Pagefiles</a>
			<a href="m_permissions/">Permissions</a>
			<a href="m_languages/">Languages</a>
			<span></span>
			<a href="m_settings/">Settings</a>
			<a href="m_update/">Update</a>
			<span></span>
			<a href="<?php echo dirname($_SERVER['PHP_SELF']);?>/?logout">Logout</a>
		</div>
	</div>
	<div class="name">
		<span>CPanel3 Management</span>
	</div>
	<div></div>
</div>
<div style="padding:50px 20px 20px 30px;text-align:left">
<?php }?>