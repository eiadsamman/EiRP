<?php

declare(strict_types=1);

namespace System\Graphics\SVG;

class SVG
{
	public function prepareArray(array &$input): float
	{
		$bound = 0;
		foreach ($input as $k => $v) {
			if (abs($v) > $bound)
				$bound = abs($v);
		}
		if ($bound > 0)
			foreach ($input as $k => &$v) {
				$v = $v / $bound;
			}
		return $bound;
	}
}
