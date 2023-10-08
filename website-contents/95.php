<?php
use System\Finance\Account;
use System\Template\Gremium;
use System\Finance\PredefinedRules;
use System\SmartListObject;


$predefined = new PredefinedRules($app);
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

	if (!$app->user->account->role->outbound) {
		_JSON_output(false, "Outbound rules is not allowed on `{$app->user->account->name}`");
	}

	$versus_account = new Account($app, (int) $_POST['creditor']);
	$category = $_POST['category'];
	$value = (float) trim(str_replace(",", "", $_POST['value']));
	$comments = isset($_POST['comments']) && trim($_POST['comments']) != "" ? addslashes($_POST['comments']) : null;
	$benificial = isset($_POST['benificial']) && trim($_POST['benificial']) != "" ? addslashes($_POST['benificial']) : null;
	$reference = isset($_POST['reference']) && trim($_POST['reference']) != "" ? addslashes($_POST['reference']) : null;
	$employee = isset($_POST['employee']) && (int) ($_POST['employee']) != 0 ? (int) ($_POST['employee']) : false;
	$rel = isset($_POST['rel']) && (int) $_POST['rel'] != 0 ? (int) $_POST['rel'] : "NULL";




	if (!$versus_account->role->inbound) {
		_JSON_output(false, "Invalid account", "jQcreditor");
	}
	if ($versus_account->id == $app->user->account->id) {
		_JSON_output(false, "Debitor account can't be as same as Creditor account", "jQcreditor");
	}
	$date = $app->date_validate($_POST['trandate'] ?? "");
	if ($date === false) {
		_JSON_output(false, "Select the statement date", "jQdate");
	}
	if (trim($benificial) == "") {
		_JSON_output(false, "Benificial name is required", "jQbeneficial");
	}
	if ((float) $value <= 0) {
		_JSON_output(false, "Enter a valid float value", "jQvalue");
	}



	if ($r = $app->db->query("SELECT acccat_id FROM acc_categories WHERE acccat_id=$category;")) {
		if ($r->num_rows == 0) {
			_JSON_output(false, "Select the statement category", "jQcategory");
		}
	}


	$month = false;
	if (isset($_POST['month']) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['month'], $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			$month = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		}
	}


	//Prepare attachment list
	$attachments = array();
	if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
		foreach ($_POST['attachments'] as $VAtt) {
			$attachments[] = (int) $VAtt;
		}
	}

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

	$qacc_main = sprintf(
		"INSERT INTO acc_main (
		acm_usr_id,
		acm_editor_id,
		acm_ctime,
		acm_time,
		acm_type,
		acm_beneficial,
		acm_category,
		acm_comments,
		acm_reference,
		acm_realvalue,
		acm_realcurrency,
		acm_realcurrency_crd,
		acm_realcurrency_dbt,
		acm_month,
		acm_rel,
		acm_party
		) VALUES (
		%1\$s,
		%2\$d,
		%4\$s,
		%15\$s,
		%5\$d,
		'%6\$s',
		%7\$d,
		%8\$s,
		%9\$s,
		%10\$f,
		%11\$d,
		%12\$f,
		%13\$f,
		%14\$s,
		%16\$s,
		%17\$d
		);",
		$employee ? (int) $employee : "NULL",
		$app->user->info->id,
		"NULL",
		"'" . date("Y-m-d", $date) . "'",
		\System\Finance\Transaction\Nature::Payment->value,
		$benificial,
		(int) $category,
		($comments != null ? "'" . $comments . "'" : "NULL"),
		($reference != null ? "'" . $reference . "'" : "NULL"),
		$value,
		$app->user->account->currency->id,
		$exchangerate_crd,
		$exchangerate_dbt,
		($month ? "FROM_UNIXTIME(" . $month . ")" : "NULL"),
		"FROM_UNIXTIME(" . time() . ")",
		$rel,
		$app->user->company->id
	);
	$result &= $app->db->query($qacc_main);

	if (!$result) {
		$app->db->rollback();
		_JSON_output(false, "Database error, reference error 91 at line " . __LINE__);
	}


	$mainid = (int) $app->db->insert_id;

	//INSERT creditor statement
	$qacc_release = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $app->user->account->id, -1 * $value_from, 0, $mainid);
	$result &= $app->db->query($qacc_release);

	//INSERT debitor statement
	$qacc_insert = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $versus_account->id, $value_to, 1, $mainid);
	$result &= $app->db->query($qacc_insert);

	//Attach uploaded files to the transaction
	if (sizeof($attachments) > 0) {
		$qacc_attach = "UPDATE uploads SET up_rel=$mainid, up_active = 1 WHERE up_id IN (" . implode(",", $attachments) . ") AND up_user = {$app->user->info->id};";
		$result &= $app->db->query($qacc_attach);
	}

	if ($result) {
		$app->db->commit();
		$balance = 0;
		$app->db->query("INSERT INTO user_settings (usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) 
				VALUES ({$app->user->info->id}," . \System\Personalization\Identifiers::SystemCountAccountOperation->value . ", {$versus_account->id}, 1, NOW()) ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");

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


if (!$app->user->account->id) {
	echo "<div class=\"btn-set\"><button>{$app->user->account->name}</button><span>Is not a valid account</span></div>";
} elseif (!$app->user->account->role->outbound) {

	$grem = new Gremium\Gremium();
	$grem->header()->status(Gremium\Status::Exclamation)->serve("<h1>Invalid outbound account!</h1>");

	$grem->legend()->serve("<span class=\"flex\">Selected account is not valid for payment operations:</span>");
	$grem->article()->serve('
		<ul>
			<li>Payments require an account with outbound rules, chose a valid account and try again</li>
			<li>Contact system adminstration for further assistance</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		<b>Related links:</b>
		<ul>
			<li>Goto <a href="{$fs(99)->dir}">Ledger report</a></li>
			<li>Goto <a href="{$fs(91)->dir}">New Receipt</a></li>
		</ul>
	');
	unset($grem);
	exit;

} else {
	?>
	<iframe style="display:none;" src="" id="jQiframe"></iframe>
	<?php

	$grem = new Gremium\Gremium(true);
	$grem->header()->prev($fs(179)->dir)->sticky(true)->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"clr-red\" id=\"jQsubmit\" tabindex=\"9\">Submit Payment</button></div>");

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
				<td width="100%">
					<?php
					echo "<div class=\"btn-set\">
							<span tabindex=\"-1\">{$app->user->account->type->name}: {$app->user->account->name}</span>
							<input type=\"text\" readonly=\"readonly\" class=\"flex\" style=\"text-align:right\" tabindex=\"-1\" id=\"jQbalanceTitle\" value=\"" . number_format($app->user->account->balance, 2, ".", ",") . "\" />
							<span style=\"text-align:center\">{$app->user->account->currency->shortname}</span>
						</div>";
					?>
				</td>
			</tr>
			<tr class="predefined">
				<th>Date</th>
				<td>
					<div class="btn-set">
						<input type="text" class="flex" data-slo=":DATE" value="<?php echo date("Y-m-d"); ?>" data-rangeend="<?php echo date("Y-m-d"); ?>" tabindex="1" id="jQdate" />
						<!-- <span>Month reference</span> -->
						<input id="jQmonth" tabindex="-1" value="" data-slo="MONTH" type="text" style="width:100px;display:none;" />
					</div>
				</td>
			</tr>
			<tr class="predefined">
				<th>Debitor</th>
				<td>
					<div class="btn-set"><input tabindex="2" type="text" data-slo=":LIST" data-list="jQcreditorList" class="flex" id="jQcreditor" /></div>
					<datalist id="jQcreditorList" style="display: none;">
						<?= $SmartListObject->userAccountsInbound(null, [$app->user->account->id]); ?>
					</datalist>
				</td>
			</tr>
			<tr class="predefined">
				<th>Category</th>
				<td>
					<div class="btn-set"><input type="text" data-slo=":LIST" data-list="jQcategoryList" tabindex="3" class="flex" id="jQcategory" /></div>
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
					<div class="btn-set"><input type="text" data-slo="ACC_REFERENCE" tabindex="6" id="jQreference" class="flex" /><input type="text" id="jQrel" style="max-width:100px;min-width:100px;"
							tabindex="-1" placeholder="Related ID" /></div>
				</td>
			</tr>
			<tr>
				<th>Comments</th>
				<td>
					<div class="btn-set"><textarea type="text" tabindex="8" style="width:100%;min-width:100%;max-width:100%;min-height:100px;" class="textarea" id="jQcomments" rows="7"></textarea></div>
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
				echo "<option 
						data-id = \"{$rule->id}\"
						data-account_bound = \"{$rule->inbound_account}\"
						data-category = \"{$rule->category}\"
						>{$rule->name}</option>";
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
				relatedpagefile: <?= \System\Attachment\Type::FinanceRecord->value ?>,
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

			const $jQcreditor = $("#jQcreditor").slo({
				'limit': 10,
				onselect: function (e) {
					console.log(e)
				}
			}),
				$jQbeneficial = $("#jQbeneficial").slo({
					'limit': 7,
				}),
				$jQdate = $("#jQdate").slo(),
				$form = $("#jQformTable"),
				$jQmonth = $("#jQmonth").slo(),
				$jQreference = $("#jQreference").slo(),
				$jQemployee = $("#jQemployee").slo({
					'onselect': function (data) {
						$jQbeneficial.set(data.value, data.value);
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
			$jQcreditor.focus();
		});
	</script>
<?php } ?>