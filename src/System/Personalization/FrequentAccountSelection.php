<?php
namespace System\Personalization;

class FrequentAccountSelection extends Personalization
{
	public function __construct(protected \System\App $app, ?int $register_account = null)
	{
		$this->identifier = Identifiers::SystemCountAccountSelection->value;
		if ($register_account != null) {
			$this->register($register_account);
		}
	}

}