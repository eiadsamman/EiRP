<?php
if (isset($_POST['method']) && $_POST['method'] == 'changepassword') {
	$r = $sql->query("SELECT usr_password FROM users WHERE usr_id={$USER->info->id};");
	if ($r) {
		if ($row = $sql->fetch_assoc($r)) {
			if ($row['usr_password'] == $_POST['oldpass']) {
				if (strlen($_POST['newpass']) < 6) {
					echo "{\"result\":false,\"message\":\"New password length must be 6 or more\",\"focus\":\"newpass\"}";
				} else {
					if ($_POST['newpass'] != $_POST['conpass']) {
						echo "{\"result\":false,\"message\":\"Password confirmation does not match\",\"focus\":\"conpass\"}";
					} else {
						if ($sql->query(sprintf("UPDATE users SET usr_password='%s' WHERE usr_id={$USER->info->id};", $_POST['newpass']))) {
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
	} else {
		echo "{\"result\":false,\"message\":\"User not found\",\"focus\":false}";
	}
	exit;
}


$curinfo = false;
$r = $sql->query("SELECT usr_regdate, usr_birthdate, usr_login_date FROM users WHERE usr_id={$USER->info->id}");
if ($r && $row = $sql->fetch_assoc($r)) {
	$curinfo = $row;
}

require_once("admin/class/Template/class.template.build.php");

use Template\TemplateBuild;

$_TEMPLATE 	= new TemplateBuild();
$_TEMPLATE->SetWidth("800px");
$_TEMPLATE->Title("My Account", null, null);


echo $_TEMPLATE->CommandBarStart();
echo "<div class=\"btn-set\">";
echo "<a class=\"as-button\" href=\"{$fs(263)->dir}\">{$fs(263)->title}</a>";
echo "</div>";
echo $_TEMPLATE->CommandBarEnd();



$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Account information</span>", false, true);
echo $_TEMPLATE->NewFrameBodyStart();
echo "<table class=\"bom-table\">
		<tbody>
			
			<tr>
				<th>Name</th><td><div class=\"btn-set\"><input type=\"text\" value=\"" . $USER->info->name . "\" class=\"flex\" readonly=\"readonly\" /></div></td>
			</tr>
			<tr>
				<th style=\"min-width:150px;\">Username</th><td style=\"width:100%;\"><div class=\"btn-set\"><input type=\"text\" value=\"" . $USER->info->username . "\" class=\"flex\" readonly=\"readonly\" /></div></td>
			</tr>
			
			<tr>
				<th style=\"min-width:150px;\">Register date</th><td style=\"width:100%;\"><div class=\"btn-set\"><input type=\"text\" value=\"" . $curinfo['usr_regdate'] . "\" class=\"flex\" readonly=\"readonly\" /></div></td>
			</tr>
			
			</tbody>
		</table>";
echo $_TEMPLATE->NewFrameBodyEnd();


$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Security management</span>", false, true);
echo $_TEMPLATE->NewFrameBodyStart();
?>
<form action="<?php echo $pageinfo['directory']; ?>" method="post" id="passForm">
	<input type="hidden" name="method" value="changepassword" />
	<table class="bom-table">
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
	<div class="btn-set" style="flex-direction: row-reverse;padding:10px;"><button>Update my settings</button></div>
</form>
<?php
echo $_TEMPLATE->NewFrameBodyEnd();
?>

<script>
	$(document).ready(function(e) {
		var ajax = null;
		$("#passForm").on('submit', function(e) {
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
				url: '<?php echo $pageinfo['directory']; ?>',
				type: 'POST',
				data: $("#passForm").serialize()
			}).done(function(data) {
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