<?php

declare(strict_types=1);

namespace System\Finance\StatementOfAccount;

class Criteria
{
	private string|null $date_start;
	private string|null $date_end;
	private int $page_current;
	private int $page_records;

	public function __construct()
	{
		$this->date_start = null;
		$this->date_end = null;
		$this->page_current = 0;
		$this->page_records = 25;
	}

	public function dateStart(string $date)
	{
		$this->date_start = $date;
	}
	public function dateEnd(string $date)
	{
		$this->date_end = $date;
	}
	public function setCurrentPage(int $num)
	{
		if($num<=0){
			return false;
		}
		$this->page_current = $num;
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

	public function where(): string
	{
		$where = " 1 ";
		$where .= is_null($this->date_start) ? "" : " AND acm_ctime >= '{$this->date_start}' ";
		$where .= is_null($this->date_end) ? "" : " AND acm_ctime <= '{$this->date_end}' ";
		return $where;
	}
	public function limit(): string
	{
		return " " . (($this->page_current - 1) * $this->page_records) . ", {$this->page_records}";
	}
}