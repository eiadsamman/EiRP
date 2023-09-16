<?php
namespace System\Attachment;

class SubScope
{
	private array $subs = array();
	public function __set(string $name, int $value)
	{
		$this->subs[$name] = $value;
	}

	public function __get(string $name): int
	{
		if (array_key_exists($name, $this->subs)) {
			return $this->subs[$name];
		}
		return 0;
	}
}

class Scope
{
	public SubScope $individual;
	public SubScope $finance;
	public SubScope $company;
	public SubScope $goods;

	public function __construct()
	{
		$this->individual = new SubScope();
		$this->individual->social_id = 190;
		$this->individual->portrait = 189;

		$this->finance = new SubScope();
		$this->finance->transation_evidence = 188;

		$this->company = new SubScope();
		$this->company->logo = 242;

		$this->goods = new SubScope();
		$this->goods->photo = 243;
	}
}