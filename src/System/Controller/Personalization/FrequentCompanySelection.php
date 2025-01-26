<?php
namespace System\Controller\Personalization;

class FrequentCompanySelection extends Personalization
{

	public function __construct(protected \System\App $app, ?int $register_company = null)
	{
		$this->identifier = Identifiers::SystemCountCompanySelection->value;
		if ($register_company != null) {
			$this->register($register_company);
		}
	}

}