<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Exceptions\Finance\TransactionException;
use System\Finance\Account;
use System\Finance\Currency;
use System\Finance\Forex;


class ManualForexInstructions
{
	public function __construct(public Currency $exchangeFrom, public Currency $exchangeTo, public float $value)
	{
	}
}

class Instructions
{
	protected Account $issuer_account;
	protected Account $target_account;

	protected int $category;
	protected bool $open = true;
	protected float $value;
	protected \DateTime $dateTime;
	protected string $beneficiary;
	protected string $description;
	protected ?string $reference = null;
	protected ?int $relation = null;
	protected ?int $individual = null;
	protected ?array $attachments = null;
	protected bool $isOverridenForex = false;
	protected ?int $party = null;

	protected ?ManualForexInstructions $manualForexInstructions = null;

	public ?int $insert_id = null;

	public function __construct(protected \System\App &$app)
	{
	}


	public function overrideForex(int $currency_from, int $currency_to, float $value): void
	{
		if ($value <= 0 || $currency_from == 0 || $currency_to == 0) {
		} else {
			$this->isOverridenForex        = true;
			$this->manualForexInstructions = new ManualForexInstructions(
				new Currency($currency_from),
				new Currency($currency_to),
				$value
			);
		}
	}

	public function attachments(?array $attachments = null): self
	{
		$this->attachments = array();
		foreach ($attachments as $v) {
			if ((int) $v > 0) {
				$this->attachments[] = (int) $v;
			}
		}
		return $this;
	}

	public function value(string|int|float $value): self
	{
		if (gettype($value) == "integer" || gettype($value) == "double") {
			if ($value > 0) {
				$this->value = $value;
			} else {
				throw new TransactionException("Value must be a positive number", 101);
			}
		} else if (gettype($value) == "string") {
			$value = trim(str_replace(",", "", (string) $value));
			if (is_numeric($value)) {
				$this->value = (float) $value;
			} else {
				throw new TransactionException("Invalid transaction value", 101);
			}
		}
		return $this;
	}

	public function beneficiary(string $beneficiary): self
	{
		$beneficiary = trim($beneficiary);
		if (empty($beneficiary)) {
			throw new TransactionException("Beneficiary is required", 102);
		}
		$this->beneficiary = $beneficiary;
		return $this;
	}

	public function description(string $description): self
	{
		$description = trim($description);
		if (empty($description)) {
			throw new TransactionException("Description field is required", 103);
		}
		$this->description = $description;
		return $this;
	}

	public function reference(?string $reference): self
	{
		$reference = trim($reference);
		if (empty(trim($reference))) {
			$this->reference = null;
		} else {
			$this->reference = (string) $reference;
		}
		return $this;
	}

	public function individual(string|int|null $id = null): self
	{
		if (gettype($id) == "integer") {
			if ($id > 0) {
				$this->individual = $id;
				return $this;
			} else {
				throw new TransactionException("Value must be a positive number", 104);
			}
		} else if (gettype($id) == "string") {
			if (is_numeric(trim($id))) {
				$this->individual = (int) $id;
				return $this;
			}
		}
		$this->individual = null;
		return $this;
	}

	public function relation(string|int|null $id = null): self
	{
		if (gettype($id) == "integer") {
			if ($id > 0) {
				$this->relation = $id;
				return $this;
			}
		} else if (gettype($id) == "string") {
			if (is_numeric(trim($id))) {
				if ((int) $id > 0) {
					$this->relation = (int) $id;
					return $this;
				}
			}
		}
		$this->relation = null;
		return $this;
	}

	public function category(int|string $category_id): self
	{
		if (gettype($category_id) == "integer" && $category_id > 0) {
			$this->category = $category_id;
		} else if (gettype($category_id) == "string") {
			$category_id = (int) $category_id;
			if ($category_id > 0) {
				$this->category = $category_id;
			}
		}

		if (isset($this->category)) {
			if ($result = $this->app->db->query("SELECT acccat_id FROM acc_categories WHERE acccat_id = {$this->category};")) {
				if ($result->num_rows == 1) {
					return $this;
				}
			}
		}
		unset($this->category);
		throw new TransactionException("Invalid category", 105);
	}

	public function party(int|string|null $party_id): self
	{
		if (is_null($party_id)) {
		} else if (gettype($party_id) == "integer" && $party_id > 0) {
			$this->party = $party_id;
		} else if (gettype($party_id) == "string") {
			$party_id = (int) $party_id;
			if ($party_id > 0) {
				$this->party = $party_id;
			}
		}
		return $this;
	}


	public function issuerAccount(Account $account): self
	{
		$this->issuer_account = $account;
		$this->accountConflict();
		return $this;
	}
	public function openState(bool $isOpen): self
	{
		$this->open = $isOpen;
		return $this;
	}

	public function targetAccount(Account $account): self
	{
		$this->target_account = $account;
		$this->accountConflict();
		return $this;
	}

	protected function accountConflict(): void
	{
		if (isset($this->issuer_account, $this->target_account)) {
			if ($this->issuer_account->id == $this->target_account->id) {
				throw new TransactionException("Account conflict", 106);
			}
		}
	}

	public function date(\DateTime|string $date, string $format = "Y-m-d"): self
	{
		if (gettype($date) == "string") {
			$temp = \DateTime::createFromFormat($format, $date);
			if ($temp === false) {
				throw new TransactionException("Invalid transaction date", 107);
			} else {
				$this->dateTime = $temp;
			}
		} elseif (gettype($date) == "object" && get_class($date) == "DateTime") {
			$this->dateTime = $date;
		}
		return $this;
	}


	public function __toString(): string
	{
		return print_r([
			"Issuer Account" => $this->issuer_account->id . ": " . $this->issuer_account->currency->shortname . " " . $this->issuer_account->name,
			"Target Account" => $this->target_account->id . ": " . $this->target_account->currency->shortname . " " . $this->target_account->name,
			"Date" => $this->dateTime->format("Y-m-d"),
			"IsOpen" => $this->open ? "Yes" : "No",
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


abstract class Transaction extends Instructions
{
	protected Forex $forex;
	protected int $nature_id;
	protected abstract function releaseStatementPairs(int $ownerID): bool;

	public function __construct(protected \System\App &$app)
	{
		parent::__construct($app);
		$this->forex = new Forex($this->app);
	}

	private function integrity(): bool
	{
		if (
			!isset($this->issuer_account) ||
			!isset($this->target_account) ||
			!isset($this->beneficiary) ||
			!isset($this->value) ||
			!isset($this->category) ||
			!isset($this->description) ||
			!isset($this->dateTime)
		) {
			throw new TransactionException("Transaction instructions are not completed", 300);
		}
		return true;
	}

	private function clearStatementSlips(int $statementID): bool
	{
		$stmt = $this->app->db->prepare("DELETE FROM acc_temp WHERE atm_main = ?;");
		$stmt->bind_param("i", $statementID);
		if (!$stmt->execute())
			return false;

		$stmt = $this->app->db->prepare("UPDATE uploads SET up_rel = NULL WHERE up_rel = ?;");
		$stmt->bind_param("i", $statementID);

		return $stmt->execute();
	}
	private function processEdit(int $statementID): bool
	{
		$stmt = $this->app->db->prepare(
			"UPDATE acc_main SET
				acm_company = ?,
				acm_usr_id = ?,
				acm_ctime = ?,
				acm_type = ?,
				acm_beneficial = ?,
				acm_category = ?,
				acm_comments = ?,
				acm_reference = ?,
				acm_realvalue = ?,
				acm_realcurrency = ?,
				acm_rel = ?,
				acm_party = ?,
				acm_rejected = ?
			WHERE
				acm_id = ?;"
		);

		$dateTime  = $this->dateTime->format("Y-m-d");
		$openState = $this->open ? 0 : 1;

		$stmt->bind_param(
			"iisisissdiiiii",
			$this->app->user->company->id,
			$this->individual,
			$dateTime,
			$this->nature_id,
			$this->beneficiary,
			$this->category,
			$this->description,
			$this->reference,
			$this->value,
			$this->issuer_account->currency->id,
			$this->relation,
			$this->party,
			$openState,
			$statementID
		);

		if ($stmt->execute()) {
			$this->insert_id = $statementID;
			if ($this->clearStatementSlips($this->insert_id) && $this->releaseStatementPairs($this->insert_id)) {
				return $this->linkAttachments($this->insert_id);
			}
		}
		return false;
	}

	private function processPost(): bool
	{
		$stmt      = $this->app->db->prepare(
			"INSERT INTO acc_main (acm_company,acm_usr_id,acm_editor_id,acm_ctime,acm_time,acm_type,acm_beneficial,acm_category,acm_comments,acm_reference,acm_realvalue,acm_realcurrency,acm_rel,acm_party
			) VALUES (?, ?,?,?,?,? ,?,?,?,?,?, ?,?,?);"
		);
		$dateTime  = $this->dateTime->format("Y-m-d");
		$timeStamp = (new \DateTime("now"))->format("Y-m-d H:i:s");

		$stmt->bind_param(
			"iiissisissdiii",
			$this->app->user->company->id,
			$this->individual,
			$this->app->user->info->id,
			$dateTime,
			$timeStamp,
			$this->nature_id,
			$this->beneficiary,
			$this->category,
			$this->description,
			$this->reference,
			$this->value,
			$this->issuer_account->currency->id,
			$this->relation,
			$this->party
		);
		if ($stmt->execute()) {
			$this->insert_id = $stmt->insert_id;
			if ($this->releaseStatementPairs($this->insert_id)) {
				return $this->linkAttachments($this->insert_id);
			}
		}
		return false;
	}

	private function linkAttachments(int $ownerID): bool
	{
		if (!isset($this->attachments)) {
			return true;
		}
		if (sizeof($this->attachments) > 0) {
			$stmt = $this->app->db->prepare("UPDATE uploads SET up_rel = $ownerID , up_active = 1 WHERE up_id = ?;");
			foreach ($this->attachments as &$attach) {
				$stmt->bind_param('i', $attach);
				if (!$stmt->execute()) {
					return false;
				}
			}
		}
		return true;
	}

	public function post(): bool
	{
		$this->integrity();
		$this->app->db->autocommit(false);
		if ($this->processPost()) {
			$this->app->db->commit();
			$this->app->db->autocommit(true);
			return true;
		} else {
			$this->app->db->rollback();
			return false;
		}
	}

	public function edit(int $transactionID): bool
	{
		$this->integrity();
		$this->app->db->autocommit(false);
		if ($this->processEdit($transactionID)) {
			$this->app->db->commit();
			$this->app->db->autocommit(true);
			return true;
		} else {
			$this->app->db->rollback();
			return false;
		}
	}
}
