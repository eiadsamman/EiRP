<?php

declare(strict_types=1);

namespace System\Lib;


set_time_limit(60 * 3);
ini_set('memory_limit', '512M');
class SimpleImage
{
	var $image;
	var $image_type;
	var $image_file_path;
	public function FixOrientation()
	{
		$exif = exif_read_data($this->image_file_path);

		if (!empty($exif['Orientation'])) {
			switch ($exif['Orientation']) {
				case 3:
					$this->image = imagerotate($this->image, 180, 0);
					break;
				case 6:
					$this->image = imagerotate($this->image, -90, 0);
					break;
				case 8:
					$this->image = imagerotate($this->image, 90, 0);
					break;
			}
		}
	}

	function load($filename)
	{
		if ($image_info = getimagesize($filename)) {
		} else {
			return false;
		};
		$this->image_type = $image_info[2];
		$this->image_file_path = $filename;
		if ($this->image_type == IMAGETYPE_JPEG) {
			if ($this->image = imagecreatefromjpeg($filename)) {
			} else {
				return false;
			};
		} elseif ($this->image_type == IMAGETYPE_GIF) {
			if ($this->image = imagecreatefromgif($filename)) {
			} else {
				return false;
			};
		} elseif ($this->image_type == IMAGETYPE_PNG) {
			if ($this->image = imagecreatefrompng($filename)) {
			} else {
				return false;
			};
		} elseif ($this->image_type == IMAGETYPE_BMP) {
			if ($this->image = imagecreatefrompng($filename)) {
			} else {
				return false;
			};
		}
	}
	function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 90, $permissions = null)
	{
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image, $filename, $compression);
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($this->image, $filename);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($this->image, $filename);
		} elseif ($image_type == IMAGETYPE_BMP) {
			imagewbmp($this->image, $filename);
		}
		if ($permissions != null) {
			chmod($filename, $permissions);
		}
	}
	function output($image_type = IMAGETYPE_JPEG)
	{
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image);
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($this->image);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($this->image);
		} elseif ($image_type == IMAGETYPE_BMP) {
			imagewbmp($this->image);
		}
	}
	function destroy()
	{
		imagedestroy($this->image);
		$this->image = null;
	}
	function getWidth()
	{
		return imagesx($this->image);
	}
	function getHeight()
	{
		return imagesy($this->image);
	}
	function resizeToHeight($height)
	{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width, $height);
	}
	function resizeToWidth($width)
	{
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width, $height);
	}
	function scale($scale)
	{
		$width = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;
		$this->resize($width, $height);
	}
	function resize($width, $height)
	{
		$width = (int)$width;
		$height = (int)$height;
		$new_image = @imagecreatetruecolor($width, $height) or die(false);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}
	function crop($width, $height, $horizontal, $vertical, $arrColors)
	{
		$width = (int)$width;
		$height = (int)$height;
		$new_image = @imagecreatetruecolor($width, $height) or die(false);
		if ($arrColors == false) {
			$bg = imagecolorat($this->image, 0, 0);
		} else {
			if (sizeof($arrColors) != 3) {
				return false;
			}
			$bg = imagecolorallocate($new_image, $arrColors[0], $arrColors[1], $arrColors[2]);
		}
		imagefilledrectangle($new_image, 0, 0, $width, $height, $bg);
		$tmpWidth = $this->getWidth();
		$tmpHeight = $this->getHeight();
		$hor = ($this->getWidth() - $width) * $horizontal;
		$ver = ($this->getHeight() - $height) * $vertical;
		imagecopyresampled($new_image, $this->image, 0, 0, (int)$hor, (int)$ver, $width, $height, $width, $height);
		if ($tmpWidth < $width || $tmpHeight < $height) {
			$wLh = ($tmpWidth < $tmpHeight ? ($width - $tmpWidth) / 2 : $width);
			$hLw = ($tmpHeight < $tmpWidth ? ($height - $tmpHeight) / 2 : $height);
			imagefilledrectangle($new_image, 0, 0, $wLh, $hLw, $bg);
			$wLh2 = ($tmpWidth < $tmpHeight ? $tmpWidth + $hLw + $tmpWidth : 0);
			$hLw2 = ($tmpHeight < $tmpWidth ? $tmpHeight + $wLh + $tmpWidth : 0);
			imagefilledrectangle($new_image, $wLh2, $hLw2, $wLh + $tmpWidth, $hLw + $tmpHeight, $bg);
			$v1 = ($tmpHeight < $tmpWidth ? ($width - $tmpWidth) / 2 : $width);
			$v2 = ($tmpWidth < $tmpHeight ? ($height - $tmpHeight) / 2 : $height);
			$v11 = ($tmpHeight < $tmpWidth ? $v1 + $tmpWidth : 0);
			$v22 = ($tmpWidth < $tmpHeight ? $v2 + $tmpHeight : 0);
			imagefilledrectangle($new_image, 0, 0, $v1, $v2, $bg);
			imagefilledrectangle($new_image, $v11, $v22, $v1 + $v11, $v2 + $v22, $bg);
		}
		$this->image = $new_image;
	}
}
