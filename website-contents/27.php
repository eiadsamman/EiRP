<?php
use System\Template\Gremium\Gremium;

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
					if ($app->db->query(sprintf("UPDATE users SET usr_password='%s' WHERE usr_id={$app->user->info->id};", $_POST['newpass']))) {
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
$r = $app->db->query("SELECT usr_regdate, usr_birthdate, usr_login_date FROM users WHERE usr_id={$app->user->info->id}");
if ($r && $row = $r->fetch_assoc()) {
	$curinfo = $row;
}

?>
<form action="<?php echo $fs()->dir; ?>" method="post" id="passForm">
	<input type="hidden" name="method" value="changepassword" />

	<?php
	$gremium = new Gremium(true);
	$gremium->header(true, null, null, "<h1>My Account</h1>");
	$gremium->menu(true, "<a class=\"\" href=\"{$fs(263)->dir}\">{$fs(263)->title}</a><span class=\"gap\"></span><button class=\"clr-green\" type=\"submit\">Update my settings</button>");
	$gremium->section(true);
	$gremium->sectionHeader("<span class=\"flex\">Account information</span>");
	$gremium->sectionArticle();
	echo "<table class=\"bom-table mediabond-table\" style=\"margin-bottom:20px;\">
		<tbody>
			<tr>
				<th>Name</th><td><div class=\"btn-set\"><input type=\"text\" value=\"" . $app->user->info->name . "\" class=\"flex\" readonly=\"readonly\" /></div></td>
			</tr>
			<tr>
				<th style=\"min-width:150px;\">Username</th><td style=\"width:100%;\"><div class=\"btn-set\"><input type=\"text\" value=\"" . $app->user->info->username . "\" class=\"flex\" readonly=\"readonly\" /></div></td>
			</tr>
			<tr>
				<th style=\"min-width:150px;\">Register date</th><td style=\"width:100%;\"><div class=\"btn-set\"><input type=\"text\" value=\"" . $curinfo['usr_regdate'] . "\" class=\"flex\" readonly=\"readonly\" /></div></td>
			</tr>
			</tbody>
		</table>";
	$gremium->sectionArticle();
	$gremium->sectionHeader("<span class=\"flex\">Security management</span>");
	$gremium->sectionArticle();
	?>
	<table class="bom-table mediabond-table">
		<tbody>
			<tr>
				<th style="min-width:150px;">Current Password</th>
				<td style="width:100%;">
					<div class="btn-set"><input type="password" name="oldpass" id="oldpass" class="flex" /></div>
				</td>
			</tr>
			<tr>
				<th>New Password</th>
				<td>
					<div class="btn-set"><input type="password" name="newpass" id="newpass" class="flex" /></div>
				</td>
			</tr>
			<tr>
				<th>Retype new password</th>
				<td>
					<div class="btn-set"><input type="password" name="conpass" id="conpass" class="flex" /></div>
				</td>
			</tr>
		</tbody>
	</table>
</form>
<?php
$gremium->sectionArticle();

$gremium->section();
unset($gremium);


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