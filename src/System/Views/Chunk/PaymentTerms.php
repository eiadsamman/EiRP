<?php
declare(strict_types=1);
namespace System\Views\Chunk;


class PaymentTerms extends \System\Views\Chunk\Chunk
{
	protected function json(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		$terms  = \System\Finance\Invoice\enums\PaymentTerm::cases();
		foreach ($terms as $term) {
			if ($term->value < 100) {
				continue;
			}
			
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
		$terms  = \System\Finance\Invoice\enums\PaymentTerm::cases();
		foreach ($terms as $term) {
			if ($term->value < 100) {
				continue;
			}
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