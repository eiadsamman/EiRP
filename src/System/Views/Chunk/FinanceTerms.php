<?php
declare(strict_types=1);
namespace System\Views\Chunk;
use System\Controller\Financeerm;
use System\Controller\Finance\Term\Asset;
use System\Controller\Finance\Term\Equity;
use System\Controller\Finance\Term\IncomeStatement;
use System\Controller\Finance\Term\Liability;

class FinanceTerms extends \System\Views\Chunk\Chunk
{
	protected function json(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		$terms = [Asset::cases(), Liability::cases(), Equity::cases(), IncomeStatement::cases()];
		foreach ($terms as $term) {
			foreach ($term as $asset) {
				$output .= $smart . "{";
				$output .= "\"id\": {$asset->value},";
				$output .= "\"value\": \"$asset->name\" ";
				$output .= "\"term\": \"{$asset->termType()}\" ";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}

	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		$terms = [Asset::cases(), Liability::cases(), Equity::cases(), IncomeStatement::cases()];
		foreach ($terms as $term) {
			foreach ($term as $asset) {
				$output .= $smart . "{";
				$output .= "\"id\": {$asset->value},";
				$output .= "\"value\": \"{$asset->termType()}: $asset->name\", ";
				$output .= "\"term\": \"{$asset->termType()}\" ";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}