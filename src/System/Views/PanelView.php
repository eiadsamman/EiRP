<?php
declare(strict_types=1);

namespace System\Views;

use System\App;
use System\Views\Views;


class HTMLAssetsMap
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

class PanelView implements Views
{
	protected array $pagesScope;

	protected array $assets;

	public function __construct(protected App &$app)
	{
		/** 
		 * Pages Scrop
		 * array of pages ID => array(bool panelVisible true|false, string JS module)
		 *  */
		$this->pagesScope = array();
	}

	public function htmlAssets(string $version = ""): void
	{
		echo "\n";
		foreach ($this->assets as $asset) {
			echo "\t" . HTMLAssetsMap::print($asset[0], $asset[1], $version) . "\n";
		}
	}

	protected function panelHtmlWrapOpen(bool $visible = true): string
	{
		return "<div class=\"split-view\"><div class=\"panel entire " . ($visible ? "" : " hide") . "\" id=\"pana-Side\">";
	}
	protected function panelHtmlWrapClose(): string
	{
		return "</div><div class=\"body\" id=\"pana-Body\"></div></div>";
	}

	protected function scopeToString(): string
	{
		$output = "";
		$output .= "{";
		$smart  = "";
		foreach ($this->pagesScope as $page => $attributes) {
			$side        = ($attributes[0] ? "true" : "false");
			$import      = ($attributes[1] != null ? "\"$attributes[1]\"" : "null");
			$module      = ($attributes[2] != null ? "\"$attributes[2]\"" : "null");
			$output .= $smart . "\"{$this->app->file->find($page)->dir}\": {\"title\": \"{$this->app->file->find($page)->title}\",\"import\": $import,\"side\": $side,\"module\": $module}";
			$smart       = ", ";
		}
		$output .= "}";
		return $output;
	}
	protected function contentPlaceHolder(): void
	{

	}

	public function render(): void
	{
	}
}
