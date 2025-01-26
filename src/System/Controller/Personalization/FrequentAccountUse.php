<?php
namespace System\Controller\Personalization;

class FrequentAccountUse extends Personalization
{

	public function __construct(protected \System\App $app, ?int $register_account = null)
	{
		$this->identifier = Identifiers::SystemCountAccountOperation->value;
		if ($register_account != null) {
			$this->register($register_account);
		}
	}

}