<?php

namespace System\Template\PanelNavigator;

use System\App;
use System\Template\Gremium\Gremium;



class PanelStatements extends PanelNavigator
{


	public function SidePanelHTML()
	{
		$grem_panel = new Gremium(true, true, false, "PanelNavigator-Scroll");
		$grem_panel->base = "0px";
		$grem_panel->header()->serve("<h1>Statements</h1>");
		//$grem_panel->menu()->serve("<span class=\"flex\" id=\"PanelNavigator-TotalRecords\"></span>");
		//<input class=\"edge-left\" type=\"button\" value=\"Search\" /><button id=\"js-input_btunew\">New</button>
		$grem_panel->article("PanelNavigator-Window")->options(array("nopadding"))->serve();
		$grem_panel->title("PanelNavigator-Informative")->serve("<div style=\"text-align:center;font-size:0.8em\">No more records</div>");
		unset($grem_panel);
	}

}