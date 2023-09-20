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

			$grem->article()->serve('
			<div class="template-gridLayout role-input">
				<div class="btn-set vertical" style="min-width:100%;"><span>Username</span><input type="text" autofocus class="flex" list="browsers" autocomplete="off" name="log_username" id="username" value="' . (isset($_POST['username']) ? $_POST['username'] : "") . '" /></div>
				<div class="btn-set vertical" style="min-width:100%;"><span>Password</span><input type="password" class="flex" class="password" name="log_password" autocomplete="off" value="" /></div>
				<div><label><input type="checkbox" name="remember" /> Remember my login</label></div>
			</div>
			<div class="template-gridLayout role-input">
				<div class="btn-set vertical"><div class="btn-set"><button style="width:200px;">Login</button></div>
			</div>');
			unset($grem);

			?>
	</form>
	<?php
} ?>