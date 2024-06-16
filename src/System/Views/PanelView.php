<?php
declare(strict_types=1);

namespace System\Views;

use System\App;
use System\Template\Gremium\Gremium;
use System\Views\Views;

class HTMLWrapper
{
	public function __construct(public string $open = "", public string $close = "")
	{
	}
}

class PanelView implements Views
{
	protected HTMLWrapper $htmlWrapperSidePanel;
	public function __construct(protected App &$app)
	{
		$this->htmlWrapperSidePanel = new HTMLWrapper(
			"<div class=\"split-view\"><div class=\"panel entire\" id=\"PanelNavigator-Side\">",
			"</div><div class=\"body\" id=\"PanelNavigator-Body\"></div></div>"
		);
	}

	protected function contentPlaceHolder(): void
	{
		echo "<div id=\"PanelNavigator-LoadingScreen\">";
		$grem = new Gremium(true);
		$grem->header()->serve("<span class=\"loadingScreen-placeholder header\">&nbsp;</span>");
		$grem->menu()->serve("<span class=\"\">&nbsp;</span>");
		$grem->title()->serve("<span class=\"loadingScreen-placeholder title\">&nbsp;</span>");
		$grem->article()->serve("<span class=\"loadingScreen-placeholderBody\"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></span>");
		unset($grem);
		echo "</div>";
	}



	public function render(): void
	{
		echo "Fuck ";
	}
}
