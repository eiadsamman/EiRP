<?php
namespace System\Personalization;

class FrequentCompanySelection extends Personalization
{
	protected int $identifier = Identifiers::SystemCountCompanySelection->value;

	public function __construct(protected \System\App $app, ?int $register_company = null)
	{
		if ($register_company != null) {
			$this->register($register_company);
		}
	}
	
}