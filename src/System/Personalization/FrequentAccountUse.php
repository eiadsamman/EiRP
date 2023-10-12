<?php
namespace System\Personalization;

class FrequentAccountUse extends Personalization
{
	protected int $identifier = Identifiers::SystemCountAccountOperation->value;

	public function __construct(protected \System\App $app, ?int $register_account = null)
	{
		if ($register_account != null) {
			$this->register($register_account);
		}
	}
	
}