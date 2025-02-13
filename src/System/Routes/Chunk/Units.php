<?php
declare(strict_types=1);
namespace System\Routes\Chunk;


class Units extends \System\Routes\Chunk\Chunk
{
	protected function json(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		$terms  = \System\Enum\UnitSystem::cases();
		foreach ($terms as $term) {
			$output .= $smart . "{";
			$output .= "\"id\": {$term->value},";
			$output .= "\"value\": \"{$term->toString()}\" ";
			$output .= "}";
			$smart  = ",";
		}
		$output .= "]";
		echo gzencode($output);
	}

	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		$terms  = \System\Enum\UnitSystem::cases();
		foreach ($terms as $term) {
			$output .= $smart . "{";
			$output .= "\"id\": {$term->value},";
			$output .= "\"value\": \"{$term->toString()}\"";
			$output .= "}";
			$smart  = ",";
		}
		$output .= "]";
		echo gzencode($output);
	}
}