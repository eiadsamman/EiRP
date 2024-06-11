<?php
class Barcode {
	protected static $code39 = array(
	'0' => 'bwbwwwbbbwbbbwbw','1' => 'bbbwbwwwbwbwbbbw',
	'2' => 'bwbbbwwwbwbwbbbw','3' => 'bbbwbbbwwwbwbwbw',
	'4' => 'bwbwwwbbbwbwbbbw','5' => 'bbbwbwwwbbbwbwbw',
	'6' => 'bwbbbwwwbbbwbwbw','7' => 'bwbwwwbwbbbwbbbw',
	'8' => 'bbbwbwwwbwbbbwbw','9' => 'bwbbbwwwbwbbbwbw',
	'A' => 'bbbwbwbwwwbwbbbw','B' => 'bwbbbwbwwwbwbbbw',
	'C' => 'bbbwbbbwbwwwbwbw','D' => 'bwbwbbbwwwbwbbbw',
	'E' => 'bbbwbwbbbwwwbwbw','F' => 'bwbbbwbbbwwwbwbw',
	'G' => 'bwbwbwwwbbbwbbbw','H' => 'bbbwbwbwwwbbbwbw',
	'I' => 'bwbbbwbwwwbbbwbw','J' => 'bwbwbbbwwwbbbwbw',
	'K' => 'bbbwbwbwbwwwbbbw','L' => 'bwbbbwbwbwwwbbbw',
	'M' => 'bbbwbbbwbwbwwwbw','N' => 'bwbwbbbwbwwwbbbw',
	'O' => 'bbbwbwbbbwbwwwbw','P' => 'bwbbbwbbbwbwwwbw',
	'Q' => 'bwbwbwbbbwwwbbbw','R' => 'bbbwbwbwbbbwwwbw',
	'S' => 'bwbbbwbwbbbwwwbw','T' => 'bwbwbbbwbbbwwwbw',
	'U' => 'bbbwwwbwbwbwbbbw','V' => 'bwwwbbbwbwbwbbbw',
	'W' => 'bbbwwwbbbwbwbwbw','X' => 'bwwwbwbbbwbwbbbw',
	'Y' => 'bbbwwwbwbbbwbwbw','Z' => 'bwwwbbbwbbbwbwbw',
	'-' => 'bwwwbwbwbbbwbbbw','.' => 'bbbwwwbwbwbbbwbw',
	' ' => 'bwwwbbbwbwbbbwbw','*' => 'bwwwbwbbbwbbbwbw',
	'$' => 'bwwwbwwwbwwwbwbw','/' => 'bwwwbwwwbwbwwwbw',
	'+' => 'bwwwbwbwwwbwwwbw','%' => 'bwbwwwbwwwbwwwbw');


	public static function code39($text, $height = 50, $widthScale = 1) {
		if (!preg_match('/^[A-Z0-9-. $+\/%]+$/i', $text)) {
			throw new Exception('Invalid text input.');
		}
		
		$text = '*' . strtoupper($text) . '*'; // *UPPERCASE TEXT*
		$length = strlen($text);

		$barcode = imageCreate($length * 16 * $widthScale, $height);
		imageantialias($barcode, true);
		
		imagesetinterpolation($barcode,IMG_BICUBIC);
		imagealphablending($barcode, false);
	
	
		$bg = imagecolorallocate($barcode, 255, 255, 0); //sets background to yellow
		imagecolortransparent($barcode, $bg); //makes that yellow transparent
		$black = imagecolorallocate($barcode, 0, 0, 0); //defines a color for black

		$chars = str_split($text);

		$colors = '';

		foreach ($chars as $char) {
			$colors .= self::$code39[$char];
		}

		foreach (str_split($colors) as $i => $color) {
			if ($color == 'b') {
				
				imageFilledRectangle($barcode, $widthScale * $i, 0, $widthScale * ($i+1) -1 , $height, $black);
			}
		}

		imagePNG($barcode);
		imageDestroy($barcode);
		exit;
	}
}





class Code128Barcode
{

	const CODES = array(
		212222, 222122, 222221, 121223, 121322, 131222, 122213, 122312, 132212, 221213,
		221312, 231212, 112232, 122132, 122231, 113222, 123122, 123221, 223211, 221132,
		221231, 213212, 223112, 312131, 311222, 321122, 321221, 312212, 322112, 322211,
		212123, 212321, 232121, 111323, 131123, 131321, 112313, 132113, 132311, 211313,
		231113, 231311, 112133, 112331, 132131, 113123, 113321, 133121, 313121, 211331,
		231131, 213113, 213311, 213131, 311123, 311321, 331121, 312113, 312311, 332111,
		314111, 221411, 431111, 111224, 111422, 121124, 121421, 141122, 141221, 112214,
		112412, 122114, 122411, 142112, 142211, 241211, 221114, 413111, 241112, 134111,
		111242, 121142, 121241, 114212, 124112, 124211, 411212, 421112, 421211, 212141,
		214121, 412121, 111143, 111341, 131141, 114113, 114311, 411113, 411311, 113141,
		114131, 311141, 411131, 211412, 211214, 211232, 23311120
	);


	public static function generate(string $code, int $density = 1,int $height = 20)
	{
		
		$width = (((11 * strlen($code)) + 35) * ($density / 72)); 
        $width = round($width * 72);

		$image = imagecreatetruecolor($width, $height);

		imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
		imagesetthickness($image, $density );
		
		$checksum = 103;
		$encoding = array(self::CODES[103]);

		//Add Code 128 values from ASCII values found in $code
		for ($i = 0; $i < strlen($code); $i++) {
			//Add checksum value of character
			$checksum += (ord(substr($code, $i, 1)) - 32) * ($i + 1);

			//Add Code 128 values from ASCII values found in $code
			//Position is array is ASCII - 32
			array_push($encoding, self::CODES[(ord(substr($code, $i, 1))) - 32]);

		}

		//Insert the checksum character (remainder of $checksum/103) and STOP value
		array_push($encoding, self::CODES[$checksum % 103]);
		array_push($encoding, self::CODES[106]);

		//Implode the array as string
		$enc_str = implode($encoding);

		//Assemble the barcode
		for ($i = 0, $x = 0, $inc = round(($density / 72) * 100); $i < strlen($enc_str); $i++) {
			//Get the integer value of the string element
			$val = intval(substr($enc_str, $i, 1));

			//Create lines/spaces
			//Bars are generated on even sequences, spaces on odd
			$black =  imagecolorallocate($image, 0, 0, 0);
			for ($n = 0; $n < $val; $n++, $x += $inc) {
				if ($i % 2 == 0) {
					imageline($image, $x, 0, $x, $height, $black);
				}
			}

		}

		imagePNG($image);
		imageDestroy($image);
		exit;
	}


}

?>