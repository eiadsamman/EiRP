<?php
use System\Views\PanelView;

header('Content-Type: application/javascript');
/* header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
header("Cache-Control: public, immutable, max-age=3600");
header("Pragma: cache"); */

header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 24 * 3600) . ' GMT');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");

$view = new PanelView($app);
echo <<<JS
const Route = {$view->groupBuildJSON()};

export default Route;

JS;
exit;