<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Finance\Account;
use System\Finance\Currency;
use System\Profiles\AccountProfile;
use System\Profiles\IndividualProfile;


class StatementCategoryProperty
{
	public function __construct(public int $id, public string $group, public string $name)
	{
	}
}


class StatementProperty
{
	public Nature $type;

	public int $id;
	public float $value;
	public AccountProfile|bool $creditor;
	public AccountProfile|bool $debitor;

	public float $creditAmount;
	public float $debitAmount;


	public StatementCategoryProperty $category;
	public Currency $currency;

	public \DateTime $dateTime;
	public ?string $beneficiary;
	public ?string $description;
	public ?string $reference = null;

	public ?int $relation = null;
	public ?IndividualProfile $individual = null;

	public IndividualProfile $editor;

	public array $attachments;

	public bool $canceled = false;

	public function __construct()
	{

	}

	public function __toString(): string
	{
		return print_r([
			"ID" => $this->id,
			"Canceled" => $this->canceled,
			"Issuer Account" => $this->creditor->id . ": " . $this->creditor->currency->shortname . " " . $this->creditor->name,
			"Target Account" => $this->debitor->id . ": " . $this->debitor->currency->shortname . " " . $this->debitor->name,
			"Date" => $this->dateTime->format("Y-m-d"),
			"Beneficiary" => $this->beneficiary,
			"Value" => $this->value,
			"Category" => $this->category->group . ": " . $this->category->name,
			"Description" => $this->description,
			"Individual" => "(" . gettype($this->individual) . ") " . $this->individual,
			"Reference" => "(" . gettype($this->reference) . ") " . $this->reference,
			"Relation" => "(" . gettype($this->relation) . ") " . $this->relation,
			"Attachments" => $this->attachments,
		], true);
	}
}