<?php

use System\IO\AttachLib;

/*
Crit v2.1 210818
0:	string|null 				Alias
1:	string						Field title
2:	true|false					Display output field column
3:	null|#px|#%					Field width
4:	primary|hidden|text|slo|sloref	Input type
5:	int|string|float			Table column type
6:	true|false					Process INSERT\UPDATE
7:	null|string					SLO reference field
8:	null|string					SLO field ID
9:	null|string					Feild description
10:	string						Default value
11:	bool						Disable field editing 
12:	string						Null output alternative

'table'=>string
'tableselect'=>string
'tablename'=>string
'flexablewidth'=>array()
'order'=>array()
'fields'=>array()
'search'=>array()
'perpage'=>int
'disable-delete'=>bool
'readonly'=>bool

*/

function UploadDOM($fileID, $fileMime, $fileTitle, $fileSelected = false, $domField = "")
{
	return "
	<tr>
		<td class=\"checkbox\"><label><input name=\"{$domField}[]\" value=\"$fileID\" type=\"checkbox\"" . ($fileSelected ? "checked=\"checked\"" : "") . " /></label></td>
		<td class=\"op-remove\" data-id=\"$fileID\"><span></span></td>
		<td class=\"content\"><a class=\"js_upload_view\" target=\"_blank\" data-mime=\"$fileMime\" href=\"download/?id=$fileID&amp;pr=v\" data-href=\"download/?pr=v&amp;id=$fileID\">$fileTitle</a></td>
	</tr>";
}

$debug = false;
$database['primary'] = null;
$pageper = 20;
$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");

if (isset($database['perpage']) && (int) $database['perpage'] > 0) {
	$pageper = $database['perpage'];
}


foreach ((array) $database['fields'] as $fieldk => $fieldv) {
	if ($fieldv[4] == "primary") {
		if ($database['primary'] != null) {
			die("Primary ID conflict");
		}
		$database['primary'] = $fieldk;
	}
}
if (is_null($database['primary'])) {
	die("Primary ID is missing!");
}


$database['hash'] = array('l' => array(), 'r' => array());
foreach ($database['fields'] as $key => $value) {
	$database['hash']['l'][md5("MEdH265" . $key)] = $key;
	$database['hash']['r'][$key] = md5("MEdH265" . $key);
}



function single_call(&$app, $database, $id)
{
	$tempquery = "SELECT ";
	$smart = "";
	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] != "file") {
			$tempquery .= $smart . (isset($fieldv[0]) && $fieldv[0] != null ? "{$fieldv[0]} AS " : "") . "$fieldk";
			$smart = ",";
		}
	}
	$tempquery .= " FROM {$database['tableselect']} ";
	$tempquery .= isset($database['where']) ? " WHERE " . $database['where'] . " AND {$database['primary']}=$id " : " WHERE {$database['primary']}=$id ";
	if ($r = $app->db->query($tempquery)) {
		if ($temprow = $r->fetch_assoc()) {
			return $temprow;
		}
	} else {
		return false;
	}
	return false;
}
function sqlvalue($type, $value, $isset)
{
	if ($type == "int") {
		return $isset && trim($value) != "" ? (int) ($value) : "NULL";
	} elseif ($type == "float") {
		return $isset && trim($value) != "" ? (float) ($value) : "NULL";
	} elseif ($type == "string") {
		return $isset && trim($value) != "" ? "'" . addslashes($value) . "'" : "NULL";
	} else {
		return $isset && trim($value) != "" ? "'" . addslashes($value) . "'" : "NULL";
	}
}


if ($fs()->permission->edit && $fs()->permission->add && isset($_POST['operator'])) {
	if (isset($database['pre_submit_functions']) && is_array($database['pre_submit_functions'])) {
		foreach ($database['pre_submit_functions'] as $func) {
			call_user_func($func, $_POST, $app);
		}
	}

	$attachments = array();
	if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
		foreach ($_POST['attachments'] as $fieldk => $attaches) {
			foreach ($attaches as $filek => $fileid) {
				$attachments[] = (int) $fileid;
			}
		}
	}


	$q = "INSERT INTO {$database['table']} (\n\t\t";
	$smart = "";
	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == "file") {
		} elseif ($fieldv[6] == true) { /*Allow field value updating*/
			$q .= $smart . "$fieldk";
			$smart = ",\n\t\t";
		}
	}

	$q .= "\n) VALUES (\n\t\t";
	$smart = "";
	$idvalue = 0;

	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] != "file") {
			if ($fieldv[6] == true) { /*Allow field value updating*/
				if (($fieldv[4] == 'hidden' || $fieldv[4] == 'text' || $fieldv[4] == 'textarea')) {
					$q .= $smart . sqlvalue($fieldv[5], $_POST[$database['hash']['r'][$fieldk]], isset($_POST[$database['hash']['r'][$fieldk]]));
				} elseif (($fieldv[4] == 'slo')) {
					$q .= $smart . sqlvalue($database['fields'][$fieldv[8]][5], $_POST[$database['hash']['r'][$fieldk]][1], isset($_POST[$database['hash']['r'][$fieldk]][1]));
				} else if ($fieldv[4] == 'default') {
					$q .= $smart . sqlvalue($fieldv[5], $fieldv[10], isset($fieldv[10]));
				} else if ($fieldv[4] == 'primary') {
					$idvalue = (isset($_POST[$database['hash']['r'][$fieldk]]) && (int) $_POST[$database['hash']['r'][$fieldk]] != 0 ? (int) $_POST[$database['hash']['r'][$fieldk]] : 0);
					$q .= $smart . ($idvalue == 0 ? "NULL" : $idvalue);
				} else if ($fieldv[4] == 'bool') {
					$q .= $smart . (isset($_POST[$database['hash']['r'][$fieldk]]) ? "1" : "0");
				}
			} else {
				if ($fieldv[4] == 'slo') {
					$q .= $smart . sqlvalue($database['fields'][$fieldv[8]][5], $_POST[$database['hash']['r'][$fieldk]][1], isset($_POST[$database['hash']['r'][$fieldk]][1]));
				}
			}
			$smart = ",\n\t\t";
		}
	}

	$q .= "\n) ON DUPLICATE KEY UPDATE \n\t\t";

	$smart = "";

	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] != "file") {
			if ($fieldv[6] == true) { /*Allow field value updating*/
				if (($fieldv[4] == 'hidden' || $fieldv[4] == 'text' || $fieldv[4] == 'textarea')) {
					$q .= $smart . "`$fieldk`=VALUES(`$fieldk`)";
				} elseif (($fieldv[4] == 'slo')) {
					$q .= $smart . "`$fieldk`=VALUES(`$fieldk`)";
				} else if ($fieldv[4] == 'default') {
					$q .= $smart . "`$fieldk`=VALUES(`$fieldk`)";
				} else if ($fieldv[4] == 'bool') {
					$q .= $smart . "`$fieldk`=VALUES(`$fieldk`)";
				} else if ($fieldv[4] == "primary") {
					$q .= $smart . "`$fieldk`=LAST_INSERT_ID(`$fieldk`)";
				}
			} else {
				if ($fieldv[4] == 'slo') {
					$q .= $smart . "`{$fieldv[8]}`=VALUES(`{$fieldv[8]}`)";
				}
			}
			$smart = ",\n\t\t";
		}
	}

	$q .= "\n;";


	$r = $app->db->query($q);
	$json_output = array("result" => null, "method" => null, "string" => null);
	if ($r) {
		$affected_id = $app->db->insert_id;


		//Release previous attached files
		foreach ($database['fields'] as $fieldk => $fieldv) {
			if ($fieldv[4] == "file") {
				$app->db->query("UPDATE uploads SET up_rel=0 WHERE up_rel=$affected_id AND up_pagefile={$fieldv[5]};");
			}
		}

		//Attached files
		if (sizeof($attachments) > 0) {
			$app->db->query("UPDATE uploads SET up_rel=$affected_id, up_active = 1 WHERE up_id IN (" . implode(",", $attachments) . ") AND up_user = {$app->user->info->id};");
		}

		if (isset($database['post_submit_functions']) && is_array($database['post_submit_functions'])) {
			foreach ($database['post_submit_functions'] as $func) {
				call_user_func($func, $_POST, $app, $affected_id);
			}
		}
		$json_output['result'] = true;
		$json_output['method'] = $idvalue == 0 ? 0 : 1;
	} else {
		$json_output['result'] = false;
		$json_output['method'] = $idvalue == 0 ? 0 : 1;
		$json_output['string'] = "SQL Query failed, error number " . $app->db->errno;
	}
	echo json_encode($json_output);
	exit;
}


if ($fs()->permission->delete && isset($_POST['method']) && $_POST['method'] == 'delete' && (!isset($database['disable-delete']) || $database['disable-delete'] != true)) {
	$record_id = (int) $_POST['id'];
	try {
		include($app->root . 'admin/class/attachlib.php');
		$ulib = new AttachLib($app);
		foreach ($database['fields'] as $fieldk => $fieldv) {
			if ($fieldv[4] == "file") {
				$r = $app->db->query("SELECT up_id FROM uploads WHERE up_pagefile = {$fieldv[5]} AND up_rel = {$record_id}");
				while ($attrow = $r->fetch_assoc()) {
					$ulib->delete($attrow['up_id']);
				}
			}
		}
	} catch (Exception $e) {
	}

	$output = "0";
	try {
		if ($r = $app->db->query("DELETE FROM {$database['table']} WHERE {$database['primary']} = {$record_id};")) {
			if ($app->db->affected_rows > 0) {
				$output = "1";
			} else {
				$output = "0";
			}
		}
	} catch (Exception $e) {
		$output = "0";
	}
	echo $output;
	exit;
}


if ($fs()->permission->edit && $fs()->permission->add && isset($_POST['ea_prepare'], $_POST['id']) && (!isset($database['readonly']) || !$database['readonly'])) {
	$_POST['id'] = (int) $_POST['id'];
	$cleaned = $database['fields'];
	foreach ($cleaned as $k => $v) {
		$cleaned[$k] = "";
	}
	$op_type = null;
	if ((int) $_POST['id'] == 0) {
		$op_type = "add";
		$cleaned[$database['primary']] = (int) $_POST['id'];
	} else {
		$op_type = "edit";
		if ($cleaned = single_call($app, $database, (int) $_POST['id'])) {
		} else {
			echo "Fetching record information failed";
			exit;
		}
	}

	echo "
	<div>
		<div>
			<form action=\"{$fs()->dir}/\" method=\"post\" id=\"jQform\"><input type=\"hidden\" name=\"operator\" />";


	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == 'primary') {
			echo "<input type=\"hidden\" name=\"{$database['hash']['r'][$fieldk]}\" id=\"{$database['hash']['r'][$fieldk]}\" value=\"" . (isset($cleaned[$fieldk]) ? $cleaned[$fieldk] : "") . "\" />";
		} elseif ($fieldv[4] == 'hidden') {
			echo "<input type=\"hidden\" name=\"{$database['hash']['r'][$fieldk]}\" id=\"{$database['hash']['r'][$fieldk]}\" value=\"" . (isset($fieldv[10]) && $fieldv[10] != null ? $fieldv[10] : "") . "\" />";
		}
	}
	echo "<table class=\"bom-table hover\">
			<thead><tr class=\"special\"><td colspan=\"3\"><span id=\"jQcomtitle\"><span class=\"vs-add\"><span></span></span>" . ((int) $_POST['id'] == 0 ? "Add a new record to " : "Modify an existing record from ") . "</span>`{$database['tablename']}`</td></tr></thead>
			<tbody>";

	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == 'text') {
			echo "<tr>
						<th style=\"min-width:130px\">{$fieldv[1]}</th>
						<td style=\"min-width:400px\">
							<div class=\"btn-set normal\"><input type=\"text\" id=\"{$database['hash']['r'][$fieldk]}\" class=\"flex\" style=\";max-width:400px;\" name=\"{$database['hash']['r'][$fieldk]}\" class=\"text\" 
							" . (isset($fieldv[6]) && $fieldv[6] == false ? " readonly=\"readonly\" " : "") . "
							value=\"" . (isset($cleaned[$fieldk]) ? htmlspecialchars($cleaned[$fieldk], ENT_QUOTES) : "") . "\" 
							" . (isset($fieldv[11]) && $fieldv[11] && $op_type == "edit" ? " readonly=\"readonly\" " : "") . " /></div>
						</td>
						<td class=\"css_fieldDesc\"><span>" . (isset($fieldv[9]) ? $fieldv[9] : "") . "</span></td></tr>";
		} elseif ($fieldv[4] == 'slo') {
			echo "<tr>
						<th style=\"min-width:130px\">{$fieldv[1]}</th>
						<td>
							<div class=\"btn-set normal\">
								<input type=\"text\" id=\"{$database['hash']['r'][$fieldk]}\" data-slo=\"{$fieldv[7]}\" class=\"flex\" style=\"max-width:400px;\" 
								" . (isset($cleaned[$fieldk]) ? " value=\"" . $cleaned[$fieldk] . "\" " : "") . " " . (isset($cleaned[$fieldv[8]]) ? " data-slodefaultid=\"" . $cleaned[$fieldv[8]] . "\" " : "") . " name=\"{$database['hash']['r'][$fieldk]}\" />
							</div>
						</td>
						<td class=\"css_fieldDesc\"><span>" . (isset($fieldv[9]) ? $fieldv[9] : "") . "</span></td></tr>";
		} elseif ($fieldv[4] == 'bool') {
			echo "<tr>
						<th style=\"min-width:130px\">{$fieldv[1]}</th>
						<td><label class=\"ios-io\"><input type=\"checkbox\" id=\"{$database['hash']['r'][$fieldk]}\" name=\"{$database['hash']['r'][$fieldk]}\" " . (isset($cleaned[$fieldk]) && (int) $cleaned[$fieldk] == 1 ? " checked=\"checked\" " : "") . " class=\"change-we-status\" /><span>&nbsp;</span><div></div></lable></td>
						<td class=\"css_fieldDesc\"><span>" . (isset($fieldv[9]) ? $fieldv[9] : "") . "</span></td></tr>";
		} elseif ($fieldv[4] == 'textarea') {
			echo "<tr>
						<th style=\"min-width:130px\">{$fieldv[1]}</th>
						<td style=\"min-width:400px\">
							<div class=\"btn-set normal\"><textarea id=\"{$database['hash']['r'][$fieldk]}\" class=\"flex\" style=\"max-width:400px;min-width:400px;height:100px\" name=\"{$database['hash']['r'][$fieldk]}\" class=\"text\" 
							 " . (isset($fieldv[11]) && $fieldv[11] && $op_type == "edit" ? " readonly=\"readonly\" " : "") . ">" . (isset($cleaned[$fieldk]) ? $cleaned[$fieldk] : "") . "</textarea></div>
						</td>
						<td class=\"css_fieldDesc\"><span>" . (isset($fieldv[9]) ? $fieldv[9] : "") . "</span></td></tr>";
		} elseif ($fieldv[4] == 'file') {
			// accept=\"image/*\" Here

			$_uploads_query = $app->db->query(
				"SELECT 
					up_id, up_name, up_size, up_date, up_pagefile, up_mime, up_rel 
				FROM 
					uploads 
				WHERE 
					(up_rel={$_POST['id']} OR up_rel = 0)
					AND up_deleted = 0 
					AND up_pagefile = {$fieldv[5]} 
				ORDER BY 
					up_rel DESC, up_date DESC;");

			echo <<<HTML
				<tr>
					<th style="min-width:130px">{$fieldv[1]}</th>
					<td style="min-width:400px">
						<div class="btn-set" style="justify-content:left">
							<input id="up_trigger{$fieldk}" class="js_upload_trigger" type="button" value="Upload" />
							<input type="file" id="up_btn{$fieldk}" class="js_uploader_btn" multiple="multiple" />
							<span id="up_list{$fieldk}" class="js_upload_list">
								<div id="up_handler{$fieldk}">
									<table class="bom-table hover">
										<tbody>
			HTML;
			while ($_uploads_query && $_uploads_row = $_uploads_query->fetch_assoc()) {
				echo UploadDOM(
					$_uploads_row['up_id'],
					(in_array($_uploads_row['up_mime'], $accepted_mimes) ? "image" : "document"),
					$_uploads_row['up_name'],
					((int) $_uploads_row['up_rel'] == 0 ? false : true),
					"attachments[$fieldk]"
				);
			}
			echo <<<HTML
										</tbody>
									</table>
								</div>
							</span>
							<span id="up_count{$fieldk}" class="js_upload_count"><span>0 / 0</span></span>
						</div>
					</td>
					<td class="css_fieldDesc"><span>{$fieldv[9]}</span></td>
				</tr>
			HTML;
		}
	}

	echo "
		<tr><td align=\"left\" colspan=\"3\" id=\"jQcommands\">
			<div class=\"btn-set\" style=\"justify-content:center\"><button type=\"submit\" id=\"jQsubmit\">" . ((int) $_POST['id'] == 0 ? "Add" : "Modify") . "</button><input type=\"button\" id=\"jQcancel\" value=\"Cancel\" /></div>
		</td></tr>
	</tbody>
	</table>
	</form></div></div>";

	exit;
}


if (isset($_POST['method'], $_POST['page']) && $_POST['method'] == "populate") {
	$_POST['page'] = (int) $_POST['page'];
	$totalRecords = 0;
	$pagecurrent = 1;
	$pagecount = 1;

	$searchCriteria = "";
	$searchQuery = "";
	$searchOccurrenece = 0;

	/*Search function*/
	if (isset($_POST['search'])) {
		parse_str($_POST['search'], $searchCriteria);
		foreach ($database['fields'] as $fieldk => $fieldv) {
			if (isset($searchCriteria['search_' . $database['hash']['r'][$fieldk]])) {
				if ($fieldv[4] == 'text' || $fieldv[4] == 'textarea') {
					if ($fieldv[5] == "string" && trim($searchCriteria['search_' . $database['hash']['r'][$fieldk]]) != "") {
						$sq = " AND (1 ";
						$q = explode(" ", ($searchCriteria['search_' . $database['hash']['r'][$fieldk]]));
						for ($i = 0; $i < sizeof($q); $i++) {
							$sq .= "AND  $fieldk RLIKE '.*" . ($q[$i]) . ".*' ";
						}
						$sq .= ")";
						$searchQuery .= " $sq ";
						$searchOccurrenece++;
					} elseif ($fieldv[5] == "int" && is_numeric($searchCriteria['search_' . $database['hash']['r'][$fieldk]])) {
						$searchQuery .= " AND {$fieldk} = " . (int) $searchCriteria['search_' . $database['hash']['r'][$fieldk]];
						$searchOccurrenece++;
					} elseif ($fieldv[5] == "float" && is_numeric($searchCriteria['search_' . $database['hash']['r'][$fieldk]])) {
						$searchQuery .= " AND {$fieldk} = " . (float) $searchCriteria['search_' . $database['hash']['r'][$fieldk]];
						$searchOccurrenece++;
					}
				} elseif ($fieldv[4] == 'slo' && (isset($searchCriteria['search_' . $database['hash']['r'][$fieldk]][1]) && (int) ($searchCriteria['search_' . $database['hash']['r'][$fieldk]][1]) != 0)) {
					$searchQuery .= " AND {$fieldv[8]} = " . ((int) $searchCriteria['search_' . $database['hash']['r'][$fieldk]][1]);
					$searchOccurrenece++;
				} elseif ($fieldv[4] == 'bool') {
					//$searchQuery.=" AND {$fieldk} = ".((int)$searchCriteria['search_'.$fieldk][1]);
				} elseif ($fieldv[4] == 'file') {
					//$searchQuery.=" AND {$fieldk} = ".((int)$searchCriteria['search_'.$fieldk][1]);
				} elseif ($fieldv[4] == 'primary' && (int) $searchCriteria['search_' . $database['hash']['r'][$fieldk]] != 0) {
					$searchQuery .= " AND {$fieldk} = " . (int) $searchCriteria['search_' . $database['hash']['r'][$fieldk]];
					$searchOccurrenece++;
				}
			}
		}
	}

	$database['count'] = "SELECT COUNT({$database['primary']}) AS rowsCount ";
	$database['count'] .= "\nFROM {$database['tableselect']} ";
	$database['count'] .= " WHERE 1 " . (isset($database['where']) ? " AND " . $database['where'] : "") . $searchQuery;

	//Debug::Write($database['count'],__FILE__,__LINE__);


	if ($r = $app->db->query($database['count'])) {
		if ($row = $r->fetch_assoc()) {
			$totalRecords = $row['rowsCount'];
			$pagecount = ceil($totalRecords / $pageper);
		}
	}

	$pagecurrent = $_POST['page'];
	$pagecurrent = $pagecurrent > $pagecount ? $pagecount : $pagecurrent;
	$pagecurrent = $pagecurrent < 1 ? 1 : $pagecurrent;
	$pagenav = ((int) $pagecurrent - 1) * $pageper;


	$filehandler = "";

	$database['query'] = "SELECT \n\t";
	$smart = "";
	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == "file") {
			$database['query'] .= $smart . "\n\t" . "{$fieldk}.count AS {$fieldk}_count";
			$database['query'] .= $smart . "\n\t" . "{$fieldk}.up_id AS {$fieldk}_id";
			$filehandler .= "
		LEFT JOIN 
		(
			SELECT 
				COUNT(up_id) AS count, up_rel, up_id
			FROM 
				uploads 
			WHERE 
				up_pagefile = {$fieldv[5]} 
			GROUP BY 
				up_rel
		) AS {$fieldk} ON {$fieldk}.up_rel = {$database['primary']} 
	";
		} else {
			$database['query'] .= $smart . "\n\t" . (isset($fieldv[0]) && $fieldv[0] != null ? "{$fieldv[0]} AS " : "") . "$fieldk";
		}

		$smart = ",";
	}
	$database['query'] .= "\nFROM {$database['tableselect']} " . $filehandler;
	$database['query'] .= " WHERE 1 " . (isset($database['where']) ? " AND " . $database['where'] : "") . $searchQuery;
	$database['query'] .= (isset($database['order']) && sizeof($database['order']) > 0 ? " ORDER BY " . implode(",", $database['order']) : "");
	$database['query'] .= " LIMIT $pagenav,$pageper ";

	if ($r = $app->db->query($database['query'])) {

		echo "<tr style=\"display:none;\"><td id=\"LegendParams\" data-pagecount=\"$pagecount\" data-pagecurrent=\"{$pagecurrent}\" data-total=\"$totalRecords\" data-searchoccurrence=\"$searchOccurrenece\"></td></tr>";
		while ($row = $r->fetch_assoc()) {
			echo "<tr data-id=\"{$row[$database['primary']]}\">";
			if ($fs()->permission->delete && !(isset($database['disable-delete']) && $database['disable-delete'] == true) && (!isset($database['readonly']) || !$database['readonly'])) {
				echo "<td class=\"op-remove noselect\"><span></span></td>";
			}
			if ($fs()->permission->edit && (!isset($database['readonly']) || !$database['readonly'])) {
				echo "<td class=\"op-edit noselect\"><span></span></td>";
			}
			foreach ($database['fields'] as $fieldk => $fieldv) {
				if ($fieldv[2] == true) {
					if (isset($database['flexablewidth']) && in_array($fieldk, $database['flexablewidth'])) {
						echo "<td class=\"css_maxfieldwidth\"><span>";
					} else {
						echo "<td " . ($fieldv[4] == "file" ? "style=\"padding:0px;\"" : "") . ">";
					}

					if ($fieldv[4] == "bool") {
						echo ((int) $row[$fieldk] == 1 ? "Yes" : "");
					} elseif ($fieldv[4] == "file") {
						if (is_null($row[$fieldk . "_count"])) {
							echo "-";
						} else {
							echo "<div class=\"editor_miniuploads\" style=\"background-image:url('download/?id={$row[$fieldk . "_id"]}&pr=t');\"><span>{$row[$fieldk . "_count"]}</span></div>";
						}
					} else {
						if (isset($fieldv[12])) {
							echo (is_null($row[$fieldk]) ? $fieldv[12] : htmlentities($row[$fieldk]));
						} elseif ($fieldv[4] == "textarea") {
							echo (htmlentities(is_null($row[$fieldk]) ? "" : $row[$fieldk])); //nl2br
						} else {
							echo htmlentities(is_null($row[$fieldk]) ? "" : $row[$fieldk]);
						}
					}

					if (isset($database['flexablewidth']) && in_array($fieldk, $database['flexablewidth'])) {
						echo "</span></td>";
					} else {
						echo "</td>";
					}
				}
			}
			echo "</tr>";
		}
	} else {
		echo "<tr><td>{$app->db->error}<br />{$database['query']}</td></tr>";
	}
	exit;
}


?>
<style type="text/css">
	.editor_miniuploads {
		display: block;
		cursor: pointer;
		background-color: var(--root-background-color);
		background-size: auto 100%;
		background-repeat: no-repeat;
		height: 30px;
		padding-left: 30px;
		padding-right: 3px;
		padding-top: 3px;
	}

	.editor_miniuploads>span {
		display: block;
		border: solid 1px #ccc;
		padding: 4px 7px 6px 7px;
		height: 24px;
		border-radius: 3px;
		background-color: rgba(255, 255, 255, 0.5);
	}

	.editor_miniuploads:hover>span {
		background-color: rgba(255, 255, 255, 0.5);
		border-color: #333;
	}

	.menu_screen {
		position: relative;
		width: 0px;
		padding: 0px;
		border: none;
		margin: 0px;
	}

	.menu_screen>div {
		display: none;
		border: solid 1px #ccc;
		font-weight: normal;
		position: absolute;
		z-index: 11;
		white-space: normal;
		top: 31px;
		width: 130px;
		padding: 0px;
		height: auto;
		background-color: var(--root-background-color);
		left: -1px;
		max-height: 212px;
		overflow: auto;
	}

	.menu_screen>div>span {
		cursor: default;
		display: block;
		padding: 5px 10px;
		border: solid 1px transparent;
	}

	.menu_screen>div>span:hover {
		background-color: rgba(82, 168, 236, .1);
		border-color: rgba(82, 168, 236, .75);
		text-decoration: none;
	}
</style>

<div style="padding:20px 0px 20px 0px;min-width:800px;background-color: var(--root-background-color);position: sticky;top:50px;z-index: 20;">
	<div class="btn-set flex" id="jQnavigator">
		<?php
		if (!isset($database['readonly']) || (isset($database['readonly']) && !$database['readonly'])) {
			echo '<button type="button" class="plus" id="jQbtnAdd"></button>';
		}
		?>

		<input type="button" id="jQbtnRefresh" value="Refresh" />
		<input type="text" style="text-align:center;width:80px;" readonly id="jQnavTotal" value="0" />
		<input disabled="disabled" id="jQnavFirst" type="button" value="First" />
		<input disabled="disabled" id="jQnavPrev" type="button" value="Previous" />
		<input type="hidden" name="offset" id="jQoffset" />
		<b id="jQpageination" class="menu_screen">
			<div>0/0</div>
		</b>
		<input type="text" style="text-align:center;width:80px;" readonly id="jQnavTitle" value="0/0" />
		<input disabled="disabled" id="jQnavNext" type="button" value="Next" />

	</div>

	<div style="position: relative;margin-top: 10px;">
		<span style="padding: 0;width:0px;border:none;">
			<div style="display:none;position: absolute;background-color: var(--root-background-color);top:100%;left:0px;padding: 10px;border:solid 1px #ccc;border-radius: 4px; box-shadow: 0px 0px 6px 1px rgba(0,0,0,0.2);margin-top: 5px;"
				id="jQDivSearchWindow">
				<form id="jQFromSearch">
					<table class="bom-table">
						<thead>
							<tr class="special">
								<td colspan="2">Search</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($database['fields'] as $fieldk => $fieldv) {
								if ($fieldv[4] == 'text' || $fieldv[4] == 'primary') {
									echo "<tr>
									<th style=\"min-width:130px\">{$fieldv[1]}</th>
									<td style=\"min-width:300px\">
										<div class=\"btn-set small\"><input type=\"text\" class=\"flex\" style=\"max-width:300px;\" name=\"search_{$database['hash']['r'][$fieldk]}\" class=\"text\" /></div>
									</td>
									</tr>";
								} elseif ($fieldv[4] == 'slo') {
									echo "<tr>
									<th style=\"min-width:130px\">{$fieldv[1]}</th>
									<td>
										<div class=\"btn-set small\"><input type=\"text\" data-slo=\"{$fieldv[7]}\" class=\"flex\" style=\"max-width:300px;\" name=\"search_{$database['hash']['r'][$fieldk]}\" /></div>
									</td>
									</tr>";
								} elseif ($fieldv[4] == 'bool') {
									echo "<tr>
									<th style=\"min-width:130px\">{$fieldv[1]}</th>
									<td><label class=\"ios-io\"><input type=\"checkbox\" name=\"search_{$database['hash']['r'][$fieldk]}\" class=\"change-we-status\" /><span>&nbsp;</span><div></div></lable></td>
									</tr>";
								} elseif ($fieldv[4] == 'textarea') {
									echo "<tr>
									<th style=\"min-width:130px\">{$fieldv[1]}</th>
									<td style=\"min-width:300px\">
										<div class=\"btn-set \"><textarea class=\"flex\" style=\"max-width:300px;min-width:300px;height:100px\" name=\"search_{$database['hash']['r'][$fieldk]}\" class=\"text\"></textarea></div>
									</td>
									</tr>";
								}
							}
							?>
							<tr>
								<td></td>
								<td>
									<div class="btn-set">
										<button type="submit" class="flex" id="jQButtonSearchDo" style="width:200px;">Submit</button>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
		</span>

		<div class="btn-set">
			<button id="jQButtonSearch" type="button">Search</button>
			<input id="jQButtonSearchClear" type="button" value="Clear" />
			<span id="jQInputSearchOccurrence" style="max-width: 150px;width:150px;">Search criterias: 0</span>

		</div>
	</div>
</div>

<table class="bom-table hover" id="jQmtable" style="margin-top:1px;">
	<thead>
		<tr data-id="0">
			<?php
			$colspan = 0
				+ ($fs()->permission->delete && !(isset($database['disable-delete']) && $database['disable-delete']) ? 1 : 0)
				+ ($fs()->permission->edit ? 1 : 0);

			if (!isset($database['readonly']) || (isset($database['readonly']) && !$database['readonly'])) {
				echo "<td colspan=\"$colspan\"></td>";
			}
			foreach ($database['fields'] as $fieldv) {
				if ($fieldv[2] == true) {
					echo "<td " . ($fieldv[3] != null ? " style=\"width:{$fieldv[3]};\" " : "") . ">{$fieldv[1]}</td>";
				}
			}
			?>
		</tr>
	</thead>
	<tbody id="jQPopulatePool"></tbody>
</table>



<script type="text/javascript">
	$(document).ready(function (e) {

		var slo = {};
		let nav = {
			'current': 1,
			'search': ''
		};
		let searchWindowState = false;
		popup.onboundaryclick(function () { });

		<?php
		if (isset($_GET['nav']) && (int) $_GET['nav'] > 0) {
			echo "nav.current=" . (int) $_GET['nav'] . ";";
		}
		if (isset($_GET['search']) && trim($_GET['search']) != "") {
			echo "nav.search=\"" . base64_decode($_GET['search']) . "\";";
		}
		?>

		$("#jQDivSearchWindow").find("[data-slo]").each(function () {
			$(this).slo({
				onselect: function (e) {
					return false;
				},
				limit: 7
			});
		});


		$("#jQbtnRefresh").on("click", function (e) {
			Populate();
		});

		$("#jQFromSearch").on("submit", function (e) {
			e.preventDefault();
			nav.search = $("#jQFromSearch").serialize().replace(/[^&]+=\.?(?:&|$)/g, '');
			nav.current = 1;
			history.pushState({
				'nav': nav.current,
				'search': btoa(nav.search)
			}, "<?php echo $fs()->title; ?>", "<?php echo $fs()->dir; ?>/?nav=" + nav.current + "&search=" + btoa(nav.search));

			searchWindowState = false;
			$("#jQDivSearchWindow").css("display", (searchWindowState ? "block" : "none"));
			Populate();
			return false;
		});
		$("#jQButtonSearchClear").on("click", function () {
			nav.search = "";
			nav.current = 1;
			history.pushState({
				'nav': nav.current,
				'search': ''
			}, "<?php echo $fs()->title; ?>", "<?php echo $fs()->dir; ?>/?nav=" + nav.current + "");
			searchWindowState = false;
			$("#jQDivSearchWindow").css("display", (searchWindowState ? "block" : "none"));
			Populate();
		});

		$("#jQButtonSearch").on("click", function () {
			searchWindowState = !searchWindowState;
			$("#jQDivSearchWindow").css("display", (searchWindowState ? "block" : "none"));
		});

		let Populate = function () {
			overlay.show();
			var $ajax = $.ajax({
				type: 'POST',
				url: '<?php echo $fs()->dir; ?>',
				data: {
					'method': 'populate',
					'page': nav.current,
					'search': nav.search
				}
			}).done(function (data) {
				$("#jQPopulatePool").html(data);
				let legend = $("#jQPopulatePool").find("td#LegendParams");

				$("#jQnavTotal").val(~~legend.attr("data-total"));
				$("#jQInputSearchOccurrence").html("Search criterias: " + legend.attr("data-searchoccurrence"));

				if (~~legend.attr("data-pagecount") > 1) {
					$("#jQnavPrev").prop("disabled", !(~~legend.attr("data-pagecurrent") > 1));
					$("#jQnavFirst").prop("disabled", (~~legend.attr("data-pagecurrent") == 1));
					$("#jQnavNext").prop("disabled", !(~~legend.attr("data-pagecurrent") < ~~legend.attr("data-pagecount")));
					$("#jQnavTitle").val(legend.attr("data-pagecurrent") + "/" + legend.attr("data-pagecount"));
				} else {
					$("#jQnavTitle").val("1/1");
					$("#jQnavFirst").prop("disabled", true);
					$("#jQnavNext").prop("disabled", true);
					$("#jQnavPrev").prop("disabled", true);
				}
				nav.current = ~~legend.attr("data-pagecurrent");

			}).fail(function (a, b, c) {
				messagesys.failure("Unable to execute your request, " + b);
			}).always(function () {
				overlay.hide();
			});
		}
		Populate();


		$("#jQnavNext").on("click", function () {
			nav.current += 1;
			history.pushState({
				'nav': nav.current,
				'search': btoa(nav.search)
			}, "<?php echo $fs()->title; ?>", "<?php echo $fs()->dir; ?>/?nav=" + nav.current + "&search=" + btoa(nav.search));
			Populate();
		});
		$("#jQnavFirst").on("click", function () {
			nav.current = 1;
			history.pushState({
				'nav': nav.current,
				'search': btoa(nav.search)
			}, "<?php echo $fs()->title; ?>", "<?php echo $fs()->dir; ?>/?nav=" + nav.current + "&search=" + btoa(nav.search));
			Populate();
		});
		$("#jQnavPrev").on("click", function () {
			nav.current -= 1;
			history.pushState({
				'nav': nav.current,
				'search': btoa(nav.search)
			}, "<?php echo $fs()->title; ?>", "<?php echo $fs()->dir; ?>/?nav=" + nav.current + "&search=" + btoa(nav.search));
			Populate();
		});

		window.onpopstate = function (e) {
			if (e.state && e.state.nav)
				nav.current = e.state.nav;
			else
				nav.current = 1;
			if (e.state && e.state.search)
				nav.search = atob(e.state.search);
			else
				nav.search = "";

			Populate();
		};

		<?php if ($fs()->permission->edit || $fs()->permission->add) { ?>
			var FormDisplay = function ($evoker, _id) {
				overlay.show();
				var $ajax = $.ajax({
					type: 'POST',
					url: '<?php echo $fs()->dir; ?>',
					data: {
						'id': _id,
						'ea_prepare': ''
					},
					async: true,
				}).done(function (data) {
					overlay.hide();
					popup.show(data);
					popup.self().find("[data-slo]").each(function () {
						var $pop = $(this);
						slo[$pop.attr('id')] = $("#" + $pop.attr('id')).slo({
							limit: 7
						});
					});

					<?php
					foreach ($database['fields'] as $fieldk => $fieldv) {
						if ($fieldv[4] == 'file') {
							echo '
						$.Upload({
							objectHandler:$("#up_list' . $fieldk . '"),
							domselector:$("#up_btn' . $fieldk . '"),
							dombutton:$("#up_trigger' . $fieldk . '"),
							list_button:$("#up_count' . $fieldk . '"),
							emptymessage:"[No files uploaded]",
							delete_method:"permanent",
							upload_url:"' . $fs(186)->dir . '",
							relatedpagefile:' . (int) $fieldv[5] . ',
							multiple:true,
							inputname:"attachments[' . $fieldk . ']",
							domhandler:$("#up_handler' . $fieldk . '"),
							}
						).update();';
						}
					}
					?>




					popup.self().find("#jQcancel").on('click', function () {
						popup.hide();
					});
					popup.self().find("#jQcancel").focus();
					popup.self().find("#jQform").on('submit', function (e) {
						overlay.show();
						e.preventDefault();
						$form = $(this);
						var _ser = $(this).serialize();
						$form.find("input,button,textarea").prop("disabled", true);
						var $ajax = $.ajax({
							type: 'POST',
							url: '<?php echo $fs()->dir; ?>',
							data: _ser,
						}).done(function (data) {
										<?php if ($debug) { ?>popup.show(data);
								return;
							<?php } ?>
							$form.find("input,button,textarea").prop("disabled", false);
							$form.find("#jQsubmit").focus();

							let json = false;
							try {
								json = JSON.parse(data);
							} catch (e) {
								messagesys.failure("Parsing output failed");
								return false;
							}

							if (json.result == true) {
								if (json.method == 0) {
									messagesys.success("Record added successfully");
									Populate();
								} else {
									messagesys.success("Record modified successfully");
									Populate();
								}
							} else {
								messagesys.failure(json.string);
							}

						}).fail(function (a, b, c) {
							messagesys.failure("Executing request failed, " + b);
						}).always(function () {
							overlay.hide();
						});

						return false;
					});
				}).fail(function (a, b, c) {
					overlay.hide();
					messagesys.failure("Unable to execute your request, " + b);
				});
			}
			$("#jQmtable").on('click', ".op-edit", function () {
				var $this = $(this);
				var _id = $this.closest("tr").attr("data-id");
				FormDisplay($this, _id);
			});
			$("#jQbtnAdd").on('click', function () {
				var $this = $(this);
				var _id = 0;
				FormDisplay($this, _id);
			});
		<?php } ?>

		<?php if ($fs()->permission->delete) { ?>
			$("#jQmtable").on('click', ".op-remove", function () {
				if (window.confirm("Are you sure you want to delete this record?") != true) {
					return false;
				}
				var $this = $(this);
				var _id = $this.closest("tr").attr("data-id");

				var $ajax = $.ajax({
					type: 'POST',
					url: '<?php echo $fs()->dir; ?>',
					data: {
						'id': _id,
						'method': 'delete'
					}
				}).done(function (data) {
					if (data == "1") {
						$this.closest("tr").remove();
						messagesys.success("Record delete successfully");
					} else {
						messagesys.failure("Unable to delete requested record");
					}
				}).fail(function (a, b, c) {
					messagesys.failure("Unable to execute your request, " + b);
				});
			});
		<?php } ?>
	});
</script>