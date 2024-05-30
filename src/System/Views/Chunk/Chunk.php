<?php
declare(strict_types=1);

namespace System\Views\Chunk;

use System\App;

class Chunk
{
	protected string $outPutType = "json";

	public function __construct(protected App &$app, protected array $args)
	{
		if (sizeof($args) > 2) {
			$this->outPutType = strtolower(trim($args[2]));
		}
	}
	protected function headerJSONCacheGzip(): void
	{
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (true ? 3600 : 0)) . ' GMT');
		header("Cache-Control: public, immutable, max-age=3600");
		header("Pragma: cache");
		header('Content-Type: application/json; charset=utf-8', true);
		header("Content-Encoding: gzip");
	}
	protected function json(): void
	{
	}
	protected function html(): void
	{
	}
	protected function slo(): void
	{
		$output = "[";
		$output .= "{";
		$output .= "\"id\": \"0\",";
		$output .= "\"value\": \"\"";
		$output .= "\"highlight\": \"\",";
		$output .= "\"keywords\": \"\",";
		$output .= "\"selected\": false,";
		$output .= "}";
		$output .= "]";
		echo gzencode($output);
	}
	public function render(): void
	{
		if ($this->outPutType == "json") {
			$this->json();
		} elseif ($this->outPutType == "html") {
			$this->html();
		} elseif ($this->outPutType == "slo") {
			$this->slo();
		}
	}

}