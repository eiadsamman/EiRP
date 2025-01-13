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
	private array $sharedAssets;

	public function __construct(protected App &$app)
	{
		$this->panelGroups  = [];
		$this->sharedAssets = [
			["css", "style/style.upload.css"],
			["js", "jquery/uploader-1.0.js"]
		];







		/* Transaction */
		$panel                = new PanelGroup();
		$panel->name          = "Transaction";
		$panel->javascriptLib = './finance/transaction.js';
		$panel->title         = "Statements";
		$panel->sidePanelUrl  = $this->app->file->find(121)->dir;
		$panel->pages         = [91, 95, 101, 104, 170, 214];
		$panel->assets        = [["css", "style/pagefile/statement-control.css"], ["css", "style/pagefile/TransactionView.css"]];
		$panel->modules       = [
			new PageAssets(91, 'Post', true),
			new PageAssets(95, 'Post', true),
			new PageAssets(101, 'Post', true),
			new PageAssets(104, 'Entry', true),
			new PageAssets(170, 'CustomSearch', true),
			new PageAssets(214, 'CustomList', true),
		];
		$this->panelGroups[]  = $panel;




		/* CRM */
		$panel                = new PanelGroup();
		$panel->name          = "Customer";
		$panel->javascriptLib = './crm/customer.js';
		$panel->title         = "Customers";
		$panel->sidePanelUrl  = $this->app->file->find(266)->dir;
		$panel->pages         = [173, 269, 267, 270];
		$panel->assets        = [["css", "style/pagefile/crm-customer.css"]];
		$panel->modules       = [
			new PageAssets(173, 'CustomList', true),
			new PageAssets(269, 'CustomSearch', true),
			new PageAssets(267, 'Entry', true),
			new PageAssets(270, 'Post', true),
		];
		$this->panelGroups[]  = $panel;




		/* Invoicing - Material Request */
		$panel                = new PanelGroup();
		$panel->name          = "InvMaterialRequest";
		$panel->javascriptLib = './invoicing/MaterialRequest.js';
		$panel->title         = "Requests";
		$panel->sidePanelUrl  = $this->app->file->find(238)->dir;
		$panel->pages         = [210, 230, 240];
		$panel->assets        = [["css", "style/pagefile/Invoicing.css"]];
		$panel->modules       = [
			new PageAssets(230, 'Post', true),
			new PageAssets(210, 'CustomList', true),
			new PageAssets(240, 'Entry', true),
		];
		$this->panelGroups[]  = $panel;




		/* Invoicing - Material Request Quotation */
		$panel                = new PanelGroup();
		$panel->name          = "InvMaterialQuotation";
		$panel->javascriptLib = './invoicing/MaterialQuotation.js';
		$panel->title         = "Quotations";
		$panel->sidePanelUrl  = $this->app->file->find(241)->dir;
		$panel->pages         = [209, 233, 234, 271];
		$panel->assets        = [["css", "style/pagefile/Invoicing.css"]];
		$panel->modules       = [
			new PageAssets(233, 'Post', true),
			new PageAssets(209, 'CustomList', true),
			new PageAssets(234, 'Entry', true),
			new PageAssets(271, 'CustomSearch', true),
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

		foreach ($this->sharedAssets as $asset) {
			echo "\t" . HTMLAssetsMap::print($asset[0], $asset[1], $version) . "\n";
		}
	}

	public function groupBuildJSON(): string
	{
		$output = [];
		foreach ($this->panelGroups as $group) {
			$output[$group->name]          = [];
			$output[$group->name]['title'] = $group->title;
			$output[$group->name]['url']   = $group->sidePanelUrl;
			$output[$group->name]['js']    = $group->javascriptLib;

			$output[$group->name]['assets'] = ['css' => [], 'js' => []];
			foreach ($group->assets as $asset) {
				$output[$group->name]['assets'][$asset[0]][] = $asset[1];
			}

			$output[$group->name]['modules'] = [];
			foreach ($group->modules as $module) {
				$output[$group->name]['modules'][$this->app->file->find($module->id)->dir] = [
					$module->id,
					$this->app->file->find($module->id)->title,
					$module->sidePanelVisible,
					$module->javascriptModuleClass,
				];
			}
		}
		return json_encode($output, JSON_PRETTY_PRINT);
	}

	public function render(): void
	{
		$fs          = $this->app->file;
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
		$tab = str_repeat("\t", 5);
		if ($panelModule) {
			echo "\n\t\t\t<div class=\"split-view\">\n";
			echo "\t\t\t\t<div class=\"panel entire" . ($panelModule->sidePanelVisible ? "" : " hide") . "\" id=\"pana-Side\">\n";
			$grem_panel       = new Gremium(true, true, false, "pana-Scroll");
			$grem_panel->base = "0px";
			$grem_panel->header()->serve("<h1 id=\"pana-PanelTitle\">$panelGroup->title</h1>");
			$grem_panel->menu()->serve("<span class=\"flex\" id=\"pana-TotalRecords\"></span>");
			$grem_panel->article("pana-Window")->options(array("nopadding"))->serve();
			$grem_panel->title("pana-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
			$grem_panel->terminate();
			$tab = str_repeat("\t", 3);
			echo "\n$tab\t</div>\n";
			echo "$tab\t<div class=\"body\" id=\"pana-Body\"></div>";
			echo "\n\t\t\t</div>\n";
			$_getJSON = json_encode($_GET);
			$perpage  = static::$itemsPerRequest;
			/* JS Payload */
			echo <<<HTML
			{$tab}<script type="module">
				{$tab}import { PaNa } from './static/javascript/modules/PanelView.js';
				{$tab}let pn = new PaNa();
				{$tab}pn.itemPerRequest = {$perpage};
				{$tab}pn.init("{$fs()->dir}", {$_getJSON});
				{$tab}pn.run(true);
			{$tab}</script>\n
			HTML;
		}
	}
}
