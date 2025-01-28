<?php
declare(strict_types=1);

namespace System\Routes;

use System\App;
use System\Routes\PageAssets;

class PurchaseRequestRoute extends Routes
{
	public function __construct(protected App &$app)
	{
		$this->name          = "InvMaterialRequest";
		$this->javascriptLib = './invoicing/MaterialRequest.js';
		$this->title         = "Requests";
		$this->sidePanelUrl  = $this->app->file->find(238)->dir;
		$this->assets        = [["css", "style/pagefile/Invoicing.css"]];
		$this->modules       = [
			230 => new PageAssets('Post', true),
			210 => new PageAssets('CustomList', true),
			240 => new PageAssets('Entry', true),
		];
	}
}