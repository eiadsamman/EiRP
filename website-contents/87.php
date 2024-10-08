<?php
if ($app->xhttp) {
	if (isset($_POST['request']) && $_POST['request'] == "update") {
		if (is_null($app->currency)) {
			header("UPDATE_STATUS: SYSTEM");
			exit;
		}
		foreach ($_POST['verb-exchange-buy'] as $cur_id => $cur_value) {
			if ((float) $_POST['verb-exchange-buy'][$cur_id] == 0 || (float) $_POST['verb-exchange-sell'][$cur_id] == 0) {
				header("UPDATE_STATUS: ZERO");
				exit;
			}
		}

		$app->db->autocommit(false);

		$r     = true;
		$logID = false;

		$logstmt = $app->db->prepare("INSERT INTO currency_exchange_log (curexglog_date, curexglog_editor) VALUES (?,?);");
		$logstmt->bind_param("si", $postdate, $app->user->info->id);
		$postdate = (new \DateTime("now"))->format("Y-m-d H:i:s");
		$r &= $logstmt->execute();
		$logID    = $logstmt->insert_id;
		$logstmt->close();


		$r &= $app->db->query("TRUNCATE currency_exchange;");

		if ($r) {
			$stmt = $app->db->prepare("INSERT INTO currency_exchange (curexg_from, curexg_to, curexg_value, curexg_sell) VALUES (?,{$app->currency->id},?,?);");
			$stmt->bind_param("idd", $currency_id, $value_buy, $value_sell);

			$stmt_log = $app->db->prepare("INSERT INTO currency_exchange_log_values (curexglogval_prime,curexglogval_from,curexglogval_to,curexglogval_value,curexglogval_sell) VALUES ({$logID},?,{$app->currency->id},?,?);");
			$stmt_log->bind_param("idd", $currency_id, $value_buy, $value_sell);

			$currency_id = $app->currency->id;
			$value_buy   = 1;
			$value_sell  = 1;
			$stmt->execute();
			$stmt_log->execute();

			foreach ($_POST['verb-exchange-buy'] as $key => $val) {
				$currency_id = (int) $key;
				$value_buy   = (float) $_POST['verb-exchange-buy'][$key];
				$value_sell  = (float) $_POST['verb-exchange-sell'][$key];
				$r &= $stmt->execute();
				if (!$r) {
					break;
				}
				$stmt_log->execute();
			}

		}
		if ($r) {
			header("UPDATE_STATUS: SUCCESS");
			$app->db->commit();
			$app->db->autocommit(true);
			file_put_contents("{$app->root}broadcast", md5(uniqid()));

			echo <<<HTML
				<span>$postdate</span>
				<span class="at"><a href="{$fs(182)->dir}/?id={$app->user->info->id}">{$app->user->info->fullName()}</a></span>
			HTML;
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

/* Fetch latest update log */
$stmt   = $app->db->prepare(
	"SELECT 
		curexglog_date, usr_id,usr_firstname, usr_lastname 
	FROM 
		currency_exchange_log
		LEFT JOIN users ON usr_id = curexglog_editor 
	ORDER BY curexglog_id  DESC LIMIT 1;
 "
);
$exe    = $stmt->execute();
$record = $stmt->get_result();
$record = $record ? $record->fetch_assoc() : false;
$stmt->close();

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
			<span><?= $app->currency->name . " [{$app->currency->symbol}]"; ?></span>
		</div>
	</label>
	<label>
		<h1>Current Exchange</h1>
		<div class="btn-set" id="latest-update-slip">
			<span><?= $record ? ($record['curexglog_date']) : "N/A"; ?></span>
			<span class="at"><?= $record ? "<a href=\"{$fs(182)->dir}/?id={$record['usr_id']}\">".($record['usr_firstname'] . " " . $record['usr_lastname'])."</a>" : "N/A"; ?></span>
		</div>
	</label>
</div>
<?php
$grem->getLast()->close();

echo "<br />";
$grem->title()->serve("<span class=\"flex\">Rates conversion</span>");
$grem->article()->open();

echo <<<HTML
<form id="js-form">
	<input type="hidden" name="request" value="update" />
	<table class="form-table">
		<thead style="display:none;">
			<tr>
				<td>Sell</td>
				<td>Curreny</td>
			</tr>
		</thead>
		<tbody>
HTML;

$r = $app->db->query("SELECT cur_id, cur_name, cur_shortname, curexg_value, curexg_sell FROM currencies LEFT JOIN  currency_exchange ON curexg_from = cur_id;");
if ($r) {
	echo <<<HTML
			<tr>
				<td style="min-width:70px"></td>
				<td style="color:var(--root-font-lightcolor);text-align:center">Buy</td>
				<td style="color:var(--root-font-lightcolor);text-align:center">Sell</td>
				<td width="100%"></td>
			</tr>
		HTML;
	while ($row = $r->fetch_assoc()) {
		if ($row['cur_id'] == $app->currency->id)
			continue;

		$decimalPadLength = 2;
		$decimalPadChar   = "0";
		$output           = array();

		$output['buy']  = (float) (is_null($row['curexg_value']) ? "0.00" : $row['curexg_value']);
		$output['sell'] = (float) (is_null($row['curexg_sell']) ? "0.00" : $row['curexg_sell']);

		foreach ($output as &$val) {
			$val    = floor($val) == $val ? $val . "." : $val;
			$lendec = strlen(explode(".", $val)[1]);
			$val    = $val . ($decimalPadLength - $lendec > 0 ? str_repeat($decimalPadChar, $decimalPadLength - $lendec) : "");
		}

		echo <<<HTML
		<tr>
			<td style="text-align:center">{$row['cur_shortname']}</td>
			<td>
				<div class="btn-set">
					<input 
					title = "{$row['cur_name']}" 
					type = "text" 
					value = "{$output['buy']}"
					inputmode = "decimal"
					name = "verb-exchange-buy[{$row['cur_id']}]" 
					class = "strict-mode flex" 
					style = "max-width:200px;text-align:right;padding-right:10px;"
					/>
				</div>
			</td>
			<td>
				<div class="btn-set">
					<input 
					title = "{$row['cur_name']}" 
					type = "text" 
					value = "{$output['sell']}"
					inputmode = "decimal"
					name = "verb-exchange-sell[{$row['cur_id']}]" 
					class = "strict-mode flex" 
					style = "max-width:200px;text-align:right;padding-right:10px;"
					/>
				</div>
			</td>
			<td></td>
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
$grem->terminate();

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
					document.getElementById("latest-update-slip").innerHTML = o;
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