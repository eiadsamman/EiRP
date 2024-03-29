<?php
use System\Finance\AccountRole;
use System\Finance\Transaction\Payment;
use System\Personalization\FrequentAccountUse;
use System\Template\Gremium;
use System\Finance\Account;
use System\SmartListObject;
use System\Template\PanelNavigator\PanelStatements;


$predefined = new \System\Finance\PredefinedRules($app);
$defines = $predefined->paymentRules();
$accounting = new \System\Finance\Accounting($app);
$perpage_val = 20;

if ($app->xhttp) {
	if (isset($_POST['objective']) && $_POST['objective'] == 'transaction') {
		header("Content-Type: application/json; charset=utf-8");
		$result = array(
			"result" => false,
			"errno" => 0,
			"error" => '',
			'insert_id' => 0,
			"debug" => ""
		);
		try {
			$accountRole = new AccountRole();
			$accountRole->inbound = true;

			$transaction = new Payment($app);
			$transaction->issuerAccount($app->user->account);
			$transaction->targetAccount(new Account($app, (int) $_POST['target-account'][1], $accountRole));
			$transaction->date($_POST['date'][0]);
			$transaction->category($_POST['category'][1] ?? 0);
			$transaction->beneficiary($_POST['beneficiary'][0] ?? "");
			$transaction->value($_POST['value'] ?? 0);
			$transaction->individual($_POST['individual'][1] ?? 0);
			$transaction->description($_POST['description'] ?? "");
			$transaction->reference($_POST['reference'][0] ?? "");
			$transaction->relation($_POST['relation'] ?? 0);

			if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
				$transaction->attachments($_POST['attachments']);
			}

			if ($transaction->post()) {
				$result['result'] = true;
				$result['insert_id'] = $transaction->insert_id;
				$result['balance'] = number_format($app->user->account->getBalance(), 2);
				$result['currency'] = $app->user->account->currency->shortname;
				new FrequentAccountUse($app, (int) $_POST['target-account'][1]);
			} else {
				$result['errno'] = 300;
				$result['error'] = "Transaction posting failed";
			}

		} catch (TypeError $e) {
			$result['errno'] = 300;
			$result['error'] = 'Uknown error, contact system administrator';
			$app->errorHandler->logError($e);
		} catch (System\Exceptions\Finance\TransactionException $e) {
			$result['errno'] = $e->getCode();
			$result['error'] = $e->getMessage();
		} catch (System\Exceptions\Finance\AccountNotFoundException $e) {
			$result['errno'] = 203;
			$result['error'] = "Select a valid account";
		} catch (System\Exceptions\Finance\ForexException $e) {
			$result['errno'] = 300;
			$result['error'] = "Forex conversion failed";
		} catch (\mysqli_sql_exception $e) {
			$result['errno'] = 300;
			$result['error'] = "Server error, try again later";
			$app->errorHandler->logError($e);
		}
		echo json_encode($result);
		exit;
	}
}

$SmartListObject = new SmartListObject($app);
if (is_null($app->user->account) || !$app->user->account->role->outbound) {
	$grem = new Gremium\Gremium();
	$grem->header()->status(Gremium\Status::Exclamation)->serve("<h1>Invalid outbound account!</h1>");
	$grem->legend()->serve("<span class=\"flex\">Selected account is not valid for outbound operations:</span>");
	$grem->article()->serve(
		<<<HTML
		<ul>
			<li>Payments require an account with outbound rules, chose a valid account and try again</li>
			<li>Contact system adminstration for further assistance</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		<b>Actions</b>
		<ul>
			<li>Goto <a href="{$fs(99)->dir}">Ledger report</a></li>
			<li>Goto <a href="{$fs(91)->dir}">New Receipt</a></li>
		</ul>
		HTML
	);
	unset($grem);
	exit;

}
?>

<?php if (!$app->xhttp) { ?>
	<div class="split-view">
		<div class="panel">
			<?php
			$panelNavigator = new PanelStatements($app);
			$panelNavigator->SidePanelHTML();
			?>
		</div>
		<div class="body" id="PanelNavigator-Body">
		<?php } ?>
		<?php
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"plus\" id=\"js-input_submit\" tabindex=\"9\">&nbsp;Submit Payment</button></div>");
		if (sizeof($defines) > 0) {
			$grem->menu()->sticky(false)->open();
			echo "<input placeholder=\"Actions...\" type=\"text\" id=\"js-defines\" data-slo=\":LIST\" tabindex=\"-1\" data-list=\"defines\" />";
			$grem->getLast()->close();
		}
		$grem->title()->serve("<span class=\"flex\">Transaction details</span>");
		$grem->article()->open();
		$curreny_date = new DateTime();
		$curreny_date = $curreny_date->format("Y-m-d");
		?>
		<form name="js-ref_form-main" id="js-ref_form-main" action="<?= $fs()->dir; ?>">
			<input type="hidden" name="challenge" value="<?= uniqid(); ?>" />
			<input type="hidden" name="objective" value="transaction" />

			<div class="form predefined">
				<label>
					<h1>Creditor</h1>
					<div class="btn-set">
						<?php
						echo "<span>{$app->user->account->name}</span>";
						if ($app->user->account->role->view) {
							echo "<span id=\"issuer-account-balance\" class=\"flex\">" . number_format($app->user->account->balance, 2, ".", ",") . "</span>";
						}
						echo "<span>{$app->user->account->currency->shortname}</span>";
						?>
					</div>
				</label>
				<label style="min-width:200px;">
					<h1>Debitor</h1>
					<div class="btn-set">
						<input tabindex="1" placeholder="Debitor account" data-required title="Debitor account" data-touch="200" type="text" data-slo=":LIST" data-list="js-ref_creditor-list" class="flex" name="target-account" id="target-account" />
					</div>
				</label>
			</div>

			<div class="form predefined">
				<label style="min-width:150px">
					<h1>Date</h1>
					<div class="btn-set">
						<input type="text" placeholder="Post date" class="flex" data-slo=":DATE" data-touch="107" title="Transaction date" value="<?= $curreny_date ?>" data-rangeend="<?= $curreny_date ?>" tabindex="2" name="date" data-required />
						<input type="text" placeholder="Due date" class="flex" data-slo=":DATE" data-touch="108" title="Transaction date" value="" data-rangeend="<?= $curreny_date ?>" tabindex="-1" name="duedate" />
					</div>
				</label>
				<label style="min-width:300px">
					<h1>Category</h1>
					<div class="btn-set">
						<input type="text" placeholder="Statement category" data-required data-slo=":LIST" data-touch="105" title="Category" data-list="jQcategoryList" tabindex="3" class="flex" name="category" id="category" />
					</div>
				</label>
			</div>
			<!-- <hr style="border:none;border-top:solid 1px rgb(230,230,230);margin:20px 0px 30px 0px" /> -->

			<div class="form">
				<label style="flex-basis:0%">
					<h1>Beneficiary</h1>
					<div class="btn-set">
						<input type="text" placeholder="Beneficiary name" data-required class="flex" title="Beneficiary name" data-touch="102" tabindex="4" data-slo=":LIST" data-list="js-ref_beneficiary-list" name="beneficiary" id="beneficiary" />
						<input type="text" placeholder="Beneficiary ID" class="flex" tabindex="-1" title="System user" data-slo="B00S" name="individual" id="individual" />
					</div>
				</label>
			</div>

			<div class="form">
				<label>
					<h1>Value</h1>
					<div class="btn-set">
						<input type="number" placeholder="Payment value" data-required tabindex="5" class="flex" data-touch="101" title="Transaction value" pattern="\d*" min="0" inputmode="decimal" name="value" id="value" />
					</div>
				</label>
			</div>

			<div class="form">
				<label style="flex:0" for="">
					<h1>Attachments</h1>
					<div class="btn-set">
						<span id="js_upload_count" class="js_upload_count"><span>0 / 0</span></span>
						<input type="button" id="js_upload_trigger" class="js_upload_trigger edge-right edge-left" value="Upload" />
						<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
						<span id="js_upload_list" class="js_upload_list">
							<div id="UploadDOMHandler">
								<table class="bom-table hover">
									<tbody>
										<?php
										$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");
										$r_release = $app->db->query("SELECT up_id,up_name,up_size,up_mime FROM uploads WHERE up_user={$app->user->info->id} AND up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . " AND up_rel=0 AND up_deleted=0 LIMIT 50;");
										if ($r_release) {
											while ($row_release = $r_release->fetch_assoc()) {
												echo \System\Attachment\Template::itemDom($row_release['up_id'], (in_array($row_release['up_mime'], $accepted_mimes) ? "image" : "document"), $row_release['up_name'], false, 'attachments');
											}
										}
										?>
									</tbody>
								</table>
							</div>
						</span>
					</div>
				</label>
				<label style="min-width:300px">
					<h1>Reference</h1>
					<div class="btn-set">
						<input type="text" placeholder="Statement reference..." data-slo="ACC_REFERENCE" title="Reference" tabindex="-1" name="reference" class="flex" />
						<input type="text" placeholder="Related ID" style="max-width:100px;min-width:100px;" title="Related transaction ID" tabindex="-1" name="relation" />
					</div>
				</label>
			</div>

			<div class="form">
				<label>
					<h1>Description</h1>
					<div class="btn-set">
						<textarea type="text" placeholder="Statement description..." data-required tabindex="6" title="Statement Description" data-touch="103" style="width:100%;min-width:100%;max-width:100%;min-height:100px;" class="textarea"
							name="description" id="description" rows="7"></textarea>
					</div>
				</label>
			</div>

		</form>
		<?php
		$grem->getLast()->close();
		$grem->title()->serve("<span class=\"flex\">Session transactions</span>");
		$grem->article()->open();
		?>
		<table class="bom-table hover">
			<thead>
				<tr>
					<td>ID</td>
					<td stlye="text-align:right">Amount</td>
					<td style="width:100%">Benificial</td>
				</tr>
			</thead>
			<tbody id="jQoutput"></tbody>
		</table>
		<?php
		$grem->getLast()->close();
		$grem->terminate();
		unset($grem);
		?>
		<div>
			<datalist id="defines">
				<?php
				if (sizeof($defines) > 0)
					foreach ($defines as $rule)
						echo "<option data-id = \"{$rule->id}\" data-account_bound = \"{$rule->outbound_account}\" data-category = \"{$rule->category}\" >{$rule->name}</option>";
				?>
			</datalist>
			<datalist id="js-ref_creditor-list" style="display: none;">
				<?= $SmartListObject->userAccountsInbound(null, [$app->user->account->id], \System\Personalization\Identifiers::SystemCountAccountOperation->value); ?>
			</datalist>
			<datalist id="jQcategoryList">
				<?= $SmartListObject->financialCategories(); ?>
			</datalist>
			<datalist id="js-ref_beneficiary-list">
				<?= $SmartListObject->financialBeneficiary(); ?>
			</datalist>
		</div>
		<?php if (!$app->xhttp) { ?>
		</div>
	</div>
	<div id="PanelNavigator-LoadingScreen">
		<?php
		$grem = new Gremium\Gremium(true);
		$grem->header()->serve("<span class=\"loadingScreen-placeholder header\">&nbsp;</span>");
		$grem->menu()->serve("<span class=\"\">&nbsp;</span>");
		$grem->title()->serve("<span class=\"loadingScreen-placeholder title\">&nbsp;</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		?>
	</div>
	<script type="text/javascript">
		let pageConfig = {
			method: "new",
			url: '<?= $fs()->dir ?>',
			title: '<?= $app->settings->site['title']; ?> - <?= $fs()->title ?>',
			id: <?= !empty($_GET['id']) ? (int) $_GET['id'] : "null"; ?>,
			upload: {
				url: "<?= $fs(186)->dir ?>",
				identifier: <?= \System\Attachment\Type::FinanceRecord->value; ?>
			}
		}
	</script>
	<script type="text/javascript" src="static/javascript/Transactions.js"></script>
	<script type="text/javascript" src="static/javascript/Navigator.js"></script>
	<script type="text/javascript" src="static/javascript/PanelNavigator.js"></script>
	<script type="text/javascript">
		let pn = new PanelNavigator();
		pn.sourceUrl = '<?= $fs(121)->dir ?>';
		pn.itemPerRequest = <?= (int) $perpage_val; ?>;
		pn.classList = ["statment-panel"];

		if (document.getElementById("js-input_btunew"))
			document.getElementById("js-input_btunew").addEventListener("click", function () {
				pn.clearActiveItem();
				pn.navigator.setProperty("id", null);
				pn.navigator.history_vars.method = "new";
				pn.navigator.history_vars.url = '<?= $fs(95)->dir; ?>';
				pn.navigator.history_vars.title = '<?= $app->settings->site['title']; ?> - <?= $fs(95)->title; ?>';
				pn.navigator.url = '<?= $fs(91)->dir; ?>';
				pn.loader(pn.navigator.history_vars.url, pn.navigator.history_vars.title, { "method": "new", "id": null }, () => { initInvokers() });
				pn.navigator.pushState();
			});


		pn.onclick = function (event) {
			pn.navigator.setProperty("id", event.dataset.listitem_id);
			pn.navigator.history_vars.method = "view";
			pn.navigator.history_vars.url = '<?= $fs(104)->dir; ?>';
			pn.navigator.history_vars.title = '<?= $app->settings->site['title']; ?> - <?= $fs(104)->title; ?>';
			pn.navigator.url = '<?= $fs(104)->dir; ?>';
			pn.loader(pn.navigator.history_vars.url, pn.navigator.history_vars.title, { "method": "view", "id": event.dataset.listitem_id });
			pn.navigator.pushState();
		}

		pn.listitemHandler = function (data) {
			let statementTypeIcon = data.positive ? `<span class="stm inc active"></span>` : `<span class="stm pay active"></span>`;
			let lockIcon = `<span class="stt chk"></span>`;
			let attachments = parseInt(data.attachements) > 0 ? `<span class="atch"></span>` : "";
			return `<div><h1>${data.beneficial}</h1><cite>${data.id}</cite></div>` +
				`<div><h1>${data.value}</h1><cite>${data.date}</cite></div>` +
				`<div><h1>${data.category}</h1><cite>${attachments}${statementTypeIcon}</cite></div>` +
				`<div><h1 class=\"description\">${data.details}</h1></div>`;
		}
		pn.init();
	</script>

<?php } ?>