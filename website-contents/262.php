<?php
use System\App;
use System\Finance\Account;
use System\Finance\Currency;
use System\Finance\Term;
use System\Finance\Term\Asset;
use System\Finance\Term\Equity;
use System\Finance\Term\IncomeStatement;
use System\Finance\Term\Liability;
use System\Profiles\AccountProfile;
use System\Template\Gremium\Gremium;



class BalanceSheetView
{

	private array $table = [];
	private array $accounts = [];

	private Gremium $grem;

	public function __construct(protected App &$app)
	{
	}

	public function view(): void
	{
		$this->table                    = [];
		$this->table['asset']           = ["current" => [], "noncurrent" => []];
		$this->table['liability']       = ["current" => [], "noncurrent" => []];
		$this->table['equity']          = [];
		$this->table['incomestatement'] = ["revenue" => [], "expense" => []];


		foreach (Asset::cases() as $term) {
			if (Term::isCurrent($term)) {
				$this->table['asset']["current"][] = $term;
			} else {
				$this->table['asset']["noncurrent"][] = $term;
			}
		}
		foreach (Liability::cases() as $term) {
			if (Term::isCurrent($term)) {
				$this->table['liability']["current"][] = $term;
			} else {
				$this->table['liability']["noncurrent"][] = $term;
			}
		}
		foreach (Equity::cases() as $term) {
			$this->table['equity'][] = $term;
		}
		foreach (IncomeStatement::cases() as $term) {
			if (Term::isRevenue($term)) {
				$this->table['incomestatement']["revenue"][] = $term;
			} else {
				$this->table['incomestatement']["expense"][] = $term;
			}
		}

		$r = $this->app->db->execute_query(
			"SELECT prt_id, prt_name, prt_term, cur_id, cur_name, cur_shortname, cur_symbol 
			FROM view_financial_accounts
			WHERE comp_id = ? AND prt_term IS NOT NULL;"
			,
			[
				$this->app->user->company->id
			]
		);
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				$term = Term::from((int) $row['prt_term']);
				if ($term === null)
					continue;

				if (!isset($this->accounts[$term->value])) {
					$this->accounts[$term->value] = [];
				}

				$accountProfile = new AccountProfile();

				$accountProfile->id                  = (int) $row['prt_id'];
				$accountProfile->name                = $row['prt_name'] ?? "";
				$accountProfile->currency            = new Currency();
				$accountProfile->currency->id        = (int) $row['cur_id'];
				$accountProfile->currency->name      = $row['cur_name'];
				$accountProfile->currency->shortname = $row['cur_shortname'];
				$accountProfile->currency->symbol    = $row['cur_symbol'];
				$accountProfile->term                = Term::from((int) $row['prt_term']);

				$this->accounts[$term->value][] = $accountProfile;
			}
		}
		$this->plot();
	}

	private function rowTemplate(array $terms): string
	{
		$o = "";
		foreach ($terms as $term) {
			$o .= "<span class=\"termgroup\">
				<span class=\"termtitle btn-addTermAccount\" data-termtype=\"{$term->termType()}\"  data-termname=\"{$term->name}\" data-termid=\"{$term->value}\">{$term->name}</span>
				<div class=\"termaccounts\" data-termaccounts=\"{$term->value}\">";
			if (isset($this->accounts[$term->value])) {
				foreach ($this->accounts[$term->value] as $account) {
					if ($account->term != null && $account->term->value == $term->value) {
						$o .= "
						<span class=\"btn-set\">
							<input type=\"hidden\" class=\"-input-accounts\" name=\"term[{$term->value}][]\" value=\"{$account->id}\" />
							<span class=\"flex\">[{$account->currency->shortname}] {$account->name}</span>
							<button type=\"button\" class=\"standard error edge-left btn-removeTermAccount\"></button>
						</span>";
					}
				}
			}
			$o .= "</div>
				</span>";
		}
		return $o;
	}

	private function plot(): void
	{
		$this->grem = new Gremium(false);
		$this->grem->header()->serve("<h1>Balance Sheet</h1><cite></cite><div class=\"btn-set\"><button class=\"success\" id=\"appSaveButton\" >&nbsp;Save changes</button><a class=\"edge-right\" id=\"appResetButton\">Reset</a></div>");
		$this->grem->menu()->sticky(false)->serve("<button class=\"standard\">Print</button><button class=\"standard\">Export</button>");
		$this->grem->title()->serve("<span>Company: {$this->app->user->company->name}</span>");
		$this->grem->article()->open();

		echo <<<HTML
		<form action="{$this->app->file->dir}" id="appForm">
			<input type="hidden" name="company_id" value="{$this->app->user->company->id}" />
			<div class="balancesheet">
				<div>
					<h1>Assets</h1>
					<h2>Current</h2><div>{$this->rowTemplate($this->table['asset']['current'])}</div>
					<h2>Non-current</h2><div>{$this->rowTemplate($this->table['asset']['noncurrent'])}</div>
				</div>
				<div>
					<h1>Liabilities</h1>
					<h2>Current</h2><div>{$this->rowTemplate($this->table['liability']['current'])}</div>
					<h2>Non-current</h2><div>{$this->rowTemplate($this->table['liability']['noncurrent'])}</div>

					<h1>Equities</h1>
					<h2>Capital Contributions</h2>
					<div>{$this->rowTemplate($this->table['equity'])}</div>
					
					<h2>Income Statement</h2>
					<h3>Revenue</h3>
					<div>{$this->rowTemplate($this->table['incomestatement']['revenue'])}</div>
					<h3>Expense</h3>
					<div>{$this->rowTemplate($this->table['incomestatement']['expense'])}</div>
				</div>
			</div>
		</form>
		HTML;

		$this->grem->getLast()->close();
		$this->grem->terminate();

		echo <<<HTML
			<div style="display: none;">
				<form id="appPopupAssign" style="display: none;">
		HTML;
		$grem       = new Gremium(false);
		$grem->base = "0px";
		$grem->header()->serve("<h1 style=\"padding-left:20px;\">Account assignement </h1>");
		$grem->article()->maxWidth("600px")->open();
		$hash = md5($this->app->id . $this->app->user->company->id);
		echo <<<HTML
			<div style="line-height: 1.8em">Select an account to assign to:<br /><b id="popupAssignTermHint"></b></div>
			<div class="btn-set" style="margin-top: 20px; margin-bottom: 10px">
				<input type="text" placeholder="Account name" data-slo=":LIST" title="Account name" data-source="_/CompanyAssosiatedAccounts/slo/{$hash}/slo_CompanyAssosiatedAccounts.a" class="flex" id="popAssignedAccount" />
			</div>
			<div><span style="display:none;color: var(--buttonred_active-bgcolor);" id="popInvalidMessage">Select a valid account!</span>&nbsp;</div><br />
			<span>
				<b>Notes:</b>
				<ul>
					<li>Assigning an account to multiple accounting terms is not allowed.</li>
					<li>Revoking an account assignment or an accounting term might cause instability in the finance system, consider assigning all applicable pairs prior to any financial operations in the system.</li>
					<li>Financial procedures and operations require certain terms to be set in order to work.</li>
				</ul>
			</span>
			<div style="margin-top:40px;" class="btn-set right">
				<button type="submit">Assign</button>
				<button type="button" data-role="previous" class="edge-right standard">Cancel</button>
			</div>
		HTML;
		$grem->getLast()->close();
		$grem->terminate();

		echo <<<HTML
				</form>
			</div>
		HTML;
	}

	public function post(array $request): void
	{
		header("Content-Type: application/json; charset=utf-8");

		$output = array("result" => true, "errno" => "0", "error" => "");
		if ((int) $request['company_id'] != $this->app->user->company->id) {
			$output['result'] = false;
			$output['errno']  = "1100";
			$output['error']  = "Company changed before saving assignements";
			echo json_encode($output);
			exit;
		}


		$this->app->db->autocommit(false);
		$output['result'] &= $this->app->db->execute_query("UPDATE acc_accounts SET prt_term = NULL WHERE prt_company_id = ?", [$this->app->user->company->id]);

		$stmt    = $this->app->db->prepare("UPDATE acc_accounts SET prt_term = ? WHERE prt_id = ?");
		$term_id = 0;
		$acct_id = 0;
		$stmt->bind_param("ii", $term_id, $acct_id);

		foreach ($request['term'] as $term => $accounts) {
			if (is_array($accounts)) {
				foreach ($accounts as $account) {
					$term_id          = (int) $term;
					$acct_id          = (int) $account;
					$output['result'] &= $stmt->execute();
				}
			}
			if (!$output['result'])
				break;
		}
		if ($output['result']) {
			$this->app->db->commit();
			$output['result'] = true;
		} else {
			$this->app->db->rollback();
			$output['result'] = false;
			$output['errno']  = "2100";
			$output['error']  = "Database error, saveing changes failed!";
		}
		$stmt->close();
		echo json_encode($output);
	}
}


$balanceSheetView = new BalanceSheetView($app);
if ($app->xhttp) {
	$balanceSheetView->post($_POST);
	exit;
}

$balanceSheetView->view();

?>
<script type="module">
	import { Popup } from './static/javascript/modules/gui/popup.js';
	class BalanceSheetManager {
		constructor(pana) {
			this.pop = null;
			this.busy = false;
			this.form = null;
			this.buttonSave = null;
			this.popInvalidMessage = null;
			this.popAssignedAccount = null;
			this.popAssignedTermHint = null;
			this.submitCaller = () => { };
			this.init();
		}
		
		accountExists(accountId) {
			let inputs = document.querySelectorAll(".-input-accounts");
			for (const el of inputs) {
				if (parseInt(el.value) === parseInt(accountId)) {
					return el;
				}
			}
			return false;
		}

		async post() {
			if (this.busy) {
				return;
			}
			this.busy = true;
			try {
				const formData = new FormData(this.form);
				overlay.show();
				let response = await fetch(this.form.action, {
					method: 'POST',
					mode: "cors",
					cache: "no-cache",
					credentials: "same-origin",
					referrerPolicy: "no-referrer",
					headers: {
						"Application-From": "same",
						"X-Requested-With": "fetch",
					},
					body: formData,
				});
				overlay.hide();

				if (response.ok) {
					const payload = await response.json();
					this.busy = false;
					if(payload.result){
						messagesys.success("New assignements updated successfully");
					}else{
						messagesys.failure(payield.error);
					}
				}
			} catch (error) {
				this.busy = false;
				messagesys.failure(error);
			}

		}

		init() {
			document.addEventListener('DOMContentLoaded', () => {
				this.pop = new Popup("appPopupAssign");
				this.form = document.getElementById("appForm");
				this.buttonSave = document.getElementById("appSaveButton");
				this.popInvalidMessage = document.getElementById("popInvalidMessage");
				this.popAssignedAccount = $("#popAssignedAccount").slo();
				this.popAssignedTermHint = document.getElementById("popupAssignTermHint");

				this.buttonSave.addEventListener("click", (e) => {
					this.post();
				});
				this.pop.addEventListener("submit", (e) => {
					this.insertAccount(
						e.detail.term_id,
						this.popAssignedAccount.get()[0]['id'],
						this.popAssignedAccount.get()[0]['value']
					);
				});

				document.querySelectorAll(".btn-removeTermAccount").forEach((elem) => {
					elem.addEventListener("click", function (e) {
						elem.parentNode.remove();
					});
				});

				document.querySelectorAll(".btn-addTermAccount").forEach((elem) => {
					elem.addEventListener("click", (e) => {
						this.popInvalidMessage.style.display = "none";
						this.popAssignedAccount.clear();
						this.popAssignedTermHint.innerText = elem.dataset.termtype + ": " + elem.dataset.termname;
						this.pop.show({ "term_id": elem.dataset.termid });
						this.popAssignedAccount.focus();
					});

				});
			}, false);
		}

		insertAccount(termId, accountId, accountName) {
			this.popInvalidMessage.style.display = "none";
			if (isNaN(accountId) || isNaN(parseInt(accountId)) || parseInt(accountId) <= 0) {
				this.popInvalidMessage.style.display = "inline";
				this.popInvalidMessage.innerText = "Select a valid account!";
				return;
			}
			let checkAccountExists = this.accountExists(accountId);
			if (checkAccountExists !== false) {
				this.pop.close();
				messagesys.failure("Account is already assigned");

				setTimeout(() => {
					checkAccountExists.parentNode.scrollIntoView({ behavior: "smooth", block: "center" });
					checkAccountExists.parentNode.addEventListener("animationend", (event) => { event.target.classList.remove("flash"); }, { once: true });
					checkAccountExists.parentNode.classList.add("flash");
				}, 100);

				return;
			}

			let container = document.querySelector(".termaccounts[data-termaccounts=\"" + termId + "\"]");
			if (container && parseInt(accountId) != 0) {
				let nitem = document.createElement("span");
				nitem.classList.add("btn-set")
				nitem.innerHTML =
					`<input type="hidden" class="-input-accounts" name="term[${termId}][]" value="${accountId}" />
					<span class="flex">${accountName}</span>
					<button type="button" class="standard error edge-left btn-removeTermAccount"></button>`;
				nitem.querySelector(".btn-removeTermAccount").addEventListener("click", function (e) {
					nitem.remove();
				});
				container.appendChild(nitem);
				this.pop.close();
			}
		}
	}

	new BalanceSheetManager();
</script>