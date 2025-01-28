<?php
declare(strict_types=1);

namespace System\Routes;

use System\App;
use System\Layout\Gremium\Gremium;

class PageAssets
{
	public function __construct(public string $javascriptModuleClass, public bool $sidePanelVisible)
	{

	}
}
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

abstract class Routes
{
	public string $name = "";
	public string $title = "";
	public string $sidePanelUrl = "";
	public array $assets = [];
	public string $javascriptLib = "";
	public array $modules = [];

	private array $sharedAssets = [
		["css", "style/style.upload.css"],
		["js", "jquery/uploader-1.0.js"]
	];

	public function __construct(protected App &$app)
	{
	}


	public function render(): void
	{
		$fs = $this->app->file;
		if (array_key_exists($fs->id, $this->modules)) {
			echo "\n\t\t\t<div class=\"split-view\">\n";
			echo "\t\t\t\t<div class=\"panel entire" . ($this->modules[$fs->id]->sidePanelVisible ? "" : " hide") . "\" id=\"pana-Side\">\n";
			$grem_panel       = new Gremium(true, true, false, "pana-Scroll");
			$grem_panel->base = "0px";
			$grem_panel->header()->serve("<h1 id=\"pana-PanelTitle\">{$this->title}</h1>");
			$grem_panel->menu()->serve("<span class=\"flex\" id=\"pana-TotalRecords\"></span>");
			$grem_panel->article("pana-Window")->options(array("nopadding"))->serve();
			$grem_panel->title("pana-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
			$grem_panel->terminate();
			$tab = str_repeat("\t", 3);
			echo "\n$tab\t</div>\n";
			echo "$tab\t<div class=\"body\" id=\"pana-Body\"></div>";
			echo "\n\t\t\t</div>\n";
			$_getJSON = json_encode($_GET);
			/* JS Payload */
			echo <<<HTML
			{$tab}<script type="module">
				{$tab}import { PaNa } from './static/javascript/modules/PanelView.js';
				{$tab}let pn = new PaNa();
				{$tab}pn.itemPerRequest = {$this->app->user->recordsPerRequest};
				{$tab}pn.init("{$fs()->dir}", {$_getJSON});
				{$tab}pn.run(true);
			{$tab}</script>\n
			HTML;
		}
	}

	public function htmlAssets(string $version): void
	{
		echo "\n";
		foreach ($this->assets as $asset)
			echo "\t" . HTMLAssetsMap::print($asset[0], $asset[1], $version) . "\n";

		foreach ($this->sharedAssets as $asset)
			echo "\t" . HTMLAssetsMap::print($asset[0], $asset[1], $version) . "\n";
	}

	static public function groupBuildJSON(App &$app): string
	{
		$output  = [];
		$routers = [
			TransactionRoute::class,
			CustomerRoute::class,
			PurchaseRequestRoute::class,
			PurchaseQuotationRoute::class,
		];

		foreach ($routers as $route) {
			$instance                         = new $route($app);
			$output[$instance->name]          = [];
			$output[$instance->name]['title'] = $instance->title;
			$output[$instance->name]['url']   = $instance->sidePanelUrl;
			$output[$instance->name]['js']    = $instance->javascriptLib;

			$output[$instance->name]['assets'] = ['css' => [], 'js' => []];
			foreach ($instance->assets as $asset) {
				$output[$instance->name]['assets'][$asset[0]][] = $asset[1];
			}

			$output[$instance->name]['modules'] = [];

			
			foreach ($instance->modules as $moduleKey => $module) {
				$output[$instance->name]['modules'][$app->file->find($moduleKey)->dir] = [
					$moduleKey,
					$app->file->find($moduleKey)->title,
					$module->sidePanelVisible,
					$module->javascriptModuleClass,
				];
			}
		}
		return json_encode($output, JSON_PRETTY_PRINT);
	}
}
