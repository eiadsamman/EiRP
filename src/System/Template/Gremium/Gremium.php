<?php

namespace System\Template\Gremium;

use System\Exceptions\Gremium\StockOpenException;


enum Status: string
{
	case Exclamation = 'exclamation';
	case Informative = 'informative';
	case XMark = 'xmark';
	case CheckMark = 'check';
}


/**
 * HTML Gremium Blocks
 */
class Blocks
{
	/**
	 * Block ID `HTML Tag Name` (header, article, section, legend)
	 * This tag will be controlled by css
	 * 
	 * @var string
	 */
	protected string $id = "";
	/**
	 * Custom DOM ID `HTML ID Attribute`
	 * 
	 * @var string
	 */
	protected string $domid = "";
	/**
	 * Block `style top` location on the screen, Gremium owner class will set this property 
	 * @var string
	 */
	protected string $top = "";
	/**
	 * Boolean attribute for HTML tags status <tag> |</tag>
	 * @var bool
	 */
	protected bool $opened = false;
	/**
	 * Block HTML tag is set to sticky on the screen
	 * @var bool
	 */
	protected bool $sticky = true;
	/**
	 * Block HTML tag is stackable on the screen
	 * @var bool
	 */
	public bool $stackable = true;

	/**
	 * Block message <tab>{$message}</tag>
	 * @var string
	 */
	public string $message = "";

	/**
	 * Block fixed width 
	 * @var string
	 */
	public string|null $fxwidth = null;


	/**
	 * Block fixed max width 
	 * @var string
	 */
	public string|null $fxMaxwidth = null;


	public array|null $options = null;

	/**
	 * Basic __construct
	 */
	public function __construct(?string $domid = null)
	{
		if (!empty($domid)) {
			$this->domid = $domid;
		}
	}
	/**
	 * Serve HTML tags and message to the buffer
	 * If inline param ommited serve the Block $message property
	 * Otherwise serve a complete HTML block <tag>inline</tag>
	 * 
	 * @param mixed $inline
	 * @return \System\Template\Gremium\Blocks
	 */
	public function serve(?string $inline = null): self
	{
		if (!is_null($inline)) {
			if (!$this->opened) {
				$this->open();
			}
			echo "\t" . $inline . "\n";
			$this->close();
		} else {
			if (!$this->opened) {
				$this->open();
			}
			echo "\t" . $this->message . "\n";
			$this->close();
		}
		return $this;
	}
	/**
	 * Open HTML Tag
	 * @return \System\Template\Gremium\Blocks
	 */
	public function open(): self
	{
		return $this;
	}
	/**
	 * Close HTML Tag
	 * @return \System\Template\Gremium\Blocks
	 */
	public function close(): self
	{
		if ($this->opened) {
			echo "</{$this->id}>\n";
			$this->opened = false;
		}
		return $this;
	}
	/**
	 * Set HTML block `top style` property
	 * @param string $top
	 * @return \System\Template\Gremium\Blocks
	 */
	public function top(string $top): self
	{
		$this->top = $top;
		return $this;
	}
	/**
	 * HTML Tag sticky condition
	 * @param mixed $sticky
	 * @return \System\Template\Gremium\Blocks|bool
	 */
	public function sticky(?bool $sticky = null): self|bool
	{
		if (is_null($sticky)) {
			return $this->sticky;
		} else {
			$this->sticky = $sticky ? true : false;
		}
		return $this;
	}
	/**
	 * Is the current HTML block tag is open or closed
	 * @return bool
	 */
	public function isOpen(): bool
	{
		return $this->opened;
	}

	public function width(?string $fxwidth): self
	{
		if (!empty($fxwidth))
			$this->fxwidth = $fxwidth;
		return $this;
	}

	public function maxWidth(?string $fxMaxwidth): self
	{
		if (!empty($fxMaxwidth))
			$this->fxMaxwidth = $fxMaxwidth;
		return $this;
	}


	public function options(?array $options): self
	{
		if (!empty($options))
			$this->options = $options;
		return $this;
	}

}



class Header extends Blocks
{
	protected string $id = "header";
	public int $height = 60;
	private string|null $status = null;
	private string|null $prev = null;

	public function status(Status $status): self
	{
		$this->status = $status->value;
		return $this;
	}
	public function prev(string $prev): self
	{
		$this->prev = trim($prev);
		return $this;
	}
	public function open(): self
	{
		if (!$this->opened) {
			echo "<{$this->id}";
			echo (empty($this->domid) ? "" : " id=\"{$this->domid}\"");
			echo ($this->stackable && $this->sticky ? " style=\"position:sticky; top: calc({$this->top});\" " : "");
			echo ">\n";

			echo $this->prev == null ? "" : "\t<a $this->prev class=\"previous\" aria-label=\"Goto to previous page\" draggable=\"false\" data-role=\"previous\">&nbsp;</a>\n";
			echo $this->status == null ? "" : "\t<span class=\"$this->status\"></span>\n";
			$this->opened = true;
		}
		return $this;
	}
}

class Menu extends Blocks
{
	protected string $id = "menu";
	public int $height = 40;

	public function open(): self
	{
		if (!$this->opened) {
			echo "<{$this->id}";
			echo (empty($this->domid) ? "" : " id=\"{$this->domid}\"");
			echo " class=\"btn-set\"";
			echo ($this->stackable && $this->sticky ? " style=\"position:sticky; top: calc({$this->top} - var(--gremium-header-toggle));\" " : "");
			echo ">\n";
			$this->opened = true;
		}
		return $this;
	}

}

class Legend extends Blocks
{
	protected string $id = "legend";
	public int $height = 42;

	public function open(): self
	{
		if (!$this->opened) {
			echo "<{$this->id}";
			echo (empty($this->domid) ? "" : " id=\"{$this->domid}\"");
			echo " class=\"btn-set\"";
			echo ($this->stackable && $this->sticky ? " style=\"position:sticky; top: calc({$this->top} - var(--gremium-header-toggle));\" " : "");
			echo ">\n";
			$this->opened = true;
		}
		return $this;
	}
}

class Title extends Blocks
{
	protected string $id = "h2";
	public int $height = 42;

	public function open(): self
	{
		if (!$this->opened) {
			echo "<{$this->id}";
			echo (empty($this->domid) ? "" : " id=\"{$this->domid}\"");
			echo ($this->stackable && $this->sticky ? " style=\"position:sticky; top: calc({$this->top} - var(--gremium-header-toggle));\" " : "");
			echo ">\n";
			$this->opened = true;
		}
		return $this;
	}
}

class Article extends Blocks
{
	protected string $id = "article";
	public bool $stackable = false;
	public int $height = 0;

	public function open(): self
	{
		if (!$this->opened) {
			echo "<{$this->id}";
			echo (empty($this->domid) ? "" : " id=\"{$this->domid}\"");
			echo (empty($this->fxwidth) ? "" : " style=\"width:{$this->fxwidth};\" ");
			echo (empty($this->fxMaxwidth) ? "" : " style=\"max-width:{$this->fxMaxwidth};\" ");
			echo is_array($this->options) && in_array("nobg", $this->options) ? " class=\"nobg\" " : "";
			echo is_array($this->options) && in_array("nopadding", $this->options) ? " class=\"nopadding\" " : "";
			echo ">\n";
			$this->opened = true;
		}
		return $this;
	}
}


class Column extends Blocks
{
	protected string $id = "aside";
	public bool $stackable = false;
	public int $height = 0;

	public function open(): self
	{
		if (!$this->opened) {
			echo "<{$this->id} class=\"column\"";
			echo (empty($this->domid) ? "" : " id=\"{$this->domid}\"");
			echo (empty($this->fxMaxwidth) ? "" : " style=\"max-width:{$this->fxMaxwidth};\" ");
			echo is_array($this->options) && in_array("nobg", $this->options) ? " class=\"nobg\" " : "";
			echo is_array($this->options) && in_array("nopadding", $this->options) ? " class=\"nopadding\" " : "";
			echo ">\n";
			$this->opened = true;
		}
		return $this;
	}
}








/**
 * Gremium extension for build a stackable HTML object with a sticky blocks
 */
class Gremium
{
	private bool $isColumnAdded = false;
	/**
	 * Base `top` attribute for block elements
	 * @var string
	 */
	public string $base = "var(--root--menubar-height)";

	/**
	 * Specify if `legend` block are stackable
	 * @var bool
	 */
	private bool $legends_stackable = true;
	/**
	 * Class stack for Blocks elements
	 * @var array
	 */
	private array $stack;


	/**
	 * Open Gremium HTML Tags
	 * @param bool $limit_width
	 * @param mixed $legends_stackable
	 */
	public function __construct(bool $limit_width = true, ?bool $legends_stackable = true, ?bool $omit_html = false, ?string $html_id = null)
	{
		$this->legends_stackable = $legends_stackable;
		$this->stack             = array();
		if (!$omit_html) {
			echo "<div " . (!is_null($html_id) ? " id=\"$html_id\" " : "") . " class=\"gremium\">\n";
			echo "\t<div class=\"content " . ($limit_width ? "limit-width" : "") . "\">\n";
		}
	}


	/**
	 * Add a new block to the stack
	 * 
	 * @param \System\Template\Gremium\Blocks $block
	 * @throws \System\Exceptions\Gremium\StockOpenException
	 * @return \System\Template\Gremium\Blocks
	 */
	public function add(Blocks $block): Blocks
	{
		if (sizeof($this->stack) > 0 && $this->stack[sizeof($this->stack) - 1]->isOpen()) {
			throw new StockOpenException();
		}
		$topcalc = 0;
		foreach ($this->stack as $stackblock) {
			if ($stackblock->sticky() && $stackblock->stackable) {

				if (($stackblock instanceof Legend || $stackblock instanceof Title) && !$this->legends_stackable) {
					$topcalc += 0;
				} else {
					$topcalc += $stackblock->height;
				}
			}

		}
		$block->top($this->base . ($topcalc > 0 ? " + {$topcalc}px" : ""));
		array_push($this->stack, $block);

		return $block;
	}


	/**
	 * Add a new column to the stack
	 * @return \System\Template\Gremium\Column
	 */
	public function column(?string $domid = null): Column
	{
		if ($this->isColumnAdded) {
			throw new \Exception("Template column already added");
		}
		$this->isColumnAdded = true;
		return $this->add(new Column($domid));
	}


	/**
	 * Add a new header to the stack
	 * @return \System\Template\Gremium\Header
	 */
	public function header(?string $domid = null): Header
	{
		return $this->add(new Header($domid));
	}
	/**
	 * Add a new menu to the stack
	 * @return \System\Template\Gremium\Menu
	 */
	public function menu(?string $domid = null): Menu
	{
		return $this->add(new Menu($domid));
	}
	/**
	 * Add a new legend to the stack
	 * @return \System\Template\Gremium\Legend
	 */
	public function legend(?string $domid = null): Legend
	{
		return $this->add(new Legend($domid));
	}
	public function title(?string $domid = null): Title
	{
		return $this->add(new Title($domid));
	}

	/**
	 * Add a new article
	 * @return \System\Template\Gremium\Article
	 */
	public function article(?string $domid = null): Article
	{
		return $this->add(new Article($domid));
	}
	/**
	 * Get last block from the stack
	 * @return \System\Template\Gremium\Blocks|bool
	 */
	public function getLast(): Blocks|bool
	{
		if (sizeof($this->stack) > 0 && $this->stack[sizeof($this->stack) - 1]->isOpen()) {
			return $this->stack[sizeof($this->stack) - 1];
		} else {
			return false;
		}
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

	public function terminate(bool $bottomPadding = false)
	{
		if ($bottomPadding) {
			echo "<div style=\"height: calc(100vh - var(--gremium-header-height) - var(--root--menubar-height));\"></div>";
		}
		echo "</div>";
		echo "</div>";
	}
	/**
	 * Summary of __destruct
	 */
	public function __destruct()
	{
		//$this->terminate();
	}

}
