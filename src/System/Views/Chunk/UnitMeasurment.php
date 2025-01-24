<?php
declare(strict_types=1);
namespace System\Views\Chunk;


class UnitMeasurment extends \System\Views\Chunk\Chunk
{
	protected function json(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";

		$output .= "]";
		echo gzencode($output);
	}

	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output   = "[";
		$smart    = "";
		$measures = $this->app->unitMeasurment->list((int) $_GET['unit']);

		foreach ($measures as $unit_key=>$unit_param) {
			$output .= $smart . "{";
			$output .= "\"id\": {$unit_key},";
			$output .= "\"value\": \"{$unit_param->symbol}\"";
			$output .= "}";
			$smart  = ",";
		}
		$output .= "]";
		echo gzencode($output);
	}
}