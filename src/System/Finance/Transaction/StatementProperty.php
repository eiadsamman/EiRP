<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Finance\Account;
use System\Finance\Currency;
use System\Finance\Forex;
use System\Individual\PersonData;
use System\Individual\User;


class StatementCategoryProperty
{
	public function __construct(public int $id, public string $group,public string $name)
	{
	}
}


class StatementProperty
{
	public Account $creditor;
	public Account $debitor;

	public int $id;
	public StatementCategoryProperty $category;
	public float $value;
	public Currency $currency;

	public \DateTime $dateTime;
	public ?string $beneficiary;
	public ?string $description;
	public ?string $reference;
	public Nature $type;

	public ?int $relation;
	public ?PersonData $individual;
	
	public PersonData $editor;


	public array $attachments;

	public bool $canceled;

	public function __construct()
	{

	}

	public function __toString(): string
	{
		return print_r([
			"Issuer Account" => $this->issuer_account->id . ": " . $this->issuer_account->currency->shortname . " " . $this->issuer_account->name,
			"Target Account" => $this->target_account->id . ": " . $this->target_account->currency->shortname . " " . $this->target_account->name,
			"Date" => $this->dateTime->format("Y-m-d"),
			"Beneficiary" => $this->beneficiary,
			"Value" => $this->value,
			"Category" => $this->category->id . ": " . $this->category->name,
			"Description" => $this->description,
			"Individual" => "(" . gettype($this->individual) . ") " . $this->individual,
			"Reference" => "(" . gettype($this->reference) . ") " . $this->reference,
			"Relation" => "(" . gettype($this->relation) . ") " . $this->relation,
			"Attachments" => $this->attachments,
		], true);
	}
}