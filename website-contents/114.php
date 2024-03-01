<?php


/* Ecrypt users passwords */
$res = $app->db->query("SELECT usr_password, usr_id FROM users;");
$newpass = $userid = 0;
$stmt = $app->db->prepare("UPDATE users SET usr_password = ? WHERE usr_id = ?");
if ($res) {
	while ($row = $res->fetch_assoc()) {
		if (!is_null($row['usr_password']) && strlen($row['usr_password']) > 1) {
			$newpass = password_hash($row['usr_password'], PASSWORD_BCRYPT, ["cost" => "12"]);
			$userid = $row['usr_id'];
			$stmt->bind_param("si", $newpass, $userid);
			$stmt->execute();
		}
	}
}
$stmt->close();





exit;
use System\SmartListObject;

$SmartListObject = new SmartListObject($app);
?>
<div draggable="true" style="padding:20px;border:solid 2px red;display:inline-block;">ASF</div>
<div class="btn-set page" style="margin-top:50px;margin-left:10px">
	<input type="text" />
	<input type="button" value="Button" />
	<button>Test</button>

	<input type="text" class="list" data-slo=":SELECT" data-list="zlist" />
	<input type="text" class="list" data-slo="ACC_REFERENCE" />
	<!-- <input type="text" id="date" data-slo=":DATE" value="2023-10-01" data-rangeend="2030-12-31" />
	<input type="text" id="number" data-slo=":NUMBER" data-rangestart="-11" value="55" data-rangeend="100" /> -->
	<button id="fucK">Test</button>

</div>

<datalist id="zlist">
	<?= $SmartListObject->financialTransactionNature(); ?>
</datalist>


<script type="text/javascript">
	$(document).ready(function (e) {
		const slos = $(".page [data-slo]").slo();
		slos.clear();
		slos.each(function (e) {
			if (this.id == "date") {
				this.slo.events.onselect = function (e) {
					console.log(e);
				}
			}
		});

		//list.set(1058);			/* fucK.addEventListener('click', function (e) {				console.log(list.first()[0].stamped)   			}); */

	});
</script>