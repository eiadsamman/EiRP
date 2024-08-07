<?php

declare(strict_types=1);

namespace System\Template;

class Body extends Config
{
	private $domseq = 0;
	public function Title($title, $icon, $status, $iconc = false)
	{
		$this->titlePlotted = $this->stickyLayoutTitle;
		$css_position = $this->titlePlotted ? "position: sticky;" : "";

		echo "
			<div class=\"template-pageTitle\" style=\"top: var(--root--menubar-height);{$css_position};" . (!is_null($this->width) ? "max-width: var(--gremium-width);" : "") . "\">
				" . ($iconc ? "<span style=\"font-size:1rem;\" class=\"$iconc\"></span>" : "") . "
				<div>$title</div>
				<div>$status</div>
			</div>";
	}

	public function ShiftStickyStart($amount)
	{
		$this->topBasePoint += $amount;
	}
	public function EmulateHeaders()
	{
		$this->titlePlotted = $this->stickyLayoutTitle;
		$this->commandPlotted = $this->stickyLayoutCommandBar;
	}

	public function CommandBar(string $content = ""): void
	{
		echo $this->CommandBarStart();
		echo "<div class=\"btn-set\">" . $content . "</div>";
		echo $this->CommandBarEnd();
	}
	public function CommandBarStart(): string
	{
		$this->commandPlotted = $this->stickyLayoutCommandBar;
		$css_position = $this->commandPlotted ? "position: sticky;" : "";
		$_topvalue = $this->topCommandBar;
		$_topvalue -= $this->titlePlotted ? 0 : $this->titleHeight;
		return "<div class=\"template-commandBar\" style=\" top:{$_topvalue}px;{$css_position};" . (!is_null($this->width) ? "max-width: var(--gremium-width);" : "") . "\">";
	}
	public function CommandBarEnd(): string
	{
		return "</div>";
	}



	public function NewFrameTitle($content, bool $limitWidth = false, bool $toggleBody = false, int $addOffset = 0): void
	{
		echo $this->NewFrameTitleStart($limitWidth, $toggleBody, $addOffset);
		echo "<div class=\"btn-set\">" . $content . "</div>";
		echo $this->NewFrameTitleEnd();
	}

	public function NewFrameTitleStart(bool $limitWidth = false, bool $toggleBody = false, int $addOffset = 0): string
	{
		$css_position = $this->stickyLayoutFrame ? "position: sticky;" : "";
		$_topvalue = $this->topBasePoint;
		$_topvalue += $this->titlePlotted ? $this->titleHeight : 0;
		$_topvalue += $this->commandPlotted ? $this->commandBarHeight : 0;

		$_topvalue += $this->frameTitleStack ? 0 : ($this->framesCount * $this->topJumpPeriod);
		$_topvalue += $addOffset;

		$this->framesCount += 1;
		$this->domseq += 1;
		return "<div " .
			($toggleBody ? "data-templatebody=\"jsDOMtemplateBody{$this->domseq}\" " : "") .
			"class=\"template-frameTitle\" 
						style=\"top:{$_topvalue}px;{$css_position};"
			. (!is_null($this->width) ? ";max-width: var(--gremium-width);" : "")
			. ($this->framesCount == 999 ? ";padding-top:0px;" : "")
			. "\">";
	}
	public function NewFrameTitleEnd(): string
	{
		return "</div>";
	}





	/**
	 * Start body panel
	 * 
	 * @param {string} $content Body HTML content
	 * @param {bool|int} $limitWidth Dynamic or Static body width
	 * @param {bool} $visible Body visibility at start
	 * @return void
	 */
	public function NewFrameBody($content, $limitWidth = false, $visible = true): void
	{
		echo $this->NewFrameBodyStart($limitWidth, $visible)
			. $content
			. $this->NewFrameBodyEnd();
	}

	public function NewFrameBodyStart($limitWidth = false, $visible = true): string
	{
		$visible = $visible ? "" : "display:none;";
		return "<div id=\"jsDOMtemplateBody{$this->domseq}\" class=\"template-frameBody\" 
					style=\"{$visible};" . ($limitWidth ? "max-width: {$limitWidth}px;" : "") . (!is_null($this->width) ? "max-width:var(--gremium-width);": "") . "\">
				<div>";
	}
	public function NewFrameBodyEnd(): string
	{
		return "</div>
			</div>";
	}
	public function TailGap(): void
	{
		echo "<div style=\"margin-top:50%;\"></div>";
	}





	public static function AttendanceTicketPlot($status, $image, $title, $content, $fade = false)
	{
		echo "<div>";
		echo $status === null ? "" : "<span class=\"status " . ($status ? "s" : "f") . "\" " . ($fade ? " style=\"color:#ccc\"" : "") . "><div>" . ($status ? "&#xf00c" : "&#xf00d") . "</div></span>";
		echo "<span class=\"image\" style=\"background-image:url('$image');" . ($fade ? "opacity:0.5; filter: grayscale(100%);-webkit-filter: grayscale(100%);" : "") . "\"></span>
			<span class=\"content\"><div class=\"employee-sid\">{$title}</div><div class=\"employee-name\">{$content}</div></span></div>";
	}
}