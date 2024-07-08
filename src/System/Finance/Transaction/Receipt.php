<?php

declare(strict_types=1);

namespace System\Finance\Transaction;

use System\Exceptions\Finance\TransactionException;
use System\Finance\Account;

class Receipt extends Transaction
{
	public function __construct(protected \System\App &$app)
	{
		parent::__construct($app);
		$this->nature_id = Nature::Receipt->value;
	}
	protected function releaseStatementPairs(int $ownerID): bool
	{
		$stmt    = $this->app->db->prepare(
			"INSERT INTO 
				acc_temp (atm_account_id, atm_value, atm_dir, atm_main) 
			VALUES 
				(?,?,?,?); "
		);
		$account = $value = $dir = null;
		$stmt->bind_param(
			"idii",
			$account,
			$value,
			$dir,
			$ownerID
		);


		/* First step */
		$account = $this->issuer_account->id;
		$dir     = 1;
		$value   = 1 * $this->value;
		if (!$stmt->execute()) {
			return false;
		}

		/* Second step */
		$account = $this->target_account->id;
		$dir     = 0;
		if ($this->app->file->find(87)->permission->edit && $this->isOverridenForex) {
			if ($this->manualForexInstructions->exchangeFrom->id == $this->target_account->currency->id) {
				$value = -1 * $this->value / $this->manualForexInstructions->value;
			} else {
				$value = -1 * $this->value * $this->manualForexInstructions->value;
			}
		} else {
			$value = -1 * $this->forex->exchangeBuyCurrency($this->issuer_account->currency->id, $this->target_account->currency->id, $this->value);
		}
		if (!$stmt->execute()) {
			return false;
		}
		return true;
	}

	public final function issuerAccount(Account $account): self
	{
		if (!$account->role->inbound) {
			throw new TransactionException("Account isn't set for inbound operations", 201);
		}
		$this->issuer_account = $account;
		$this->accountConflict();
		return $this;
	}

	public final function targetAccount(Account $account): self
	{
		if (!$account->role->outbound) {
			throw new TransactionException("Account isn't set for outbound operations", 202);
		}
		$this->target_account = $account;
		$this->accountConflict();
		return $this;
	}
}
