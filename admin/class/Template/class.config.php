<?php
namespace Template\Settings;

class Config{
	protected $colormap;
	protected $frameTitleStack = false;
	protected $framesCount = 0;
	protected $titlePlotted = false;
	protected $commandPlotted = false;
	protected $stickyLayoutTitle = true;
	protected $stickyLayoutCommandBar = true;
	protected $stickyLayoutFrame = true;
	protected $sidePanel = false;
	protected $titleHeight = 65;
	protected $commandBarHeight = 40;
	protected $width = null;
	
	public $topBasePoint = 42;
	public $topCommandBar = 107;
	public $topJumpPeriod = 40;
	
	public function __construct($template_name="",$side_panel=false) {
		$this->colormap=$template_name;
		$this->sidePanel = $side_panel;
	}
	
	public function SetWidth(string $width):void{
		$this->width=$width;
	}
	
	public function SetLayout(bool $sticky_title=true, bool $sticky_commandbar=true,bool $sticky_frames=true): void{
		$this->stickyLayoutTitle 		= $sticky_title;
		$this->stickyLayoutCommandBar 	= $sticky_commandbar;
		$this->stickyLayoutFrame 		= $sticky_frames;
	}
	
	public function FrameTitlesStack(bool $stack=false): void{
		$this->frameTitleStack = $stack;
	}
	
}
?>