<?php

declare(strict_types=1);

namespace System\FileSystem;

class Permission
{
	public bool $deny = true;
	public  bool $read = false;
	public  bool $add = false;
	public  bool $edit = false;
	public  bool $delete = false;
}
