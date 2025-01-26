<?php

declare(strict_types=1);

namespace System\Core\Log;

class Performance
{
	private float $starting_time;
	private string $log_file;
	public function __construct(string $log_file)
	{
		$this->starting_time = microtime(true);
		$this->log_file = $log_file;
	}

	public function fullReport(string $file_system = "", int $user_id = 0)
	{

		error_log("[" . date("Y-m-d H:i:s (T)") . "] {$user_id} " . $file_system . " " . (microtime(true) - $this->starting_time) . "s " . round(memory_get_usage() / 1000, 2) . "KB " . round(memory_get_peak_usage() / 1000, 2) . "KB " . PHP_EOL, 3, $this->log_file);
	}
	public function memUsage(string $file_system = "", int $user_id = 0)
	{
		error_log("[" . date("Y-m-d H:i:s (T)") . "] {$user_id} " . $file_system . " " . round(memory_get_usage() / 1000, 2) . "KB " . round(memory_get_peak_usage() / 1000, 2) . "KB " . PHP_EOL, 3, $this->log_file);
	}

	public function exeTime(string $file_system = "", int $user_id = 0)
	{
		error_log("[" . date("Y-m-d H:i:s (T)") . "] {$user_id} " . $file_system . " " . (microtime(true) - $this->starting_time) . PHP_EOL, 3, $this->log_file);
	}
}
