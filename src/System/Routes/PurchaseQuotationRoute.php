<?php
declare(strict_types=1);

namespace System\Routes;

use System\App;
use System\Routes\PageAssets;

class PurchaseQuotationRoute extends Routes
{
	public function __construct(protected App &$app)
	{
		$this->name          = "InvMaterialQuotation";
		$this->javascriptLib = './invoicing/MaterialQuotation.js';
		$this->title         = "Quotations";
		$this->sidePanelUrl  = $this->app->file->find(241)->dir;
		$this->assets        = [["css", "style/pagefile/Invoicing.css"]];
		$this->modules       = [
			233 => new PageAssets('Post', true),
			209 => new PageAssets('CustomList', true),
			234 => new PageAssets('Entry', true),
		];
	}
}