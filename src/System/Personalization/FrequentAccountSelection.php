<?php
namespace System\Personalization;

class FrequentAccountSelection extends Personalization
{
	protected int $identifier = Identifiers::SystemCountAccountSelection->value;
	
	public function __construct(protected \System\App $app, ?int $register_account = null)
	{
		if ($register_account != null) {
			$this->register($register_account);
		}
	}
	
}