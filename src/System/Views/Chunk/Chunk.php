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

	protected function json(): void
	{
	}
	protected function html(): void
	{
	}
	protected function slo(): void
	{
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