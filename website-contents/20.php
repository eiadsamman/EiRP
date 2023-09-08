<?php
	include_once("admin/class/Template/class.template.build.php");
	use Template\TemplateBuild;
	$_TEMPLATE = new TemplateBuild();
	$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/true);
	$_TEMPLATE->FrameTitlesStack(false);
	$_TEMPLATE->SetWidth("600px");
	
	if($access_error == 403){
		$_TEMPLATE->Title("&nbsp;Forbidden!", null, "","mark-error");
		$_TEMPLATE->NewFrameBody("
			<br />You don't have permission to access '<i>{$pageinfo['directory']}</i>' on this server!<br /><br />
			Contact system administrator, or <a href=\"{$tables->pagefile_info(20,null,'directory')}/?refer={$pageinfo['directory']}\">sign in</a> with an authorized account and try again
			");
		die();
	}else{
		?>
			<form action="<?php echo $pageinfo['directory'];?>" method="post">
				<?= (isset($_GET['refer'])?"<input type=\"hidden\" name=\"refer\" value=\"".urlencode($_GET['refer'])."\" />":"");?>
				
				<input type="hidden" name="login" />
				<?php 
					$_TEMPLATE->Title("Sign in", null, "");
					($access_error == 2 ? $_TEMPLATE->NewFrameTitle("<span class=\"flex\" style=\"color:#f03;\">Incorrect username or password</span>",500) : null);
					($access_error == 3 ? $_TEMPLATE->NewFrameTitle("<span class=\"flex\" style=\"color:#f03;\">Inactive account</span>",500) : null);

					echo $_TEMPLATE->NewFrameBodyStart();
					echo '
						<div class="template-gridLayout role-input">
							<div class="btn-set vertical" style="min-width:100%"><span>Username</span><input type="text" autofocus class="flex" list="browsers" autocomplete="off" name="log_username" id="username" value="'.(isset($_POST['username'])?$_POST['username']:"").'" /></div>
							<div class="btn-set vertical" style="min-width:100%"><span>Password</span><input type="password" class="flex" class="password" name="log_password" autocomplete="off" value="" /></div>
							<div><label><input type="checkbox" name="remember" /> Remember my login</label></div>
						</div>
						<div class="template-gridLayout role-input">
							<div class="btn-set vertical"><div class="btn-set"><button class="flex" style="width:250px" >Login</button></div>
						</div>';
					echo $_TEMPLATE->NewFrameBodyEnd();
				?>
			</form>
		<?php
	}?>