<?php

declare(strict_types=1);

namespace System\Finance\StatementOfAccount;

class Criteria
{
	private ?string $date_start = null;
	private ?string $date_end = null;
	private ?int $statement_id = null;
	private ?int $category = null;
	private ?string $beneficiary = null;
	private ?string $comments = null;
	private int $page_current;
	private int $page_records;

	public function __construct()
	{
		$this->date_start   = null;
		$this->date_end     = null;
		$this->page_current = 0;
		$this->page_records = 25;
	}

	public function statementID(int $statementID): void
	{
		$this->statement_id = $statementID;
	}
	public function beneficiary(string $beneficiary): void
	{
		$this->beneficiary = $beneficiary;
	}

	public function comments(string $comments): void
	{
		$this->comments = $comments;
	}

	public function category(int $category): void
	{
		$this->category = $category;
	}

	public function dateStart(string $date): void
	{
		$this->date_start = $date;
	}
	public function dateEnd(string $date): void
	{
		$this->date_end = $date;
	}
	public function setCurrentPage(int $num): bool
	{
		if ($num <= 0) {
			return false;
		}
		$this->page_current = $num;
		return true;
	}
	public function setRecordsPerPage(int $num): bool
	{
		if ($num <= 0) {
			return false;
		}
		$this->page_records = $num;
		return true;
	}
	public function getCurrentPage(): int
	{
		return $this->page_current;
	}
	public function getRecordsPerPage(): int
	{
		return $this->page_records;
	}

	private function prepareRegex(string $value): string
	{
		$value = mb_ereg_replace("أ|إ|آ", "[أإاآ]+", $value);
		$value = mb_ereg_replace("ة|ه", "[ةه]+", $value);
		$value = mb_ereg_replace("ى|ي", "[يى]+", $value);
		return mb_ereg_replace("[ ]+", "|", $value);
	}

	public function where(): string
	{

		$where = " 1 ";
		$where .= is_null($this->date_start) ? "" : " AND acm_ctime >= '{$this->date_start}' ";
		$where .= is_null($this->date_end) ? "" : " AND acm_ctime <= '{$this->date_end}' ";
		$where .= is_null($this->statement_id) ? "" : " AND acm_id = {$this->statement_id} ";
		$where .= is_null($this->category) ? "" : " AND acm_category = {$this->category} ";
		$where .= is_null($this->beneficiary) ? "" : " AND acm_beneficial REGEXP '{$this->prepareRegex($this->beneficiary)}' ";
		$where .= is_null($this->comments) ? "" : " AND acm_comments REGEXP '{$this->prepareRegex($this->comments)}' ";

		return $where;
	}
	public function limit(): string
	{
		return " " . (($this->page_current - 1) * $this->page_records) . ", {$this->page_records}";
	}
}