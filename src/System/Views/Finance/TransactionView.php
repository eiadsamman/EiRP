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
			91 => array(true, './finance/transaction.js', "Post"),
			95 => array(true, './finance/transaction.js', "Post"),
			101 => array(true, './finance/transaction.js', "Post"),
			104 => array(true, './finance/transaction.js', "Entry"),
			170 => array(true, './finance/transaction.js', "CustomSearch"),
			214 => array(true, './app.js', "List")
		);
		$this->assets     = array(
			["css", "style/pagefile/statement-control.css"],
			["css", "style/pagefile/TransactionView.css"],
			["css", "style/style.upload.css"],
			["js", "jquery/uploader-1.0.js"],
		);
	}


	public function render(): void
	{
		$fs       = $this->app->file;
		$imageUri = $this->app->file->find(187)->dir;

		echo $this->panelHtmlWrapOpen($this->pagesScope[$this->app->file->id][0]);
		$grem_panel       = new Gremium(true, true, false, "pana-Scroll");
		$grem_panel->base = "0px";
		$grem_panel->header()->serve("<h1>Statements</h1>");
		$grem_panel->menu()->serve("<span class=\"flex\" id=\"pana-TotalRecords\"></span>");
		$grem_panel->article("pana-Window")->options(array("nopadding"))->serve();
		$grem_panel->title("pana-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
		unset($grem_panel);
		echo $this->panelHtmlWrapClose();

		$_getJSON = json_encode($_GET);
		//import Account from './static/javascript/modules/finance/account.js';
		
		/* JS Payload */
		echo <<<HTML
			<script type="module">
				import { PaNa } from './static/javascript/modules/panel-navigator.js';
				
				let pn = new PaNa();
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

}