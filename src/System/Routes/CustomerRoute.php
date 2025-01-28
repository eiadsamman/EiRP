<?php
declare(strict_types=1);

namespace System\Routes;

use System\App;
use System\Routes\PageAssets;

class CustomerRoute extends Routes
{
	public function __construct(protected App &$app)
	{
		$this->name          = "Customer";
		$this->javascriptLib = './crm/customer.js';
		$this->title         = "Customers";
		$this->sidePanelUrl  = $this->app->file->find(266)->dir;
		$this->assets        = [["css", "style/pagefile/crm-customer.css"]];
		$this->modules       = [
			173 => new PageAssets('CustomList', true),
			269 => new PageAssets('CustomSearch', true),
			267 => new PageAssets('Entry', true),
			270 => new PageAssets('Post', true),
		];
	}
}