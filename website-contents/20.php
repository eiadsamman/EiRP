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
	<form action="<?php echo $fs()->dir; ?>" method="post">
		<input type="hidden" name="login" />
		<?= (isset($_GET['refer']) ? "<input type=\"hidden\" name=\"refer\" value=\"" . urlencode($_GET['refer']) . "\" />" : ""); ?>

		<div style="max-width:500px;">
			<?php
			$grem = new Gremium\Gremium();

			$header = $grem->header();
			if ($access_error != 0) {
				$header->status(Gremium\Status::Exclamation);
			}
			$header->serve("<h1>Sign in!</h1>");

			($access_error == 2 ? $grem->legend()->serve("<span class=\"flexx\">Incorrect username or password</span>") : null);
			($access_error == 3 ? $grem->legend()->serve("<span class=\"flexx\">Inactive account</span>") : null);


			$grem->article()->open();

			$prev_username = $_POST['username'] ?? "";
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
				<div class="btn-set"><label style="border:none"><input type="checkbox" name="remember" /> Remember me</label></div>
				<div style="flex:1"></div>
				<div class="btn-set"><button style="width:100px;">Login</button></div>
			</div>

			HTML;
			$grem->getLast()->close();
			unset($grem);

			?>
	</form>
	<?php
} ?>