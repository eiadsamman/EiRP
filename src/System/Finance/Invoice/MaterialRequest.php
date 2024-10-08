<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\Finance\Invoice\enums\Purchase;


class MaterialRequest extends Invoice
{
	public function __construct(protected \System\App &$app)
	{
		parent::__construct($app);
		$this->information->type     = Purchase::Request;
		$this->information->client = $this->app->user->company;
	}


}
