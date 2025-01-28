<?php

if (!empty($app->settings->site['environment']) && $app->settings->site['environment'] === "development") {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 24 * 3600) . ' GMT');
	header("Cache-Control: no-cache, no-store, must-revalidate");
	header("Pragma: no-cache");
} else {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
	header("Cache-Control: public, immutable, max-age=3600");
	header("Pragma: cache");
}

header('Content-Type: application/javascript');

echo "const Route = " . System\Routes\Routes::groupBuildJSON($app) . ";\n";
echo "export default Route;";