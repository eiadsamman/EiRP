<?php

declare(strict_types=1);

namespace System\Template;



class Side extends Config
{
	public function Header($content): void
	{
		echo $this->HeaderStart() . $content . $this->HeaderEnd();
	}

	public function HeaderStart(): string
	{
		return "<div class=\"template-sidePanelRow\"><div id=\"template-sidePanelTitle\">";
	}
	public function HeaderEnd(): string
	{
		return "</div></div>";
	}



	public function Body($content): void
	{
		echo $this->BodyStart() . $content . $this->BodyEnd();
	}

	public function BodyStart(): string
	{
		return '<div class="template-sidePanelRow">
					<div id="template-sidePanelContent">
						<span id="template-sideScrollbar"></span>
						<div id="template-sidePanelContentScrollable">
							<div id="template-sidePanelGroupItems">';
	}
	public function BodyEnd(): string
	{
		return "			</div>
						</div>
					</div>
				</div>";
	}
}
