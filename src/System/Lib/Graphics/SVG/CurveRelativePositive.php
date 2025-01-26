<?php

declare(strict_types=1);

namespace System\Lib\Graphics\SVG;



class CurveRelativePositive extends SVG
{

	private int $x_shift = 5;
	private float $y_shift = 3;
	private float $bez = 0;
	private array $temp = [];

	function __construct(public int $wavelength = 4, public int $magnitude = 3, public float $beziers_amp = -0.3)
	{
		$this->y_shift = $this->magnitude * 0.2;
		$this->bez = $this->beziers_amp * $this->wavelength;
	}

	public function ViewBox($size): string
	{
		return "0 0 " . ($this->wavelength * $size + ($this->x_shift * 2)) . " " . (($this->magnitude) + (2 * $this->y_shift));
	}

	public function XMLCurve(array $arr): string
	{
		$plot = "";

		reset($arr);
		if (is_null(current($arr)))
			return "";

		$plot .= "M " . ($this->x_shift) . " " . $this->magnitude * -current($arr) + $this->magnitude + $this->y_shift . " ";
		$x1 = -$this->bez + $this->x_shift;
		$y1 = $this->magnitude * -current($arr) + $this->magnitude + $this->y_shift;

		next($arr);

		if (is_null(current($arr)))
			return "";

		$x2 = $this->wavelength + $this->bez + $this->x_shift;
		$y2 = -current($arr) * $this->magnitude + $this->magnitude + $this->y_shift;
		$x = $this->wavelength + $this->x_shift;
		$y = $this->magnitude * -current($arr) + $this->magnitude + $this->y_shift;

		$plot .= "C $x1 $y1, $x2 $y2, $x $y ";

		//Already 0,1 elements are drown
		for ($i = 2; $i < sizeof($arr); $i++) {
			if (next($arr) !== false) {
				$per_val = current($arr);
				//Break graph if no more data is available
				if (is_null($per_val)) {
					break;
				}
				//Transform graph vertically
				$per_val = -$per_val;
				//Beiezer control points
				$x2 = $i * $this->wavelength + $this->bez + $this->x_shift;
				$y2 = $this->magnitude * $per_val + $this->magnitude + $this->y_shift;
				//Beiezer point
				$x = $i * $this->wavelength + $this->x_shift;
				$y = $this->magnitude * $per_val + $this->magnitude + $this->y_shift;
				//SVG XML
				$plot .= "S $x2 $y2, $x $y ";
			}
		}
		$plot .= "";
		return $plot;
	}

	public function XMLHorizontalAxis(float $size): string
	{
		return " x1=" . $this->x_shift . " y1=" . ($this->magnitude + $this->y_shift) . " x2=" . ($this->wavelength * $size + $this->x_shift) . " y2=" . ($this->magnitude + $this->y_shift);
	}

	public function XMLPoints(array $arr): array
	{
		$output = array();
		reset($arr);
		$i = 0;
		foreach ($arr as $key => $point) {
			if (is_null($point)) {
				$output[$key] = null;
			} else {
				$output[$key] = new \System\Lib\Graphics\Points2D($i * $this->wavelength + $this->x_shift, ($this->magnitude * -floatval($point)) + $this->magnitude + $this->y_shift);
			}
			$i++;
		}
		return $output;
	}
}
