<?php
use System\Personalization\Bookmark;

$bookmarks = new Bookmark($app);
echo "<div class=\"widgetWQT\"><div>";
$limit = 0;

foreach ($bookmarks->list() as $bookmark) {
	$limit++;
	if ($limit > 5)
		break;
	echo "<a href=\"{$bookmark['trd_directory']}\">
		<span " . ($bookmark['trd_attrib5'] != null ? "style=\"font-family:icomoon4;\"" : "font-family:icomoon4;") . ">&#xe{$bookmark['trd_attrib4']};</span>" . $bookmark['pfl_value'] . "</a>";

}
echo "</div></div>";