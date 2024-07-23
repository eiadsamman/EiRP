<?php
use System\Personalization\Bookmark;
$bookmarks = new Bookmark($app);
$limit = 0;
foreach ($bookmarks->list() as $bookmark) {
	if ($limit >= 5)
		break;
	if ($limit == 0) {
		echo "<div class=\"links rowclicks\"><div>";
	}
	echo "<a href=\"{$bookmark['trd_directory']}\">";
	echo "<span style=\"color:var(--root-font-color);background-color:var(--static-bgcolor);\">&#xe{$bookmark['trd_attrib4']};</span>";//background-color:#{$bookmark['trd_attrib5']}
	echo "<div>{$bookmark['pfl_value']}</div>";
	echo "</a>";
	$limit++;
}
if ($limit != 0) {
	echo "</div>";
	echo "<a href=\"{$fs(263)->dir}\">Manage bookmarks</a>";
	echo "</div>";
}