<?php 
if(!defined("GLOBALS")){exit();}

$err="";
if(isset($_GET['logout'])){
	unset($_SESSION[md5("h@shinGl0g!n{$_SERVER['REMOTE_ADDR']}")]);
	header("location:"."{$_SERVER['HTTP_SYSTEM_ROOT']}/cpanel3");
}
if(isset($_POST['cpanel_username'],$_POST['cpanel_password'])){
	if($_POST['cpanel_username']==$c__settings['cpanel']['username'] && $_POST['cpanel_password']==$c__settings['cpanel']['password']){
		$_SESSION[md5("h@shinGl0g!n{$_SERVER['REMOTE_ADDR']}")]=true;
		header("location:"."{$_SERVER['HTTP_SYSTEM_ROOT']}/cpanel3");
		exit;
	}else{
		$err.="<tr><td colspan=\"2\" align=\"left\"><span style=\"color:#f03\" nowrap=\"nowrap\">Either username or password is incorrect</span></td></tr>";
	}
}

if(!isset($_SESSION[md5("h@shinGl0g!n{$_SERVER['REMOTE_ADDR']}")])){
	$exclude_body=true;
	include_once "html.header.php";
	?>
	<form action="" method="post">
	<div class="hvalign"><i></i><span>
		<div>
			<table class="bom-table" style="width:350px">
			<thead><tr><td style="padding:13px 10px;font-size:1.2em;background-color:#06c;color:#fff;text-align:center;cursor:default"><?php echo $c__settings['site']['title'];?> - Control Panel Login</h1></td></td></thead><tbody>
			
			<tr><td>
				<div class="btn-set" style="margin-bottom:8px;">
					<span style="min-width:100px;">Username</span>
					<input type="text" name="cpanel_username" autocomplete="off" id="cpanel_username" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" value="<?php echo array_key_exists('cpanel_username',$_POST)? $_POST['cpanel_username']:"";?>" />
				</div>
				<div class="btn-set">
					<span style="min-width:100px;">Password</span>
					<input type="password" autocomplete="off" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" name="cpanel_password" />
				</div>
			</td></tr>
			<?php echo $err;?>
			<tr><td align="center"><div class="btn-set" style="-webkit-justify-content:center;justify-content: center;">
			<input style="padding-right:30px;padding-left:30px;" type="submit" value="Login" id="submit_btn" /></div></td></tr>
			<tr><td align="left" style="font-size:9px;color:#999">All right reserved - &Theta;|Theta&trade;</td></tr>
			</tbody></table>
		</div>
	</span></div>
	</form>
	<script>
	$(document).ready(function(e) {
		$("#cpanel_username").focus();
	});
	</script>
	<?php 
	include_once("html.footer.php");
	exit;
}
?>