<?php
declare(strict_types=1);

namespace System\Views\Finance;

use System\App;
use System\Template\Gremium\Gremium;

class TransactionView extends \System\Views\PanelView
{
	private int $perpage_val = 20;

	public function render(): void
	{
		$passedId        = !empty($_GET['id']) ? (int) $_GET['id'] : "null";
		$fs              = $this->app->fileSystem;
		$attachementType = \System\Attachment\Type::FinanceRecord->value;


		echo $this->htmlWrapperSidePanel->open;
		$grem_panel       = new Gremium(true, true, false, "PanelNavigator-Scroll");
		$grem_panel->base = "0px";
		$grem_panel->header()->serve("<h1>Statements</h1>");
		$grem_panel->menu()->serve(
			"<span class=\"flex\" id=\"PanelNavigator-TotalRecords\"></span>"
			//."<input class=\"edge-left\" type=\"button\" value=\"Search\" /><button id=\"js-input_btunew\">New</button>"
		);
		$grem_panel->article("PanelNavigator-Window")->options(array("nopadding"))->serve();
		$grem_panel->title("PanelNavigator-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
		unset($grem_panel);
		echo $this->htmlWrapperSidePanel->close;

		$this->contentPlaceHolder();

		/* JS Payload */
		echo <<<HTML
			<script type="text/javascript">
				let pageConfig = {
					url: '{$fs()->dir}',
					apptitle : '{$this->app->settings->site['title']}',
					title: '{$this->app->settings->site['title']} - {$fs()->title}',
					id: {$passedId},
					upload: {
						url: '{$fs->find(186)->dir}',
						identifier: {$attachementType}
					}
				}
			</script>
			<script type="module">
				import { PanelNavigator } from './static/javascript/modules/panel-navigator.js';
				import Transaction from './static/javascript/modules/finance/transaction.js';
				import Account from './static/javascript/modules/finance/account.js';

				let pn = new PanelNavigator();
				pn.sourceUrl = "{$fs(121)->dir}";
				pn.onClickUrl = "{$fs(104)->dir}";
				pn.itemPerRequest = {$this->perpage_val};
				pn.classList = ["statment-panel"];
				pn.entityModule = new Transaction(pn);
				
				pn.onclick = function (event) {
					pn.navigator.setProperty("id", event.dataset.listitem_id);
					pn.navigator.history_vars.url = pn.onClickUrl;
					pn.navigator.history_vars.title = '{$this->app->settings->site['title']} - {$fs(104)->title}';
					pn.navigator.url = pn.onClickUrl;
					pn.contentLoader(pn.navigator.history_vars.url, pn.navigator.history_vars.title, { "id": event.dataset.listitem_id });
					pn.navigator.pushState();
				}

				pn.listitemHandler = function (data) {
					let statementTypeIcon = data.positive ? `<span class="stm inc active"></span>` : `<span class="stm pay active"></span>`;
					let lockIcon = `<span class="stt chk"></span>`;
					let attachments = parseInt(data.attachements) > 0 ? `<span class="atch"></span>` : "";
					return `<div><h1>\${data.beneficial}</h1><cite>\${data.id}</cite></div>` +
						`<div><h1>\${data.value}</h1><cite>\${data.date}</cite></div>` +
						`<div><h1>\${data.category}</h1><cite>\${attachments}\${statementTypeIcon}</cite></div>` +
						`<div><h1 class=\"description\">\${data.details}</h1></div>`;
				}
				pn.init();

				pn.contentLoader("{$fs()->dir}", null, { "id": {$passedId} });
				{$this->trash()}
			</script>
		HTML;
	}
	private function trash(): string
	{
		$fs = $this->app->fileSystem;
		return <<<HTML
		//<script>
		if (document.getElementById("js-input_btunew")){
			document.getElementById("js-input_btunew").addEventListener("click", function () {
				pn.prependItem({
						"attachements": 1,
						"beneficial": "نقدي",
						"category": "رواتب: رواتب",
						"date": "2024-01-25",
						"details": "Test",
						"id": 7203,
						"positive": true,
						"value": "(1,000.00)"
					})
				
				/* pn.clearActiveItem();
				pn.navigator.setProperty("id", null);
				pn.navigator.history_vars.url = '{$fs(91)->dir}';
				pn.navigator.history_vars.title = '{$this->app->settings->site['title']} - {$fs(91)->title}';
				pn.navigator.url = '{$fs(91)->dir}';
				pn.contentLoader(pn.navigator.history_vars.url, pn.navigator.history_vars.title, { "id": null });
				pn.navigator.pushState();
				 */
			})
		};
		//</script>
		HTML;
	}
}