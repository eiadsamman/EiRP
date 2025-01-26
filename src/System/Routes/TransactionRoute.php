<?php
declare(strict_types=1);

namespace System\Routes;


use System\App;
use System\Routes\PageAssets;

class TransactionRoute extends Routes
{
	public function __construct(protected App &$app)
	{
		$this->name          = "Transaction";
		$this->javascriptLib = './finance/transaction.js';
		$this->title         = "Statements";
		$this->sidePanelUrl  = $this->app->file->find(121)->dir;
		//$this->pages         = [91, 95, 101, 104, 170, 214];
		$this->assets  = [["css", "style/pagefile/statement-control.css"], ["css", "style/pagefile/TransactionView.css"]];
		$this->modules = [
			91 => new PageAssets('Post', true),
			95 => new PageAssets('Post', true),
			101 => new PageAssets('Post', true),
			104 => new PageAssets('Entry', true),
			170 => new PageAssets('CustomSearch', true),
			214 => new PageAssets('CustomList', true),
		];
	}

}
