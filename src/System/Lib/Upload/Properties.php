<?php

namespace System\Lib\Upload;


class Properties
{
	public int $id;
	public int $scope;
	public int $relation;
	public int $uploader;
	public int $size;
	public string $name;
	public string $mime;
	public bool $active;
	public bool $deleted;
	public bool $default;
	public \DateTime $datetime;

	public function __construct()
	{
	}

}