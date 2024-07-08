<?php
declare(strict_types=1);

namespace System\Views\Finance;

use System\App;
use System\Template\Gremium\Gremium;

class TransactionView extends \System\Views\PanelView
{
	private int $perpage_val = 20;

	public function __construct(protected App &$app)
	{
		parent::__construct($app);
		//$this->var = $var;
		$this->pagesScope = array(
			91 => array(true, './finance/transaction.js', "Transaction", "ph_receipt"),
			95 => array(true, './finance/transaction.js', "Transaction", "ph_payment"),
			101 => array(true, './finance/transaction.js', "Transaction", "ph_edit"),
			104 => array(true, './finance/transaction.js', "StatementView", "ph_view"),
			214 => array(true, './finance/transaction.js', "Ledger", "ph_list")
		);
	}
	public function render(): void
	{
		$passedId        = !empty($_GET['id']) ? (int) $_GET['id'] : "null";
		$fs              = $this->app->file;
		$attachementType = \System\Attachment\Type::FinanceRecord->value;
		$imageUri        = $this->app->file->find(187)->dir;

		echo $this->panelHtmlWrapOpen($this->pagesScope[$this->app->file->id][0]);
		$grem_panel       = new Gremium(true, true, false, "PanelNavigator-Scroll");
		$grem_panel->base = "0px";
		$grem_panel->header()->serve("<h1>Statements</h1>");
		$grem_panel->menu()->serve("<span class=\"flex\" id=\"PanelNavigator-TotalRecords\"></span>");
		$grem_panel->article("PanelNavigator-Window")->options(array("nopadding"))->serve();
		$grem_panel->title("PanelNavigator-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
		unset($grem_panel);
		echo $this->panelHtmlWrapClose();


		$this->contentPlaceHolder();
		$_getJSON = json_encode($_GET);

		/* JS Payload */
		echo <<<HTML
			
			<script type="module">
				import { PanelNavigator } from './static/javascript/modules/panel-navigator.js';
				import Account from './static/javascript/modules/finance/account.js';
				
				let pn = new PanelNavigator();
				pn.scope = {$this->scopeToString()};
				pn.sidePanelUrl = "{$fs(121)->dir}";
				pn.onClickUrl = "{$fs(104)->dir}";
				pn.itemPerRequest = {$this->perpage_val};
				pn.classList = ["statment-panel"];
				
				pn.onclick = function (event) {
					pn.register(pn.onClickUrl, {"id":event.dataset.listitem_id});
					pn.navigator.pushState();
					pn.run();
				}

				pn.listitemHandler = function (data) {
					let statementTypeIcon = data.positive ? `<span class="stm inc active"></span>` : `<span class="stm pay active"></span>`;
					let lockIcon = `<span class="stt chk"></span>`;
					let attachments = parseInt(data.attachements) > 0 ? `<span class="atch"></span>` : "";
					let badgeType = parseInt(data.padge_id)  ? "image" : "initials";
					let badgeURI = parseInt(data.padge_id) ? `<span style="background-image:url('{$imageUri}/?id=\${data.padge_id}&pr=t');"></span>` : `<b style="background-color:\${data.padge_color}">\${data.padge_initials}</b>`;
					return `` +
						`<div>`+
						`	<span style="flex: 1">` +
						`		<div><h1>\${data.beneficial}</h1><cite>\${attachments}\</cite><cite>\${data.id}</cite></div>` +
						`		<div><cite>\${statementTypeIcon}</cite><h1>\${data.value}</h1><cite>\${data.date}</cite></div>` +
						`		<div><h1>\${data.category}</h1></div>` +
						`	</span>` +
						`	<i class="padge \${badgeType}">\${badgeURI}</i>` + 
						`</div>` +
						`<div><h1 class=\"description\">\${data.details}</h1></div>`;
				}
				pn.init();
				pn.register("{$fs()->dir}", {$_getJSON});
				pn.navigator.stampState();
				pn.run();
				
			</script>
		HTML;
	}
	protected function contentPlaceHolder(): void
	{
		echo "<div style=\"display: none;\">";


		echo "<div id=\"ph_receipt\">";
		$grem = new Gremium(true);
		$grem->header()->prev("disabled style=\"pointer-events: none;\"")->serve("<h1>{$this->app->file->find(91)->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"plus\" id=\"js-input_submit\" disabled tabindex=\"9\">&nbsp;Submit Receipt</button></div>");
		$grem->menu()->serve("<span>&nbsp;</span>");
		$grem->title()->serve("<span class=\"flex\">Transaction details</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		echo "</div>";


		echo "<div id=\"ph_payment\">";
		$grem = new Gremium(true);
		$grem->header()->prev("disabled style=\"pointer-events: none;\"")->serve("<h1>{$this->app->file->find(95)->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"plus\" id=\"js-input_submit\" disabled tabindex=\"9\">&nbsp;Submit Receipt</button></div>");
		$grem->menu()->serve("<span>&nbsp;</span>");
		$grem->title()->serve("<span class=\"flex\">Transaction details</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		echo "</div>";

		echo "<div id=\"ph_view\">";
		$grem = new Gremium(true);
		$grem->header()->prev("disabled style=\"pointer-events: none;\"")->serve("<h1>{$this->app->file->find(104)->title}</h1>");
		$grem->menu()->serve("<span>&nbsp;</span>");
		$grem->title()->serve("<span class=\"flex\">Statement details</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		echo "</div>";



		echo "<div id=\"ph_edit\">";
		$grem  = new Gremium(true);
		$preva = " disabled style=\"pointer-events: none;\" ";
		$grem->header()->prev($preva)->serve("<h1>{$this->app->file->find(101)->title}</h1><cite></cite>");
		$grem->menu()->serve("<span>&nbsp;</span>");
		$grem->title()->serve("<span class=\"flex\">Transaction details</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		echo "</div>";



		echo "<div id=\"ph_list\">";
		$grem = new Gremium($this->pagesScope[214][0]);
		$grem->header()->serve("<h1>{$this->app->file->find(214)->title}</h1>" .
			"<ul class=\"small-media-hide\"><li>{$this->app->user->account->type->keyTerm->toString()}: {$this->app->user->account->name}</li></ul>" .
			"<cite><span id=\"js-output-total\">" . number_format($this->app->user->account->balance, 2) . "</span>{$this->app->user->account->currency->shortname}</cite>");
		$grem->menu()->serve("<span>&nbsp;</span>");

		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		echo "</div>";




		echo "</div>";

	}
	private function trash(): string
	{
		$fs = $this->app->file;
		return "";
	}
}