<?php
declare(strict_types=1);

namespace System\Views\CRM;

use System\App;
use System\Template\Gremium\Gremium;

class Customer extends \System\Views\PanelView
{
	public static int $perpage_val = 20;

	public function __construct(protected App &$app)
	{
		parent::__construct($app);
		//$this->var = $var;
		$this->pagesScope = array(
			173 => array(true, './crm/customer.js', "CustomList"),
			269 => array(true, './crm/customer.js', "CustomSearch"),
			267 => array(true, './crm/customer.js', "Entry"),
			270 => array(true, './crm/customer.js', "Post"),
		);
		$this->assets     = array(
			["css", "style/pagefile/crm-customer.css"],
			["css", "style/style.upload.css"],
			["js", "jquery/uploader-1.0.js"],
		);
	}


	public function render(): void
	{
		$fs = $this->app->file;

		echo $this->panelHtmlWrapOpen($this->pagesScope[$this->app->file->id][0]);
		$grem_panel       = new Gremium(true, true, false, "pana-Scroll");
		$grem_panel->base = "0px";
		$grem_panel->article("pana-Window")->options(array("nopadding"))->serve();
		$grem_panel->title("pana-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
		unset($grem_panel);
		echo $this->panelHtmlWrapClose();

		$_getJSON = json_encode($_GET);

		$pp = static::$perpage_val;
		echo <<<HTML
			<script type="module">
				import { PaNa } from './static/javascript/modules/panel-navigator.js';
				
				let pn = new PaNa();
				pn.scope = {$this->scopeToString()};
				pn.sidePanelUrl = "{$fs(266)->dir}";
				pn.onClickUrl = "{$fs(267)->dir}";
				pn.itemPerRequest = {$pp};
				pn.classList = ["crm-panel"];
				
				pn.onclick = function (event) {
					pn.register(pn.onClickUrl, {"id":event.dataset.listitem_id});
					pn.navigator.pushState();
					pn.run();
				}

				pn.listitemHandler = function (data) {
					let news = data.news>0 ? `<cite class=\"badge\">\${data.news}</cite>` : "";
					return `` +
						`<div data-crmlistItem="\${data.id}">`+
						`	<span style="flex: 1">` +
						`		<div><h1>\${data.name}</h1><cite> </cite><cite>\${data.id}</cite></div>` +
						`		<div><h1>\${data.payments}</h1>\${news}</div>` +
						`	</span>` +
						`</div>` +
						``;
						//`<div><h1 class=\"description\"></h1></div>`;
				}
				pn.init();
				pn.register("{$fs()->dir}", {$_getJSON});
				pn.navigator.stampState();
				pn.run(true);
				
			</script>
		HTML;
	}

}