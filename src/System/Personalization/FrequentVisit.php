<?php
declare(strict_types=1);
namespace System\Personalization;

class FrequentVisit extends Personalization
{
	public function __construct(protected \System\App $app, ?int $register_company = null)
	{
		$this->identifier = Identifiers::SystemFrequentVisit->value;
	}
}