<?php
use System\Template\Gremium;

if ($access_error == 403) {
	$grem = new Gremium\Gremium();
	$grem->header()->status(Gremium\Status::XMark)->serve("<h1>Forbidden!</h1>");
	$grem->article()->serve("
		<br />You don't have permission to access '<i>{$fs()->dir}</i>' on this server!<br /><br />
		Contact system administrator, or <a href=\"{$fs(20)->dir}/?refer={$fs()->dir}\">sign in</a> with an authorized account and try again
	");
	unset($grem);
	exit;
} else {
	?>
	<style>
		#login-form {
			display: flex;
			justify-content: center;
			height: 100%;
			align-items: center;
		}

		#login-form>div {
			max-width: 500px;
			text-align: left;
			position: relative;
		}

		#login-form>div::before {
			position: absolute;
			display: block;
			content: " ";
			background-image: url("./static/images/logo.svg");
			width: 50px;
			height: 50px;
			z-index: 99;
			right: 30px;
			top: 20px;
		}

		#login-form .error {
			color: red;
			padding-bottom: 20px;
		}
	</style>
	<form action="<?php echo $fs()->dir; ?>" method="post" id="login-form">
		<div>
			<input type="hidden" name="login" />
			<?= (isset($_GET['refer']) ? "<input type=\"hidden\" name=\"refer\" value=\"" . urlencode($_GET['refer']) . "\" />" : ""); ?>
			<div style="max-width:500px;">
				<?php
				$grem   = new Gremium\Gremium();
				$header = $grem->header();
				$grem->article()->open();
				$prev_username = $_POST['username'] ?? "";
				echo "<h1>Login</h1>";
				echo ($access_error == 2 ? "<div class=\"error\">Incorrect username or password</div>" : null);
				echo ($access_error == 3 ? "<div class=\"error\">Inactive account</div>" : null);
				echo <<<HTML
					<div class="form">
						<label style="min-width:150px">
							<h1>Username</h1>
							<div class="btn-set">
							<input type="text" autofocus class="flex" list="browsers" autocomplete="off" name="log_username" id="username" value="{$prev_username}" />
							</div>
						</label>
						<label style="min-width:300px; flex: 1 1 100%">
							<h1>Password</h1>
							<div class="btn-set">
							<input type="password" class="flex" class="password" name="log_password" autocomplete="off" value="" />
							</div>
						</label>
						<div class="btn-set"><label><input type="checkbox" name="remember" /><span> Remember me</span></label></div>
						<div style="flex:1"></div>
						<div class="btn-set"><button style="width:100px;">Login</button></div>
					</div>
					HTML;
				$grem->getLast()->close();
				unset($grem);
				?>
			</div>
		</div>
	</form>
	<?php
} ?>