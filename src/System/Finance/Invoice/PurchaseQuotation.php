<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\Finance\Invoice\enums\Purchase;


class PurchaseQuotation extends Invoice
{
	public function __construct(protected \System\App &$app)
	{
		parent::__construct($app);
		$this->information->type   = Purchase::Quotation;
		$this->information->client = $this->app->user->company;
	}


}
