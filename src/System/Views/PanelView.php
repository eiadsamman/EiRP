<?php
declare(strict_types=1);

namespace System\Views;

use System\App;
use System\Template\Gremium\Gremium;
use System\Views\Views;


abstract class HTMLAssetsMap
{
	private static $base = "static/";
	public static function print(string $type, string $url, string $version): string
	{
		$type = strtolower($type);
		return match ($type) {
			'css' => "<link media=\"screen,print\" rel=\"stylesheet\" href=\"" . HTMLAssetsMap::$base . "{$url}{$version}\" />",
			'js' => "<script type=\"text/javascript\" src=\"" . HTMLAssetsMap::$base . "{$url}{$version}\"></script>"
		};
	}
}

class PanelGroup
{
	public string $name;
	public string $title;
	public string $sidePanelUrl;
	public string $itemCSS;
	public array $assets;
	public array $pages;
	public string $javascriptLib;
	public array $modules;
	public function __construct(
	) {
	}


}
class PageAssets
{
	public function __construct(
		public int $id,
		public string $javascriptModuleClass,
		public bool $sidePanelVisible
	) {

	}
}

class PanelView implements Views
{
	public static int $itemsPerRequest = 20;
	private array $panelGroups;

	public function __construct(protected App &$app)
	{

		$this->panelGroups = [];

		$panel                = new PanelGroup();
		$panel->name          = "Transaction";
		$panel->javascriptLib = './finance/transaction.js';
		$panel->title         = "Statements";
		$panel->itemCSS       = "statment-panel";
		$panel->sidePanelUrl  = $this->app->file->find(121)->dir;
		$panel->pages         = [91, 95, 101, 104, 170, 214];
		$panel->assets        = [
			["css", "style/pagefile/statement-control.css"],
			["css", "style/pagefile/TransactionView.css"],
			["css", "style/style.upload.css"],
			["js", "jquery/uploader-1.0.js"]
		];
		$panel->modules       = [
			new PageAssets(91, 'Post', true),
			new PageAssets(95, 'Post', true),
			new PageAssets(101, 'Post', true),
			new PageAssets(104, 'Entry', true),
			new PageAssets(170, 'CustomSearch', true),
			new PageAssets(214, 'CustomList', true),
		];
		$this->panelGroups[]  = $panel;


		$panel                = new PanelGroup();
		$panel->name          = "Customer";
		$panel->javascriptLib = './crm/customer.js';
		$panel->title         = "Customers";
		$panel->itemCSS       = "";
		$panel->sidePanelUrl  = $this->app->file->find(266)->dir;
		$panel->pages         = [173, 269, 267, 270];
		$panel->assets        = [
			["css", "style/pagefile/crm-customer.css"],
			["css", "style/style.upload.css"],
			["js", "jquery/uploader-1.0.js"],
		];
		$panel->modules       = [
			new PageAssets(173, 'CustomList', true),
			new PageAssets(269, 'CustomSearch', true),
			new PageAssets(267, 'Entry', true),
			new PageAssets(270, 'Post', true),
		];
		$this->panelGroups[]  = $panel;

	}

	public function htmlAssets(string $version = ""): void
	{
		echo "\n";
		foreach ($this->panelGroups as $group) {
			if (in_array($this->app->file->id, $group->pages)) {
				foreach ($group->assets as $asset) {
					echo "\t" . HTMLAssetsMap::print($asset[0], $asset[1], $version) . "\n";
				}
			}
		}
	}
	protected function groupBuildJSON(): string
	{
		$output = [];
		foreach ($this->panelGroups as $group) {
			$output[$group->name]            = [];
			$output[$group->name]['title']   = $group->title;
			$output[$group->name]['url']     = $group->sidePanelUrl;
			$output[$group->name]['js']      = $group->javascriptLib;
			$output[$group->name]['modules'] = [];
			foreach ($group->modules as $module) {
				$output[$group->name]['modules'][$this->app->file->find($module->id)->dir] = [
					$module->id,
					$module->sidePanelVisible,
					$module->javascriptModuleClass,
				];
			}
		}
		return json_encode($output);
	}


	public function render(): void
	{
		
		$fs = $this->app->file;

		$panelGroup  = null;
		$panelModule = null;
		foreach ($this->panelGroups as $group) {
			if (in_array($this->app->file->id, $group->pages)) {
				$panelGroup = $group;
				foreach ($group->modules as $module) {
					if ($module->id == $this->app->file->id) {
						$panelModule = $module;
					}
				}
			}
		}
		if ($panelModule) {

			echo "<div class=\"split-view\"><div class=\"panel entire " . ($panelModule->sidePanelVisible ? "" : " hide") . "\" id=\"pana-Side\">";
			$grem_panel       = new Gremium(true, true, false, "pana-Scroll");
			$grem_panel->base = "0px";
			$grem_panel->header()->serve("<h1>$panelGroup->title</h1>");
			$grem_panel->menu()->serve("<span class=\"flex\" id=\"pana-TotalRecords\"></span>");
			$grem_panel->article("pana-Window")->options(array("nopadding"))->serve();
			$grem_panel->title("pana-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
			$grem_panel->terminate();
			unset($grem_panel);
			echo "</div>";

			echo "<div class=\"body\" id=\"pana-Body\"></div></div>";

			$_getJSON = json_encode($_GET);
			$perpage = static::$itemsPerRequest;
			/* JS Payload */
			echo <<<HTML
			<script type="module">
				import { PaNa } from './static/javascript/modules/panel-navigator.js';
				
				let pn = new PaNa();
				pn.scope= {$this->groupBuildJSON()};
				pn.itemPerRequest = {$perpage};
				pn.classList = "{$panelGroup->itemCSS}";
				pn.init("{$fs()->dir}", {$_getJSON});
				pn.run(true);

			</script>
			HTML;
		}
	}
}
