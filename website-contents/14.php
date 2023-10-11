<?php
use System\Personalization\Bookmark;

$bookmarks = new Bookmark($app);
$limit     = 0;
foreach ($bookmarks->list() as $bookmark) {
	if ($limit >= 5)
		break;
	if ($limit == 0) {
		echo "<div class=\"widgetWQT\"><div>";
	}
	echo "<a href=\"{$bookmark['trd_directory']}\">
	<span " . ($bookmark['trd_attrib5'] != null ? "style=\"font-family:icomoon4;\"" : "font-family:icomoon4;") . ">&#xe{$bookmark['trd_attrib4']};</span>{$bookmark['pfl_value']}</a>";
	$limit++;
}
if ($limit != 0) {
	echo "</div></div>";
}