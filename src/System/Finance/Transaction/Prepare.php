<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Exceptions\Finance\TransactionPrepareException;
use System\Finance\Account;
use System\Finance\Forex;


class TransactionDetails
{
	public Account $issuer_account;
	public Account $target_account;

	public int $category;
	public float $value;
	public \DateTime $dateTime;
	public string $beneficiary;
	public string $description;
	public ?string $reference = null;
	public ?int $relation = null;
	public ?int $individual = null;
	public ?array $attachments = null;

	public function __toString(): string
	{
		return print_r([
			"Issuer Account" => $this->issuer_account->id . ": " . $this->issuer_account->currency->shortname . " " . $this->issuer_account->name,
			"Target Account" => $this->target_account->id . ": " . $this->target_account->currency->shortname . " " . $this->target_account->name,
			"Date" => $this->dateTime->format("Y-m-d"),
			"Beneficiary" => $this->beneficiary,
			"Value" => $this->value,
			"Category" => $this->category,
			"Description" => $this->description,
			"Individual" => "(" . gettype($this->individual) . ") " . $this->individual,
			"Reference" => "(" . gettype($this->reference) . ") " . $this->reference,
			"Relation" => "(" . gettype($this->relation) . ") " . $this->relation,
			"Attachments" => $this->attachments,
		], true);
	}

}


abstract class Prepare
{

	protected TransactionDetails $details;
	public Forex $forex;

	public function __construct(protected \System\App &$app, private Nature $nature)
	{
		$this->details = new TransactionDetails();
		$this->forex = new Forex($this->app);
	}


	public abstract function releaseStatementPairs(int $ownerID): bool;


	public function transactionDetails(): TransactionDetails
	{

		if (
			!isset($this->details->issuer_account) ||
			!isset($this->details->target_account) ||
			!isset($this->details->value) ||
			!isset($this->details->category) ||
			!isset($this->details->beneficiary) ||
			!isset($this->details->description) ||
			!isset($this->details->dateTime)
		) {
			throw new TransactionPrepareException("Transaction preperation failed", 900);
		}

		return clone $this->details;
	}

	public function attachments(?array $attachments = null): self
	{

		$this->details->attachments = array();
		foreach ($attachments as $v) {
			if ((int) $v > 0) {
				$this->details->attachments[] = (int) $v;
			}
		}
		return $this;
	}

	public function value(string|int|float $value): self
	{
		if (gettype($value) == "integer" || gettype($value) == "double") {
			if ($value > 0) {
				$this->details->value = $value;
			} else {
				throw new TransactionPrepareException("Value must be a positive number", 101);
			}
		} else if (gettype($value) == "string") {
			$value = trim(str_replace(",", "", (string) $value));
			if (is_numeric($value)) {
				$this->details->value = (float) $value;
			} else {
				throw new TransactionPrepareException("Invalid transaction value", 101);
			}
		}
		return $this;
	}

	public function beneficiary(string $beneficiary): self
	{
		$beneficiary = trim($beneficiary);
		if (empty($beneficiary)) {
			throw new TransactionPrepareException("Beneficiary is required", 104);
		}
		$this->details->beneficiary = $beneficiary;
		return $this;
	}

	public function description(string $description): self
	{
		$description = trim($description);
		if (empty($description)) {
			throw new TransactionPrepareException("Description field is required", 105);
		}
		$this->details->description = $description;
		return $this;
	}

	public function reference(?string $reference): self
	{
		$reference = trim($reference);
		if (empty(trim($reference))) {
			$this->details->reference = null;
		} else {
			$this->details->reference = (string) $reference;
		}
		return $this;
	}

	public function individual(string|int|null $id = null): self
	{

		if (gettype($id) == "integer") {
			if ($id > 0) {
				$this->details->individual = $id;
				return $this;
			} else {
				throw new TransactionPrepareException("Value must be a positive number", 106);
			}
		} else if (gettype($id) == "string") {
			if (is_numeric(trim($id))) {
				$this->details->individual = (int) $id;
				return $this;
			}
		}
		$this->details->individual = null;
		return $this;
	}

	public function relation(string|int|null $id = null): self
	{

		if (gettype($id) == "integer") {
			if ($id > 0) {
				$this->details->relation = $id;
				return $this;
			}
		} else if (gettype($id) == "string") {
			if (is_numeric(trim($id))) {
				$this->details->relation = (int) $id;
				return $this;
			}
		}
		$this->details->relation = null;
		return $this;
	}

	public function category(int|string $category_id): self
	{
		if (gettype($category_id) == "integer" && $category_id > 0) {
			$this->details->category = $category_id;
		} else if (gettype($category_id) == "string") {
			$category_id = (int) $category_id;
			if ($category_id > 0) {
				$this->details->category = $category_id;
			}
		}

		if (isset($this->details->category)) {
			if ($result = $this->app->db->query("SELECT acccat_id FROM acc_categories WHERE acccat_id = {$this->details->category};")) {
				if ($result->num_rows == 1) {
					return $this;
				}
			}
		}
		unset($this->details->category);
		throw new TransactionPrepareException("Invalid category", 103);
	}



	public function issuerAccount(Account $account): self
	{
		$this->details->issuer_account = $account;
		$this->accountConflict();
		return $this;
	}

	public function targetAccount(Account $account): self
	{
		$this->details->target_account = $account;
		$this->accountConflict();
		return $this;
	}

	protected function accountConflict(): void
	{
		if (isset($this->details->issuer_account, $this->details->target_account)) {
			if ($this->details->issuer_account->id == $this->details->target_account->id) {
				throw new TransactionPrepareException("Account conflict", 102);
			}
		}
	}


	public function date(\DateTime|string $date, string $format = "Y-m-d"): self
	{
		if (gettype($date) == "string") {
			$temp = \DateTime::createFromFormat($format, $date);
			if ($temp === false) {
				throw new TransactionPrepareException("Invalid transaction date", 110);
			} else {
				$this->details->dateTime = $temp;
			}
		} elseif (gettype($date) == "object" && get_class($date) == "DateTime") {
			$this->details->dateTime = $date;
		}
		return $this;
	}


}