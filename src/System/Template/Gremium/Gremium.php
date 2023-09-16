<?php

namespace System\Template\Gremium;



class Gremium
{

	public string $exclamation = "exclamation";
	public string $informative = "informative";
	public string $xmark = "xmark";
	public string $check = "check";

	public function __construct(bool $limit_width = true)
	{
		echo "<div class=\"gremium " . ($limit_width ? "limit-width" : "") . "\">";
	}
	/** 
	 *	<a href=\"\" class=\"previous\"></a>
	 *	<span class=\"exclamation\"></span>
	 *	
	 *	<h1>Header</h1>
	 *	<ul>
	 *		<li>List1</li>
	 *		<li>List2</li>
	 *	</ul>
	 *	<cite>Cite</cite>
	 */

	private bool $header_init = true;
	private bool $menu_init = true;
	private bool $section_init = true;
	private bool $section_header_init = true;
	private bool $section_section_init = true;

	private bool $header_served = false;
	private bool $menu_served = false;
	private bool $section_served = false;

	public function header(bool $glued = true, ?string $icon = null, ?string $previous = null, ?string $inline = null): self
	{


		if (is_null($inline)) {
			if ($this->header_init)
				echo "<header class=\"" . ($glued ? "glue" : "") . "\">";
			else
				echo "</header>";
			$this->header_init = !$this->header_init;

			if ($previous != null)
				echo "<a href=\"$previous\" class=\"previous\"></a>";

			if ($icon != null)
				echo "<span class=\"$icon\"></span>";

		} else {
			echo "<header class=\"" . ($glued ? "glue" : "") . "\">";
			if ($previous != null)
				echo "<a href=\"$previous\" class=\"previous\"></a>";

			if ($icon != null)
				echo "<span class=\"$icon\"></span>";
			echo "{$inline}</header>";
			$this->header_init = true;
		}

		$this->header_served = true;
		return $this;
	}




	public function menu(bool $glued = true, ?string $inline = null): self
	{
		$glue_position = "";
		if ($this->header_served) {
			$glue_position = "top: calc(var(--gremium--header-height) + var(--root--menubar-height));";
		} else {
			$glue_position = "top: calc(var(--root--menubar-height));";
		}

		if (is_null($inline)) {
			if ($this->menu_init)
				echo "<menu style=\"$glue_position\" class=\"btn-set" . ($glued ? " glue" : "") . "\">";
			else
				echo "</menu>";
			$this->menu_init = !$this->menu_init;
		} else {
			echo "<menu style=\"$glue_position\" class=\"btn-set" . ($glued ? " glue" : "") . "\">{$inline}</menu>";
			$this->menu_init = true;
		}

		$this->menu_served = true;
		return $this;
	}

	public function section(bool $glued = true): self
	{

		if ($this->section_init) {
			$this->section_served = true;
			echo "<section class=\"" . ($glued ? "glue" : "") . "\">";
		} else {
			$this->section_served = false;
			echo "</section>";
		}
		$this->section_init = !$this->section_init;
		return $this;

	}


	public function sectionHeader(?string $inline = null): self
	{
		$glue_position = "";

		if ($this->section_served) {
			if ($this->header_served && $this->menu_served) {
				$glue_position = "top: calc(var(--root--menubar-height) + var(--gremium--header-height) + var(--gremium--menu-height));";
			} elseif ($this->header_served && !$this->menu_served) {
				$glue_position = "top: calc(var(--root--menubar-height) + var(--gremium--header-height) );";
			} elseif (!$this->header_served && $this->menu_served) {
				$glue_position = "top: calc(var(--root--menubar-height) +  var(--gremium--menu-height));";
			} elseif (!$this->header_served && !$this->menu_served) {
				$glue_position = "top: calc(var(--root--menubar-height) );";
			}
		}
		if (is_null($inline)) {
			if ($this->section_header_init)
				echo "<header style=\"$glue_position\" class=\"btn-set\">";
			else
				echo "</header>";
			$this->section_header_init = !$this->section_header_init;
		} else {
			echo "<header style=\"$glue_position\" class=\"btn-set\">{$inline}</header>";
			$this->section_header_init = true;
		}
		return $this;

	}
	public function sectionArticle(?string $inline = null): self
	{
		if (is_null($inline)) {
			if ($this->section_section_init)
				echo "<article>";
			else
				echo "</article>";
			$this->section_section_init = !$this->section_section_init;
		} else {
			echo "<article>{$inline}</article>";
			$this->section_section_init = true;
		}

		return $this;
	}




	public function terminate()
	{
		echo "</div>";
	}

	public function __destruct()
	{
		$this->terminate();
	}



}

?>