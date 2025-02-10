<?php


function replaceARABIC($str)
{
	$str = str_replace(["أ", "إ", "آ"], "[أإاآ]+", $str);
	$str = str_replace(["ة", "ه"], "[ةه]+", $str);
	$str = str_replace(["ى", "ي"], "[يى]+", $str);
	return $str;
}
if (!isset($_POST['limit']) || !isset($_POST['role'])) {
	exit;
}
$role  = $_POST['role'];
$limit = (int) $_POST['limit'];
if ($limit > 15) {
	$limit = 15;
}

$rl = [];
require_once("website-contents/slo.database.php");
if (!isset($rl[$role])) {
	exit;
}

header("Content-Type: application/json; charset=utf-8");

//Initiate startup variables
$q     = preg_replace('/[^\p{Arabic}\da-z_\- ]/ui', " ", $_POST['query']);
$sq    = ' ';
$i     = 0;
$cols  = $rl[$role]['search'];
$q     = trim($q);
$smart = "";

//Handle various search keys
if ($q == "") {
	$sq .= "(";
	foreach ($cols as $k => $v) {
		$sq .= "$smart $k RLIKE '.*' ";
		$smart = " OR ";
	}
	$sq .= " )";
} else {
	$q = explode(" ", $q);
	for ($i = 0; $i < sizeof($q); $i++) {
		$sq .= "(";
		$smart = "";
		foreach ($cols as $k => $v) {
			$sq .= $smart . " $k RLIKE '.*" . replaceARABIC($q[$i]) . ".*' ";
			$smart = " OR ";
		}
		$sq .= ")";
		if ($i != sizeof($q) - 1)
			$sq .= ' AND ';
	}
}

$rl[$role]['hide'] ??= [];

$select_statment = array_merge(
	$rl[$role]['select'],
	$rl[$role]['details'],
	$rl[$role]['id'],
	$rl[$role]['value'],
	$rl[$role]['params'] ?? [],
);
$select_statment = array_unique($select_statment);
if (sizeof($select_statment) == 0) {
	exit;
}
$selectquery = "";
$smart       = "";
foreach ($select_statment as $k => $v) {
	if ($k == $v) {
		$selectquery .= $smart . $k;
	} elseif ($k == "-") {
		$selectquery .= $smart . $v . " ";
	} else {
		$selectquery .= $smart . "$v AS " . $k;
	}
	$smart = ",";
}
$sq = trim($sq);


$q  =
	"SELECT
		$selectquery
	FROM
		" . $rl[$role]['from'] . "
		" . ($sq != "" || trim((string) $rl[$role]['where']) != "" ? "WHERE" : "") . "
			" . $sq . "
			" . ($sq != "" && trim((string) $rl[$role]['where']) != "" ? " AND " . $rl[$role]['where'] : "") . "
		" . (isset($rl[$role]['group']) && trim((string) $rl[$role]['group']) != "" ? " GROUP BY " . $rl[$role]['group'] . " " : "") . "
		" . (isset($rl[$role]['union']) && trim((string) $rl[$role]['union']) != "" ? " " . $rl[$role]['union'] . " " : "") . "
		" . (isset($rl[$role]['order']) && is_array($rl[$role]['order']) ? " ORDER BY " . implode(",", $rl[$role]['order']) : "") . "
	LIMIT 0, $limit";
$li = [];
if ($r = $app->db->query($q)) {
	while ($row = $r->fetch_assoc()) {
		$item = [
			"id" => "",
			"value" => [],
			"select" => [],
			"details" => [],
			"params" => []
		];

		foreach ($item as $key => $data) {
			if (array_key_exists($key, $rl[$role]))
				foreach ($rl[$role][$key] as $msel => $msev) {
					if (isset($row[$msel]) && !is_null($row[$msel]) && !in_array($msel, $rl[$role]['hide'])) {
						if (is_array($item[$key])) {
							$item[$key][$msel] = $row[$msel];
						} else {
							$item[$key] = $row[$msel];
						}
					}
				}
		}

		$li[] = $item;
	}
	echo json_encode($li);
} else {
	echo json_encode(["error" => "SQL Error", "errno" => __LINE__]);
}

