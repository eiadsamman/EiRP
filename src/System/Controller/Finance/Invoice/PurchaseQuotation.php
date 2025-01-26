<?php
declare(strict_types=1);

namespace System\Controller\Finance\Invoice;
use System\Controller\Finance\Invoice\enums\Purchase;


class PurchaseQuotation extends Invoice
{
	public function __construct(protected \System\App &$app)
	{
		parent::__construct($app);
		$this->information->type   = Purchase::Quotation;
		$this->information->client = $this->app->user->company;
	}


}
