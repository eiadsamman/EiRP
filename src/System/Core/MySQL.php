<?php

declare(strict_types=1);

namespace System\Core;

class MySQL extends \mysqli
{
	private $fp;
	private bool $log = false;

	public function query(string $query, int|null $result_mode = MYSQLI_STORE_RESULT): bool|\mysqli_result
	{
		if ($this->log) {
			$bt       = debug_backtrace();
			$caller   = array_shift($bt);
			$this->fp = fopen("queries.log", 'a');
			fwrite($this->fp, $caller['file'] . " on line " . $caller['line'] . "\n" . $query . "\n" . str_repeat("-", 50) . "\n");
			fclose($this->fp);
		}
		return parent::query($query, $result_mode);
	}

}