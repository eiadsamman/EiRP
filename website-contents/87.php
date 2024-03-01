<?php

if ($app->xhttp) {	
	if (isset($_POST['request'], $_POST['verb-exchange-sell']) && $_POST['request'] == "update") {
		if (is_null($app->currency)) {
			header("UPDATE_STATUS: SYSTEM");
			exit;
		}
		foreach ($_POST['verb-exchange-sell'] as $cur_id => $cur_value) {
			if ((float) $cur_value == 0) {
				header("UPDATE_STATUS: ZERO");
				exit;
			}
		}

		$app->db->autocommit(false);
		$r = true;
		$r &= $app->db->query("TRUNCATE currency_exchange;");
		$r &= $app->db->query("INSERT INTO currency_exchange (curexg_from,curexg_to,curexg_value) VALUES ({$app->currency->id} , {$app->currency->id} , 1)  ON DUPLICATE KEY UPDATE curexg_value=1;");
		if ($r) {
			foreach ($_POST['verb-exchange-sell'] as $cur_id => $cur_value) {
				$cur_value = (float) $cur_value;

				$r &= $app->db->query("INSERT INTO currency_exchange (curexg_from,curexg_to,curexg_value) VALUES ({$cur_id} , {$app->currency->id} , {$cur_value})  ON DUPLICATE KEY UPDATE curexg_value={$cur_value};");
			}
		}
		if ($r) {
			header("UPDATE_STATUS: SUCCESS");
			$app->db->commit();
		} else {
			header("UPDATE_STATUS: DBERR");
			$app->db->rollback();
		}
		exit;
	}

	header("UPDATE_STATUS: BAD_REQUEST");
	exit;
}

use System\Template\Gremium;

$grem = new Gremium\Gremium(true);

$grem->header()->serve("<h1>{$fs()->title}</h1>");


$grem->menu()->open();
echo "<span class=\"gap\"></span>";
echo "<button class=\"edge-left\" id=\"js-button-update\" type=\"button\">Update rates table</button>";
$grem->getLast()->close();

$grem->article()->open();
?>
	<div class="form predefined">
		<label style="min-width:300px">
			<h1>System default currency</h1>
			<div class="btn-set">
			<?= $app->currency->name . " [{$app->currency->symbol}]"; ?>
			</div>
		</label>
		<label>
			<h1>Latest rates updated date</h1>
			<div class="btn-set">
			<?php echo date("Y-m-d"); ?>
			</div>
		</label>
	</div>
<?php
$grem->getLast()->close();

echo "<br />";
$grem->title()->serve("<span class=\"flex\">Exchange rates table</span>");
$grem->article()->open();

echo <<<HTML
<form id="js-form">
	<input type="hidden" name="request" value="update" />
	<table class="bom-table">
		<thead style="display:none;">
			<tr>
				<td>Sell</td>
				<td>Curreny</td>
			</tr>
		</thead>
		<tbody>
HTML;

$r = $app->db->query("SELECT cur_id, cur_name, cur_shortname, curexg_value FROM currencies LEFT JOIN  currency_exchange ON curexg_from = cur_id;");
if ($r) {
	while ($row = $r->fetch_assoc()) {
		if ($row['cur_id'] == $app->currency->id)
			continue;

		$val = is_null($row['curexg_value']) ? "0.00" : $row['curexg_value'];
		echo <<<HTML
		<tr>
			<td class="btn-set">
				<label style="min-width:54px">{$row['cur_shortname']}</label>
				<input 
					title = "{$row['cur_name']}" 
					type = "text" 
					value = "{$val}"
					name = "verb-exchange-sell[{$row['cur_id']}]" 
					class = "strict-mode flex" 
					/>
				<span>{$app->currency->shortname}</span>
			</td>
		</tr>
		HTML;
	}
}

echo <<<HTML
	</tbody>
	</table>
</form>
HTML;

$grem->getLast()->close();
unset($grem);

?>
<script type="text/javascript">
	$(document).ready(function () {

		$(".strict-mode").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
			OnlyFloat(this, null, 0);
		});

		$("#js-form").on("submit", function (e) {
			e.preventDefault;
			return false;
		});
		$("#js-button-update").on("click", function () {
			overlay.show();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $("#js-form").serialize()
			}).done(function (o, textStatus, request) {
				let response = request.getResponseHeader('UPDATE_STATUS');
				if (response == "ZERO") {
					messagesys.failure("All currencies rates are required");
				} else if (response == "BAD_REQUEST") {
					messagesys.failure("Invalid request");
				} else if (response == "SUCCESS") {
					messagesys.success("Exchange rates updated successfully");
				} else if (response == "SYSTEM") {
					messagesys.failure("System currency is not set");
				} else if (response == "DBERR") {
					messagesys.failure("Updating exchange rates failed");
				} else {
					messagesys.failure("Unknonw error");
				}
			}).always(function () {
				overlay.hide();
			});
		});

	});
</script>