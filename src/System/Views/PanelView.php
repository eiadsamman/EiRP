<?php
declare(strict_types=1);

namespace System\Views;

use System\App;
use System\Views\Views;

class PanelView implements Views
{
	protected array $pagesScope;
	public function __construct(protected App &$app)
	{
		/** 
		 * Pages Scrop
		 * array of pages ID => array(bool panelVisible true|false, string JS module)
		 *  */
		$this->pagesScope = array();
	}

	protected function panelHtmlWrapOpen(bool $visible = true): string
	{
		return "<div class=\"split-view\"><div class=\"panel entire " . ($visible ? "" : " hide") . "\" id=\"PanelNavigator-Side\">";
	}
	protected function panelHtmlWrapClose(): string
	{
		return "</div><div class=\"body\" id=\"PanelNavigator-Body\"></div></div>";
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
			$placeholder = ($attributes[3] != null ? "\"$attributes[3]\"" : "null");
			$output .= $smart . "\"{$this->app->file->find($page)->dir}\": {\"title\": \"{$this->app->file->find($page)->title}\",\"import\": $import,\"side\": $side,\"module\": $module,\"placeholder\": $placeholder}";
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
		echo "Fuck ";
	}
}
