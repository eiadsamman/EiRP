<?php

declare(strict_types=1);

namespace System\Core\FileSystem;


class Data
{
	public int $id = 0;
	public string $dir = "";
	public string $directory = "";
	public string $title = "";
	public int $parent = 0;
	public int $forward = 0;
	public bool $enabled = false;
	public bool $visible = false;
	public string $icon = "";
	public string $color = "";
	public string $parameters = "";
	public array $headers = array();
	public array $cdns = array();
	public ?string $loader = null;

	public Permission $permission;
}
