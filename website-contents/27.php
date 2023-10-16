<?php
use System\Template\Gremium;

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



$curinfo = false;
$r       = $app->db->query("SELECT usr_regdate, usr_birthdate, usr_login_date FROM users WHERE usr_id={$app->user->info->id}");
if ($r && $row = $r->fetch_assoc()) {
	$curinfo = $row;
}

?>
<form action="<?php echo $fs()->dir; ?>" method="post" id="passForm">
	<input type="hidden" name="method" value="changepassword" />

	<?php
	$grem = new Gremium\Gremium(true);

	$grem->header()->serve("<h1>My Account</h1>");
	$grem->menu()->serve("<a href=\"{$fs(17)->dir}\">{$fs(17)->title}</a><a href=\"{$fs(263)->dir}\">{$fs(263)->title}</a><span class=\"gap\"></span>");
	$grem->title()->serve("<span class=\"flex\">Account information</span>");

	$grem->article()->serve('
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical" ><span>Name</span><input type="text" tabindex="-1" value="' . $app->user->info->name . '" readonly="readonly" /></div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical" ><span>Username</span><input type="text" tabindex="-1" value="' . $app->user->info->username . '" readonly="readonly" /></div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical" ><span>Register date</span><input type="text" tabindex="-1" value="' . $curinfo['usr_regdate'] . '" readonly="readonly" /></div>
	</div>
	<br /><br /><br />
	');


	$grem->title()->serve("<span class=\"flex\">Security management</span>");
	$grem->article()->open();
	?>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Current password</span><input type="password" name="oldpass" id="oldpass" />
		</div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>New password</span><input type="password" name="newpass" id="newpass" />
		</div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Password confirmation</span><input type="password" name="conpass" id="conpass" />
		</div>
	</div>
	<div class="template-gridLayout role-input">

		<div class="btn-set" style="justify-content:end"><button type="submit">Update Password</button></div>
	</div>
</form>

<?php
$grem->getLast()->close();
unset($grem);
?>
<script>
	$(document).ready(function (e) {
		var ajax = null;
		$("#passForm").on('submit', function (e) {
			if ($("#newpass").val().length < 6) {
				e.preventDefault();
				messagesys.failure("Minumum of 6 characters password is required");
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