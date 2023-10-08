<?php
use System\Finance\Transaction\Receipt;
use System\Finance\Transaction\Transaction;
use System\Template\Gremium;
use System\Finance\Account;
use System\SmartListObject;


#https://www.youtube.com/watch?v=hkTQkaFzEEo


$predefined = new \System\Finance\PredefinedRules($app);
$defines = $predefined->paymentRules();

$accounting = new \System\Finance\Accounting($app);



function _JSON_output($result, $message, $focus = null, $extra = null)
{
	echo "{";
	echo "\"result\":" . ($result == true ? "true" : "false") . "";
	echo ",\"message\":\"" . addslashes($message) . "\"";
	echo $focus != null ? ",\"focus\":\"$focus\"" : ",\"focus\":false";
	if ($extra != null && is_array($extra)) {
		foreach ($extra as $k => $v) {
			echo ",\"$k\":\"$v\"";
		}
	}
	echo "}";
	exit;
}

if (isset($_POST['method']) && $_POST['method'] == 'addstatement') {

	try {
		$prepare = new Receipt($app);
		$prepare->issuerAccount($app->user->account);
		$prepare->targetAccount(new Account($app, (int) $_POST['creditor']));
		$prepare->category($_POST['category']);
		$prepare->value($_POST['value']);
		$prepare->beneficiary($_POST['benificial']);
		$prepare->description($_POST['comments'] ?? null);
		$prepare->individual($_POST['employee']);
		$prepare->reference($_POST['reference']);
		$prepare->relation($_POST['rel']);

		if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
			$prepare->attachments($_POST['attachments']);
		}
		$prepare->date($_POST['trandate']);
		/* Month ? */
		/* Category check */
		/* And forex */

		$trans = new Transaction($app, $prepare);

		$trans->execute();


	} catch (TypeError $e) {
		echo $e->getMessage();
	} catch (System\Exceptions\Finance\TransactionPrepareException $e) {
		echo $e->getMessage();
	} catch (System\Exceptions\Finance\AccountNotFoundException $e) {
		echo "Account not found";
	} catch (System\Exceptions\Finance\ForexCurrencyNotFound $e) {
		echo "Forex failed";
	} catch (\mysqli_sql_exception $e){
		echo "SQL exception ".$e->getMessage();
	}

	exit;


	$value_from = $value;
	$value_to = $value;
	$exchangerate_crd = 1;
	$exchangerate_dbt = 1;
	if ($versus_account->currency->id != $app->user->account->currency->id) {
		$exchangerate = $accounting->currency_exchange($versus_account->currency->id, $app->user->account->currency->id);
		if ($exchangerate === false) {
			_JSON_output(false, "Unable to exchange currencies with debitor account");
		}
		$value_to = $exchangerate * $value_to;
		$exchangerate_crd = $exchangerate;
	}

	$result = true;
	$app->db->autocommit(false);


	$mainid = (int) $app->db->insert_id;

	//INSERT creditor statement
	$qacc_release = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $versus_account->id, -1 * $value_from, 0, $mainid);
	$result &= $app->db->query($qacc_release);

	//INSERT debitor statement
	$qacc_insert = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $app->user->account->id, $value_to, 1, $mainid);
	$result &= $app->db->query($qacc_insert);

	//Attach uploaded files to the transaction
	if (sizeof($attachments) > 0) {
		$qacc_attach = "UPDATE uploads SET up_rel=$mainid, up_active = 1 WHERE up_id IN (" . implode(",", $attachments) . ") AND up_user = {$app->user->info->id};";
		$result &= $app->db->query($qacc_attach);
	}

	if ($result) {
		$app->db->commit();
		$app->db->query("INSERT INTO user_settings (usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) 
				VALUES ({$app->user->info->id}," . \System\Personalization\Identifiers::SystemCountAccountOperation->value . ",{$versus_account->id}, 1 ,NOW()) ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");

		$balance = 0;
		if ($app->user->account->role->view) {
			if ($r = $app->db->query("SELECT SUM(atm_value) AS zsum FROM acc_temp JOIN acc_main ON acm_id=atm_main WHERE atm_account_id={$app->user->account->id} AND acm_rejected=0;")) {
				if ($row = $r->fetch_assoc()) {
					$balance = $row['zsum'];
				}
			}
		}
		_JSON_output(
			true,
			"Statement submited successfully",
			null,
			array(
				"newbalance" => number_format((float) $balance, 2, ".", ","),
				"id" => $mainid,
				"value" => number_format((float) $value, 2, ".", "")
			)
		);
	} else {
		$app->db->rollback();
		_JSON_output(false, "Statement insertion failed");
	}
	exit;
}
if ($app->xhttp) {
	exit;
}
/*AJAX-END*/



$SmartListObject = new SmartListObject($app);



if (!$app->user->account->role->inbound) {

	$grem = new Gremium\Gremium();
	$grem->header()->status(Gremium\Status::Exclamation)->serve("<h1>Invalid inbound account!</h1>");

	$grem->legend()->serve("<span class=\"flex\">Selected account is not valid for inbound operations:</span>");
	$grem->article()->serve('
		<ul>
			<li>Receipts require an account with inbound rules, chose a valid account and try again</li>
			<li>Contact system adminstration for further assistance</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		<b>Actions</b>
		<ul>
			<li>Goto <a href="{$fs(99)->dir}">Ledger report</a></li>
			<li>Goto <a href="{$fs(95)->dir}">New Payment</a></li>
		</ul>
	');
	unset($grem);
	exit;

} else {
	?>
	<iframe style="display:none;" src="" id="jQiframe"></iframe>
	<?php

	$grem = new Gremium\Gremium(true);
	$grem->header()->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"clr-green\" id=\"jQsubmit\" tabindex=\"9\">Submit Receipt</button></div>");

	if (sizeof($defines) > 0) {
		$grem->menu()->sticky(false)->open();
		echo "<input placeholder=\"Actions...\" type=\"text\" id=\"js-defines\" data-slo=\":LIST\" data-list=\"defines\" />";
		$grem->getLast()->close();
	}

	$grem->article()->open();
	?>
	<table class="bom-table mediabond-table" id="jQformTable">
		<tbody>
			<tr class="predefined">
				<th>Creditor</th>
				<td>
					<div class="btn-set"><input tabindex="1" type="text" data-slo=":LIST" data-list="jQcreditorList" class="flex" id="jQcreditor" /></div>
					<datalist id="jQcreditorList" style="display: none;">
						<?= $SmartListObject->userAccountsOutbound(null, [$app->user->account->id]); ?>
					</datalist>

				</td>
			</tr>
			<tr class="predefined">
				<th>Date</th>
				<td>
					<div class="btn-set">
						<input type="text" class="flex" data-slo=":DATE" value="<?php echo date("Y-m-d"); ?>" data-rangeend="<?php echo date("Y-m-d"); ?>" tabindex="1" id="jQdate" />
						<!-- <span>Month reference</span> -->
						<input id="jQmonth" tabindex="-1" value="" data-slo="MONTH" type="text" style="width:100px;display:none" />
					</div>
				</td>
			</tr>
			<tr class="predefined">
				<th>Debitor</th>
				<td>
					<?php
					echo "<div class=\"btn-set\">";
					echo "<span tabindex=\"-1\">{$app->user->account->type->name}: {$app->user->account->name}</span>";
					if ($app->user->account->balance) {
						echo "<input type=\"text\" readonly=\"readonly\" class=\"flex\" style=\"text-align:right\" tabindex=\"-1\" id=\"jQbalanceTitle\" value=\"" . number_format($app->user->account->balance, 2, ".", ",") . "\" />";
					} else {
						echo "<span class=\"flex\"></span>";
					}
					echo "<span style=\"text-align:center\">{$app->user->account->currency->shortname}</span></div>";
					?>
				</td>
			</tr>
			<tr class="predefined">
				<th>Category</th>
				<td>
					<div class="btn-set">
						<input type="text" data-slo=":LIST" data-list="jQcategoryList" tabindex="3" class="flex" id="jQcategory" />
					</div>
					<datalist id="jQcategoryList">
						<?= $SmartListObject->financialCategories(); ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th>Beneficiary</th>
				<td width="100%">
					<div class="btn-set">
						<input type="text" class="flex" tabindex="4" data-slo=":LIST" data-list="jQbeneficialList" id="jQbeneficial" />
						<input type="text" tabindex="-1" data-slo="B00S" id="jQemployee" />
					</div>
					<datalist id="jQbeneficialList">
						<?= $SmartListObject->financialBeneficiary(); ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th>Value</th>
				<td>
					<div class="btn-set">
						<input type="number" tabindex="5" class="flex" id="jQvalue" pattern="\d*" min="0" inputmode="decimal" />

					</div>
				</td>
			</tr>
			<tr>
				<th>Attachments</th>
				<td>
					<div class="btn-set" style="justify-content:left">
						<button id="js_upload_trigger" class="js_upload_trigger">Upload</button>
						<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
						<span id="js_upload_list" class="js_upload_list"></span>
						<button id="js_upload_count" class="js_upload_count"><span>0 / 0</span> files</button>
						<!--<label class="btn-checkbox"><input type="checkbox" id="jQprodmode"><span>Prouductive mode</span></label>-->
					</div>
				</td>
			</tr>
			<tr>
				<th>Reference</th>
				<td>
					<div class="btn-set">
						<input type="text" data-slo="ACC_REFERENCE" tabindex="6" id="jQreference" class="flex" />
						<input type="text" id="jQrel" style="max-width:100px;min-width:100px;" tabindex="-1" placeholder="Related ID" />
					</div>
				</td>
			</tr>
			<tr>
				<th>Comments</th>
				<td>
					<div class="btn-set">
						<textarea type="text" tabindex="8" style="width:100%;min-width:100%;max-width:100%;min-height:100px;" class="textarea" id="jQcomments" rows="7"></textarea>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<br />
	<?php
	$grem->getLast()->close();

	$grem->legend()->serve("<span class=\"flex\">Session Receipts</span><span id=\"jQtotalEntries\">0</span><input tabindex=\"-1\" type=\"text\" style=\"text-align: right;\" readonly=\"readonly\" id=\"jQtotalTotal\" value=\"0\" />");
	$grem->article()->open();
	?>
	<table class="bom-table hover">
		<thead>
			<tr>
				<td>ID</td>
				<td stlye="text-align:right" colspan="2">Amount</td>
				<td>Benificial</td>
				<td colspan="3" stlye="text-align:center" width="100%"></td>
			</tr>
		</thead>
		<tbody id="jQoutput"></tbody>
	</table>

	<?php
	$grem->getLast()->close();
	unset($grem);
	?>

	<datalist id="defines">
		<?php
		if (sizeof($defines) > 0) {
			foreach ($defines as $rule) {
				echo "<option data-id = \"{$rule->id}\" data-account_bound = \"{$rule->outbound_account}\" data-category = \"{$rule->category}\" >{$rule->name}</option>";
			}
		}
		?>
	</datalist>

	<script type="text/javascript">
		var $ajax = null;
		var trancount = 0;
		var trantotal = 0;
		$(document).ready(function (e) {
			Upload = $.Upload({
				objectHandler: $("#js_upload_list"),
				domselector: $("#js_uploader_btn"),
				dombutton: $("#js_upload_trigger"),
				list_button: $("#js_upload_count"),
				emptymessage: "[No files uploaded]",
				delete_method: 'permanent',
				upload_url: "<?= $fs(186)->dir ?>",
				relatedpagefile: <?= \System\Attachment\Type::FinanceRecord->value; ?>,
				multiple: true,
				inputname: "attachments"
			});
			<?php
			$empty = true;
			$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");
			$r_release = $app->db->query("SELECT up_id,up_name,up_size,up_mime FROM uploads WHERE up_user={$app->user->info->id} AND up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . " AND up_rel=0 AND up_deleted=0;");
			if ($r_release) {
				while ($row_release = $r_release->fetch_assoc()) {
					$empty = false;
					echo "Upload.AddListItem({$row_release['up_id']},'{$row_release['up_name']}',false,false,'" . (in_array($row_release['up_mime'], $accepted_mimes) ? "image" : "document") . "');";
				}
			}
			?>

			const $jQcreditor = $("#jQcreditor").slo(),
				$jQbeneficial = $("#jQbeneficial").slo(),
				$jQdate = $("#jQdate").slo(),
				$form = $("#jQformTable"),
				$jQmonth = $("#jQmonth").slo(),
				$jQreference = $("#jQreference").slo(),
				$jQemployee = $("#jQemployee").slo({
					'onselect': function (data) {
						$jQbeneficial.set(data.hidden, data.value);
						$("#jQbeneficial").prop("readonly", true).prop("disabled", true);
					},
					'ondeselect': function () {
						$("#jQbeneficial").prop("readonly", false).prop("disabled", false);
						$jQbeneficial.clear();
					}
				}),
				$jQcategory = $("#jQcategory").slo({
					'onselect': function (data) { }
				}),
				$jQrel = $("#jQrel");


			$("#jQvalue").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
				OnlyFloat(this, null, 0);
			});
			var addStatement = function () {
				var $creditor = $("#jQcreditor_1"),
					$category = $("#jQcategory_1"),
					$date = $("#jQdate_1"),
					$dateSLO = $("#jQdate"),
					$monthSLO = $("#jQmonth"),
					$month = $("#jQmonth_1"),
					$creditorSLO = $("#jQcreditor"),
					$categorySLO = $("#jQcategory"),
					$SLO = $("#jQcurrenyident"),
					$benificialSLO = $("#jQbeneficial"),
					$employeeSLO = $("#jQemployee"),
					$employee = $("#jQemployee_1"),
					$value = $("#jQvalue"),
					$comments = $("#jQcomments"),
					$submitbtn = $("#jQsubmit"),
					$reference = $("#jQreference");

				var waitingState = function (status) {
					$submitbtn.prop("disabled", status);
					$creditorSLO.prop("disabled", status);
					$categorySLO.prop("disabled", status);
					$value.prop("disabled", status);
					$benificialSLO.prop("disabled", status);
					$comments.prop("disabled", status);
					$reference.prop("disabled", status);
					$dateSLO.prop("disabled", status);
					$monthSLO.prop("disabled", status);
					$employeeSLO.prop("disabled", status);
				}
				if ($creditor.val() == "" || parseInt($creditor.val()) == 0) {
					messagesys.failure("Select debitor account");
					$creditorSLO.focus().select();
					return false;
				}
				if (parseInt($creditor.val()) == <?php echo $app->user->account->id; ?>) {
					messagesys.failure("Debitor account must not be same as Creditor account");
					$creditorSLO.focus().select();
					return false;
				}
				if ($category.val() == "" || parseInt($category.val()) == 0) {
					messagesys.failure("Select the transaction category");
					$categorySLO.focus().select();
					return false;
				}
				if ($benificialSLO.val().trim() == "") {
					messagesys.failure("Beneficiary name is required");
					$jQbeneficial.focus();
					return false;
				}

				try {
					_value = parseFloat($value.val());
				} catch (e) {
					messagesys.failure("Enter a valid value");
					$value.focus().select();
					return false;
				}
				if (isNaN(_value) || _value <= 0) {
					messagesys.failure("Enter a valid value");
					$value.focus().select();
					return false;
				}




				waitingState(true);
				var preparePOST = $("#js_upload_list :input").serialize() + '&' + $.param({
					method: 'addstatement',
					creditor: $creditor.val(),
					category: $category.val(),
					benificial: $benificialSLO.val(),
					value: $value.val(),
					trandate: $date.val(),
					comments: $comments.val(),
					reference: $reference.val(),
					month: $month.val(),
					employee: $employee.val(),
					rel: ~~$jQrel.val(),
				});

				$ajax = $.ajax({
					data: preparePOST,
					url: "<?php echo $fs()->dir; ?>",
					type: "POST"
				}).done(function (data) {

					console.log(data);
					return;
					var _data = null;
					try {
						_data = JSON.parse(data);
					} catch (e) {
						messagesys.failure("Parsing JSON failed");
						waitingState(false);
						return false;
					}

					if (_data.result == true) {
						messagesys.success(_data.message);
						$("#jQoutput").prepend(
							"<tr data-transactionid=\"" + _data.id + "\">" +
							"<td>" + _data.id + "</td>" +
							"<td align=\"right\">" + _data.value + "</td>" +
							"<td></td>" +
							"<td>" + $benificialSLO.val() + "</td>" +
							"</tr>"
						);

						if (!$("#jQprodmode").prop("checked")) {
							$jQemployee.clear();
							$jQreference.clear();
							$jQbeneficial.clear();
							$comments.val("");
						}
						trancount++;
						trantotal += ~~_data.value;
						$("#jQtotalEntries").html(trancount);
						$("#jQtotalTotal").val(trantotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
						$("#jQbalanceTitle").val(_data.newbalance);
						BALANCE_UPDATE();
						waitingState(false);
						$("#jQbeneficial").prop("readonly", false).prop("disabled", false);
						//$("#jQiframe").attr("src","<?= $fs(142)->dir ?>/?id="+_data.id);
						$value.val("").focus().select();
						Upload.clean();
						BALANCE_UPDATE();
						return true;
					} else {
						messagesys.failure(_data.message);
						waitingState(false);
						if (_data.focus != false)
							$("#" + _data.focus).focus().select();
						return false;
					}
				}).fail(function (a, b, c) {
					messagesys.failure(b + " - " + c);
				}).always(function () {
					waitingState(false);
				});
			}
			$("#jQsubmit").on('click', function () {
				addStatement();
			});

			$("#js-defines").slo({
				onselect: function (e) {
					const selected_option = document.querySelector("#defines option[data-id='" + e.hidden + "']");
					$jQcreditor.set(selected_option.dataset.account_bound, selected_option.dataset.account_bound);
					$jQcategory.set(selected_option.dataset.category, selected_option.dataset.category);
					document.querySelectorAll(".predefined").forEach(element => {
						element.style.display = "none";
					});
					$jQbeneficial.focus();
				}, ondeselect: function (e) {
					$jQcreditor.clear();
					$jQcategory.clear();
					document.querySelectorAll(".predefined").forEach(element => {
						element.style.display = "table-row";
					});
				}
			});
			//$jQcreditor.focus();
		});
	</script>
<?php } ?>