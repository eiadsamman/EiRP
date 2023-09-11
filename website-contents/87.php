<?php

if (isset($_POST['request']) && $_POST['request'] == "update") {
	if (is_null($app->currency)) {
		header("HTTP_X_RESPONSE: SYSTEM");
		exit;
	}
	foreach ($_POST['verb-exchange-sell'] as $cur_id => $cur_value) {
		if ((float)$cur_value == 0) {
			header("HTTP_X_RESPONSE: ZERO");
			exit;
		}
	}

	$app->db->autocommit(false);
	$r = true;
	$r &= $app->db->query("TRUNCATE currency_exchange;");
	$r &= $app->db->query("INSERT INTO currency_exchange (curexg_from,curexg_to,curexg_value) VALUES ({$app->currency->id} , {$app->currency->id} , 1)  ON DUPLICATE KEY UPDATE curexg_value=1;");
	if ($r) {
		foreach ($_POST['verb-exchange-sell'] as $cur_id => $cur_value) {
			$cur_value = (float)$cur_value;
			echo $cur_value;
			$r &= $app->db->query("INSERT INTO currency_exchange (curexg_from,curexg_to,curexg_value) VALUES ({$cur_id} , {$app->currency->id} , {$cur_value})  ON DUPLICATE KEY UPDATE curexg_value={$cur_value};");
		}
	}

	if ($r) {
		header("HTTP_X_RESPONSE: SUCCESS");
		$app->db->commit();
	} else {
		header("HTTP_X_RESPONSE: DBERR");
		$app->db->rollback();
	}
	exit;
}

if ($app->xhttp) {
	exit;
}

$_TEMPLATE 	= new \System\Template\Body();
$_TEMPLATE->SetWidth("800px");
$_TEMPLATE->Title($fs()->title, null, null);


echo $_TEMPLATE->CommandBarStart();
echo "<div class=\"btn-set\">";
echo "<span class=\"gap\"></span>";
echo "<button id=\"js-button-update\" type=\"button\">Update rates table</button>";
echo "</div>";
echo $_TEMPLATE->CommandBarEnd();


echo $_TEMPLATE->NewFrameBodyStart();
?>
<form id="js-formDetails">
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>System default currency</span><?= $app->currency->name . " [{$app->currency->symbol}]"; ?></div>
		<div class="btn-set vertical"><span>Latest rates updated date</span><?php echo date("Y-m-d"); ?></div>
		<div></div>
	</div>
</form>
<?php echo $_TEMPLATE->NewFrameBodyEnd();

$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Exchange rates table</span>", false, true);
echo $_TEMPLATE->NewFrameBodyStart();
echo "<form id=\"js-form\">
<input type=\"hidden\" name=\"request\" value=\"update\" />
<table class=\"bom-table mediabond-table\"><thead style=\"display:none;\"><tr><td>Sell</td><td>Curreny</td></tr></thead><tbody>";

$r = $app->db->query("SELECT cur_id, cur_name, cur_shortname, curexg_value FROM currencies LEFT JOIN  currency_exchange ON curexg_from = cur_id;");
if ($r) {
	while ($row = $r->fetch_assoc()) {
		if ($row['cur_id'] == $app->currency->id) continue;
		echo "<tr>";
		echo "<td class=\"btn-set\">";
		echo "<input title=\"{$row['cur_name']}\" type=\"text\" class=\"strict-mode flex\" 
			value=\"" . (is_null($row['curexg_value']) ? "0.00" : $row['curexg_value']) . "\" name=\"verb-exchange-sell[{$row['cur_id']}]\" />";
		echo "</td>";
		echo "<td style=\"width:100%;\"> = &nbsp;&nbsp;{$row['cur_name']}</td>";
		echo "</tr>";
	}
}

echo "</tbody>
</table></form>";
echo $_TEMPLATE->NewFrameBodyEnd();

?>
<script type="text/javascript">
	$(document).ready(function() {

		$(".strict-mode").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
			OnlyFloat(this, null, 0);
		});

		$("#js-form").on("submit", function(e) {
			e.preventDefault;
			return false;
		});
		$("#js-button-update").on("click", function() {
			overlay.show();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $("#js-form").serialize()
			}).done(function(o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				if (response == "ZERO") {
					messagesys.failure("All currencies rates are required");
				} else if (response == "SUCCESS") {
					messagesys.success("Exchange rates updated successfully");
				} else if (response == "SYSTEM") {
					messagesys.failure("System currency is not set");
				} else if (response == "DBERR") {
					messagesys.failure("Updating exchange rates failed");
				} else {
					messagesys.failure("Unknonw error");
				}
			}).always(function() {
				overlay.hide();
			});
		});

	});
</script>