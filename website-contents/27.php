<?php
use System\Layout\Gremium;

if (isset($_POST['method']) && $_POST['method'] == 'changepassword') {
	$r = $app->db->query("SELECT usr_password FROM users WHERE usr_id={$app->user->info->id};");
	if ($r && $row = $r->fetch_assoc()) {
		if ($row['usr_password'] == $_POST['oldpass']) {
			if (strlen($_POST['newpass']) < 6) {
				echo "{\"result\":false,\"message\":\"New password length must be 6 or more\",\"focus\":\"newpass\"}";
			} else {
				if ($_POST['newpass'] != $_POST['conpass']) {
					echo "{\"result\":false,\"message\":\"Password confirmation does not match\",\"focus\":\"conpass\"}";
				} else {
					$pass = password_hash($_POST['newpass'], PASSWORD_BCRYPT, ["cost" => "12"]);
					if ($app->db->query(sprintf("UPDATE users SET usr_password='%s' WHERE usr_id={$app->user->info->id};", $pass))) {
						echo "{\"result\":true,\"message\":\"Password changed successfully\",\"focus\":\"oldpass\"}";
					} else {
						echo "{\"result\":false,\"message\":\"Unable to change password\",\"focus\":false}";
					}
				}
			}
		} else {
			echo "{\"result\":false,\"message\":\"Invalid password\",\"focus\":\"oldpass\"}";
		}
	} else {
		echo "{\"result\":false,\"message\":\"User not found\",\"focus\":false}";
	}
	exit;
}






echo <<<HTML
	<form action="{$fs()->dir}" method="post" id="passForm">
	<input type="hidden" name="method" value="changepassword" />
HTML;
$grem = new Gremium\Gremium(true);
$grem->header()->serve("<h1>My Account</h1>");
$grem->menu()->serve("<a href=\"{$fs(17)->dir}\">{$fs(17)->title}</a><a href=\"{$fs(263)->dir}\">{$fs(263)->title}</a></span>");
$grem->title()->serve("<span class=\"flex\">Account information</span>");
$grem->article()->maxWidth("600px")->serve(
	<<<HTML
	<div class="form">
		<label>
			<h1>Name</h1>
			<div class="btn-set">
				<input type="text" class="flex" tabindex="-1" value="{$app->user->info->firstname} {$app->user->info->lastname}" readonly="readonly" />
			</div>
		</label>
	</div>
	<div class="form">
		<label>
			<h1>Username</h1>
			<div class="btn-set">
				<input type="text" class="flex" tabindex="-1" value="{$app->user->info->username}" readonly="readonly" />
			</div>
		</label>
	</div>
	
	HTML
);

echo "<br /><br />";
$grem->title()->serve("<span class=\"flex\">Security management</span>");
$grem->article()->maxWidth("600px")->open();
echo <<<HTML
	<div class="form">
		<label>
			<h1>Current password</h1>
			<div class="btn-set">
				<input type="password" class="flex" name="oldpass" id="oldpass" />	
			</div>
		</label>
	</div>
	<div class="form">
		<label>
			<h1>New password</h1>
			<div class="btn-set">
				<input type="password" class="flex" name="newpass" id="newpass" />
			</div>
		</label>
	</div>
	<div class="form">
		<label>
			<h1>Password confirmation</h1>
			<div class="btn-set">
				<input type="password" class="flex" name="conpass" id="conpass" />
			</div>
		</label>
	</div>

	<div class="form">
		<label>
			<div class="btn-set">
				<button type="submit">Update Password</button>
			</div>
		</label>
	</div>
HTML;


$grem->getLast()->close();
$grem->terminate();
?>
</form>

<script>
	$(document).ready(function (e) {
		var ajax = null;
		$("#passForm").on('submit', function (e) {
			if ($("#newpass").val().length < 8) {
				e.preventDefault();
				messagesys.failure("At least 8 characters long but 10 or more is better.<br />A combination of uppercase letters, lowercase letters, numbers, and symbols.");
				$("#newpass").focus().select();
				return false;
			} else if ($("#conpass").val() != $("#newpass").val()) {
				e.preventDefault();
				messagesys.failure("Password confirmation does not match");
				$("#conpass").focus().select();
				return false;
			}
			e.preventDefault();
			if (ajax != null) {
				ajax.abort();
			}
			ajax = $.ajax({
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST',
				data: $("#passForm").serialize()
			}).done(function (data) {
				var _data = null
				try {
					_data = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Unknow JSON error");
					return false;
				}

				if (_data.result == true) {
					messagesys.success(_data.message);
					$("#oldpass").val("");
					$("#newpass").val("");
					$("#conpass").val("");

					if (_data.focus != false) {
						$("#" + _data.focus).focus().select();
					}
				} else {
					messagesys.failure(_data.message);
					if (_data.focus != false) {
						$("#" + _data.focus).focus().select();
					}
				}
			});
			return false;
		});


	});
</script>