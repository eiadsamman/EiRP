<?php

use System\IO\AttachLib;
use System\Template\Gremium\Gremium;


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
		<td class=\"content\"><a class=\"js_upload_view\" target=\"_blank\" data-mime=\"$fileMime\" href=\"download/?id=$fileID&pr=v\" 
		>$fileTitle</a></td>
	</tr>";
}

$debug               = false;
$database['primary'] = null;
$pageper             = 20;
$accepted_mimes      = array("image/jpeg", "image/gif", "image/bmp", "image/png");

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
	$database['hash']['r'][$key]                  = md5("MEdH265" . $key);
}


function single_call(&$app, $database, $id)
{
	$tempquery = "SELECT ";
	$smart     = "";
	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] != "file") {
			$tempquery .= $smart . (isset($fieldv[0]) && $fieldv[0] != null ? "{$fieldv[0]} AS " : "") . "$fieldk";
			$smart     = ",";
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


	$q     = "INSERT INTO {$database['table']} (\n\t\t";
	$smart = "";
	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == "file") {
		} elseif ($fieldv[6] == true) { /*Allow field value updating*/
			$q .= $smart . "$fieldk";
			$smart = ",\n\t\t";
		}
	}

	$q .= "\n) VALUES (\n\t\t";
	$smart   = "";
	$idvalue = 0;

	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] != "file") {
			if ($fieldv[6] == true) { /*Allow field value updating*/
				if (($fieldv[4] == 'hidden' || $fieldv[4] == 'text' || $fieldv[4] == 'textarea')) {
					$q .= $smart . sqlvalue($fieldv[5], $_POST[$database['hash']['r'][$fieldk]], isset($_POST[$database['hash']['r'][$fieldk]]));

				} elseif (($fieldv[4] == 'slo')) {

					if( $fieldv[7] == ":LIST"){
						$q .= $smart . sqlvalue(
							$fieldv[5], 
							$_POST[$database['hash']['r'][$fieldk]][1], 
							isset($_POST[$database['hash']['r'][$fieldk]][1])
						);
					}else{
						$q .= $smart . sqlvalue($database['fields'][$fieldv[8]][5], $_POST[$database['hash']['r'][$fieldk]][1], isset($_POST[$database['hash']['r'][$fieldk]][1]));
					}

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

	$json_output = array("result" => null, "method" => null, "string" => null);


	try {
		$r = $app->db->query($q);
	} catch (mysqli_sql_exception $e) {
		$json_output['result'] = false;
		$json_output['string'] = $e->getMessage();
		echo json_encode($json_output);

		exit;
	}
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
		include ($app->root . 'admin/class/attachlib.php');
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
	$cleaned     = $database['fields'];
	foreach ($cleaned as $k => $v) {
		$cleaned[$k] = "";
	}
	$op_type = null;
	if ((int) $_POST['id'] == 0) {
		$op_type                       = "add";
		$cleaned[$database['primary']] = (int) $_POST['id'];
	} else {
		$op_type = "edit";
		if ($cleaned = single_call($app, $database, (int) $_POST['id'])) {
		} else {
			echo "Fetching record information failed";
			exit;
		}
	}

	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == 'primary') {
			echo "<input type=\"hidden\" name=\"{$database['hash']['r'][$fieldk]}\" id=\"{$database['hash']['r'][$fieldk]}\" value=\"" . (isset($cleaned[$fieldk]) ? $cleaned[$fieldk] : "") . "\" />";
		} elseif ($fieldv[4] == 'hidden') {
			echo "<input type=\"hidden\" name=\"{$database['hash']['r'][$fieldk]}\" id=\"{$database['hash']['r'][$fieldk]}\" value=\"" . (isset($fieldv[10]) && $fieldv[10] != null ? $fieldv[10] : "") . "\" />";
		}
	}
	// . "</span>`{$database['tablename']}`

	$grem       = new Gremium(false);
	$grem->base = "0px";
	$grem->header()->prev("href=\"{$fs()->dir}\"")->serve(
		"<h1>{$fs()->title}</h1>" .
		((int) $_POST['id'] != 0 ? "<ul class=\"small-media-hide\"><li>{$_POST['id']}</li></ul>" : "") .
		"<cite><button type=\"submit\" data-role=\"submit\" id=\"jQsubmit\"></button></cite>"
	);
	$grem->article()->width("auto")->open();
	$autofocus = "autofocus";
	foreach ($database['fields'] as $fieldk => $fieldv) {
		if ($fieldv[4] == 'text') {
			echo "<div class=\"form\"><label>
					<h1>{$fieldv[1]}</h1>
					<div class=\"btn-set\">
						<input type=\"text\" $autofocus id=\"{$database['hash']['r'][$fieldk]}\" class=\"flex\" name=\"{$database['hash']['r'][$fieldk]}\" class=\"text\" 
							style=\"min-width:250px;\"
							" . (isset($fieldv[6]) && $fieldv[6] == false ? " readonly=\"readonly\" " : "") . "
							value=\"" . (isset($cleaned[$fieldk]) ? htmlspecialchars($cleaned[$fieldk], ENT_QUOTES) : "") . "\" 
							" . (isset($fieldv[11]) && $fieldv[11] && $op_type == "edit" ? " readonly=\"readonly\" " : "") . " />
					</div>
				</label>
				
				</div>";
		} elseif ($fieldv[4] == 'slo') {
			if($fieldv[7]==":LIST"){
				echo "<div class=\"form\"><label>
					<h1>{$fieldv[1]}</h1>
					<div class=\"btn-set\">
						<input type=\"text\" $autofocus id=\"{$database['hash']['r'][$fieldk]}\" data-list=\"{$fieldv[8]}\"
							data-slo=\"{$fieldv[7]}\" class=\"flex\" style=\"min-width:250px;\"
							" . (isset($cleaned[$fieldk]) ? " value=\"" . $cleaned[$fieldk] . "\" " : "") . " 
							" . (isset($cleaned[$fieldv[8]]) ? " data-slodefaultid=\"" . $cleaned[$fieldv[8]] . "\" " : "") . " name=\"{$database['hash']['r'][$fieldk]}\" />
					</div>
				</label>
				</div>";
			}else{
				echo "<div class=\"form\"><label>
						<h1>{$fieldv[1]}</h1>
						<div class=\"btn-set\">
							<input type=\"text\" $autofocus id=\"{$database['hash']['r'][$fieldk]}\" data-slo=\"{$fieldv[7]}\" class=\"flex\" style=\"min-width:250px;\"
								" . (isset($cleaned[$fieldk]) ? " value=\"" . $cleaned[$fieldk] . "\" " : "") . " " . (isset($cleaned[$fieldv[8]]) ? " data-slodefaultid=\"" . $cleaned[$fieldv[8]] . "\" " : "") . " name=\"{$database['hash']['r'][$fieldk]}\" />
						</div>
					</label>
					</div>";
			}
		} elseif ($fieldv[4] == 'bool') {
			echo "<div class=\"form\"><label>
					<h1>{$fieldv[1]}</h1>
					<div class=\"btn-set\">
						<label>
							<input type=\"checkbox\" $autofocus id=\"{$database['hash']['r'][$fieldk]}\" name=\"{$database['hash']['r'][$fieldk]}\" " . (isset($cleaned[$fieldk]) && (int) $cleaned[$fieldk] == 1 ? " checked=\"checked\" " : "") . " />
						</label>
					</div>
				</label>
				
				</div>";
		} elseif ($fieldv[4] == 'textarea') {
			echo "<div class=\"form\"><label>
					<h1>{$fieldv[1]}</h1>
					<div class=\"btn-set\">
						<textarea id=\"{$database['hash']['r'][$fieldk]}\" $autofocus class=\"flex\" style=\"min-width:250px;height:100px\" name=\"{$database['hash']['r'][$fieldk]}\" class=\"text\" 
							 " . (isset($fieldv[11]) && $fieldv[11] && $op_type == "edit" ? " readonly=\"readonly\" " : "") . ">" . (isset($cleaned[$fieldk]) ? $cleaned[$fieldk] : "") . "</textarea>
					</div>
				</label>
				
				</div>";
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
					up_rel DESC, up_date DESC;"
			);

			echo <<<HTML
				<div class="form"><label for="">
					<h1>{$fieldv[1]}</h1>
					<div  style="min-width:250px;">
						<div class="btn-set" style="justify-content:left">
							<span id="up_count{$fieldk}" class="js_upload_count"><span>0 / 0</span></span>

							<input id="up_trigger{$fieldk}" class="js_upload_trigger" type="button" value="Upload" />
							<input type="file" id="up_btn{$fieldk}" class="js_uploader_btn" multiple="multiple" />
							<span id="up_list{$fieldk}" class="js_upload_list">
								<div id="up_handler{$fieldk}">
									<table class="hover">
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
							
						</div>
					</div>
					
			</label>
			
			</div>
			HTML;
		}
	}
	$grem->getLast()->close();
	$grem->terminate();
	unset($grem);
	exit;
}


if (isset($_POST['method'], $_POST['page']) && $_POST['method'] == "populate") {
	$_POST['page'] = (int) $_POST['page'];
	$totalRecords  = 0;
	$pagecurrent   = 1;
	$pagecount     = 1;

	$searchCriteria    = "";
	$searchQuery       = "";
	$searchOccurrenece = 0;

	/*Search function*/
	if (isset($_POST['search'])) {
		parse_str($_POST['search'], $searchCriteria);
		foreach ($database['fields'] as $fieldk => $fieldv) {
			if (isset($searchCriteria['search_' . $database['hash']['r'][$fieldk]])) {
				if ($fieldv[4] == 'text' || $fieldv[4] == 'textarea') {
					if ($fieldv[5] == "string" && trim($searchCriteria['search_' . $database['hash']['r'][$fieldk]]) != "") {
						$sq = " AND (1 ";
						$q  = explode(" ", ($searchCriteria['search_' . $database['hash']['r'][$fieldk]]));
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
			$pagecount    = ceil($totalRecords / $pageper);
		}
	}

	$pagecurrent = $_POST['page'];
	$pagecurrent = $pagecurrent > $pagecount ? $pagecount : $pagecurrent;
	$pagecurrent = $pagecurrent < 1 ? 1 : $pagecurrent;
	$pagenav     = ((int) $pagecurrent - 1) * $pageper;


	$filehandler = "";

	$database['query'] = "SELECT \n\t";
	$smart             = "";
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
		) AS {$fieldk} ON {$fieldk}.up_rel = {$database['primary']} ";
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
</style>
<?php
$addButton = (!isset($database['readonly']) || (isset($database['readonly']) && !$database['readonly']) ?
	'<cite style="font-size:1em;justify-content:flex-end;margin-top:5px" class="btn-set"><button type="button" class="plus" id="jQbtnAdd" title="Add new record"> Add</button></cite>' : "");


$grem_main = new Gremium(false, true);
$grem_main->header()->serve(
	"<h1>{$fs()->title}</h1>" .
	"<ul><li style=\"font-size:0.8em;margin-top:3px;\"><a href=\"{$fs($fs()->parent)->dir}\">" . ($fs($fs()->parent)->title) . "</a></li></ul>" .
	$addButton
);
$grem_main->menu()->open();
?>
<!-- <input disabled="disabled" id="jQnavFirst" type="button" value="First" class="edge-left" /> -->
<input disabled="disabled" class="pagination prev edge-left" value="&#xE618;" id="jQnavPrev" type="button" />
<input type="text" id="js-input_page-current" placeholder="#" data-slo=":NUMBER" style="width:80px;text-align:center" data-rangestart="1" value="0" data-rangeend="1" />
<input disabled="disabled" class="pagination next edge-right" value="&#xE61B;" id="jQnavNext" type="button" value="Next" />
<input type="button" style="text-align:center;display:none" id="jQnavTitle" value="0 pages" />
<span type="text" style="text-align:center;min-width:80px;" readonly id="jQnavTotal">0 records</span>

<span class="gap"></span>
<span id="jQInputSearchOccurrence">Filters: 0</span>
<input id="jQButtonSearchClear" class="edge-left" type="button" value="Clear" />
<button id="jQButtonSearch" class="edge-right" type="button">Filter</button>

<?php
$grem_main->getLast()->close();
$grem_main->article()->open();
?>

<table class="hover" id="jQmtable" style="margin-top:1px;">
	<thead>
		<tr data-id="0" style="position: sticky; top: calc(var(--root--menubar-height) + 115px - var(--gremium-header-toggle));">
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


<?php
$grem_main->getLast()->close();
$grem_main->terminate();
unset($grem);
?>

<div style="display:none">
	<form id="appPopupDelConfirm">
		<?php
		$grem       = new Gremium(false);
		$grem->base = "0px";
		$grem->header()->serve("<h1 style=\"padding-left:20px;\">Delete confirmation!</h1>");

		$grem->article()->width("auto")->open();
		echo "Are you sure you want to delete this record?";
		echo "<div style=\"margin-top:20px\" class=\"btn-set right\"><button type=\"submit\">Delete</button><input type=\"button\" autofocus data-role=\"previous\" value=\"Cancel\" class=\"edge-right\" /></div>";
		$grem->getLast()->close();
		$grem->terminate();
		unset($grem);
		?>
	</form>

	<form id="appPopupSearch">
		<?php
		$grem       = new Gremium(false);
		$grem->base = "0px";
		$grem->header()->prev("href=\"{$fs()->dir}\"")->serve(
			"<h1>Filter</h1>" .
			"<cite><button data-role=\"submit\" type=\"submit\" id=\"jQsubmit\"></button></cite>"
		);
		$grem->article()->width("auto")->open();
		$autofocus = "autofocus";
		foreach ($database['fields'] as $fieldk => $fieldv) {
			if ($fieldv[4] == 'text' || $fieldv[4] == 'primary') {
				echo "<div class=\"form\"><label><h1>{$fieldv[1]}</h1><div class=\"btn-set\"><input $autofocus type=\"text\" class=\"flex\" name=\"search_{$database['hash']['r'][$fieldk]}\" class=\"text\" /></div></label></div>";
			} elseif ($fieldv[4] == 'slo') {
				echo "<div class=\"form\"><label><h1>{$fieldv[1]}</h1><div class=\"btn-set\"><input $autofocus type=\"text\" data-slo=\"{$fieldv[7]}\" class=\"flex\" name=\"search_{$database['hash']['r'][$fieldk]}\" /></div></label></div>";
			} elseif ($fieldv[4] == 'bool') {
				echo "<div class=\"form\"><label><h1>{$fieldv[1]}</h1><div class=\"btn-set\"><label><input $autofocus type=\"checkbox\" name=\"search_{$database['hash']['r'][$fieldk]}\" />{$fieldv[1]}</label></div></label></div>";
			} elseif ($fieldv[4] == 'textarea') {
				echo "<div class=\"form\"><label><h1>{$fieldv[1]}</h1><div class=\"btn-set\"><textarea $autofocus class=\"flex\" style=\"min-width:300px;height:100px\" name=\"search_{$database['hash']['r'][$fieldk]}\" class=\"text\"></textarea></div></label></div>";
			}
			$autofocus = "";
		}
		$grem->getLast()->close();
		$grem->terminate();
		unset($grem);
		?>
	</form>
</div>


<script type="module">
	import { Navigator } from './static/javascript/modules/app.js';
	import { Popup } from './static/javascript/modules/gui/popup.js';

	var nav = new Navigator({
		'page': 1,
		'search': ''
	}, '<?= $fs()->dir; ?>');

	const searchPopup = new Popup("appPopupSearch");
	const confirmPopup = new Popup("appPopupDelConfirm");
	searchPopup.height("400px");

	$(document).ready(function (e) {
		let searchWindowState = false;

		<?php
		if (isset($_GET['page']) && (int) $_GET['page'] > 0)
			echo "nav.setProperty(\"page\", " . (int) $_GET['page'] . ");";
		if (isset($_GET['search']) && trim($_GET['search']) != "")
			echo "nav.setProperty(\"search\", '" . base64_decode($_GET['search']) . "');";
		?>

		nav.replaceVariableState();
		let slo_page_current = $("#js-input_page-current").slo({
			onselect: function (e) {
				nav.setProperty("page", e.key);
				nav.pushState();
				Populate()
			}
		});


		searchPopup.addEventListener("submit", function (e) {
			slo_page_current.set(1, 1);
			nav.setProperty("page", 1);
			nav.setProperty("search", $(this.controlContent).serialize().replace(/[^&]+=\.?(?:&|$)/g, ''));
			nav.pushState();
			searchPopup.close();
			Populate();
			return false;
		});

		nav.onPopState((e) => {
			if (e.state && e.state.page) {
				nav.setProperty("page", e.state.page);
				slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
			}
			if (e.state && e.state.search) { nav.setProperty("search", e.state.search); }
			Populate();
		});
		slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));

		$(searchPopup.controller()).find("[data-slo]").each(function () { $(this).slo(); });


		$("#jQButtonSearchClear").on("click", function () {
			slo_page_current.set(1, 1);
			nav.setProperty("page", 1);
			nav.setProperty("search", "");
			nav.pushState();
			searchWindowState = false;
			Populate();
		});

		$("#jQButtonSearch").on("click", function () {
			searchWindowState = !searchWindowState;
			searchPopup.show();
		});

		let Populate = function () {
			overlay.show();
			var $ajax = $.ajax({
				type: 'POST',
				url: '<?php echo $fs()->dir; ?>',
				data: {
					'method': 'populate',
					'page': nav.history_vars.page,
					'search': nav.history_vars.search
				}
			}).done(function (data) {
				$("#jQPopulatePool").html(data);
				let legend = $("#jQPopulatePool").find("td#LegendParams");

				$("#jQnavTotal").html(parseInt(legend.attr("data-total")) + " records");
				$("#jQInputSearchOccurrence").html("Filters: " + legend.attr("data-searchoccurrence"));

				if (parseInt(legend.attr("data-pagecount")) > 1) {
					$("#jQnavPrev").prop("disabled", !(~~legend.attr("data-pagecurrent") > 1));
					$("#jQnavFirst").prop("disabled", (~~legend.attr("data-pagecurrent") == 1));
					$("#jQnavNext").prop("disabled", !(~~legend.attr("data-pagecurrent") < ~~legend.attr("data-pagecount")));
					$("#jQnavTitle").val(legend.attr("data-pagecount"));
					try {
						if (slo_page_current[0].slo.handler instanceof NumberHandler) {
							slo_page_current[0].slo.handler.rangeEnd(parseInt(legend.attr("data-pagecount")));
						}
					} catch (e) {
						slo_page_current.clear();
					}
				} else {
					$("#jQnavTitle").val("1");
					$("#jQnavFirst").prop("disabled", true);
					$("#jQnavNext").prop("disabled", true);
					$("#jQnavPrev").prop("disabled", true);
					try {
						if (slo_page_current[0].slo.handler instanceof NumberHandler) {
							slo_page_current[0].slo.handler.rangeEnd(1);
						}
					} catch (e) {
						slo_page_current.clear();
					}
				}
				nav.setProperty("page", parseInt(legend.attr("data-pagecurrent")));

			}).fail(function (a, b, c) {
				messagesys.failure("Unable to execute your request, " + b);
				try {
					if (slo_page_current[0].slo.handler instanceof NumberHandler) {
						slo_page_current[0].slo.handler.rangeEnd(0);
					}
				} catch (e) {
					slo_page_current.clear();
				}
			}).always(function () {
				overlay.hide();
			});
		}
		Populate();


		$("#jQnavNext").on("click", function () {
			nav.setProperty("page", nav.getVariable("page") + 1);
			slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
			nav.pushState();
			Populate();
		});
		$("#jQnavFirst").on("click", function () {
			nav.setProperty("page", 1);
			slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
			nav.pushState();
			Populate();
		});
		$("#jQnavPrev").on("click", function () {
			if (nav.getVariable("page") > 1) {
				nav.setProperty("page", nav.getVariable("page") - 1);
				slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
				nav.pushState();
				Populate();
			}
		});


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
					const modal = new Popup();

					modal.addEventListener("close", function () {
						this.destroy();
					});
					modal.addEventListener("show", () => {
						$(modal.controller()).find("[data-slo]").each(function () {
							var el = $(this);
							$("#" + el.attr('id')).slo();
						});
					});
					modal.content(data).show();
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

					modal.addEventListener("submit", function (e) {
						let $form = $(this.controlContent);
						var _ser = $form.serialize() + "&operator=";

						$form.find("input,button,textarea").prop("disabled", true);
						var $ajax = $.ajax({
							type: 'POST',
							url: '<?= $fs()->dir; ?>',
							data: _ser,
						}).done(function (data) {
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
								modal.destroy();
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

			confirmPopup.addEventListener("submit", (e) => {
				var $ajax = $.ajax({
					type: 'POST',
					url: '<?= $fs()->dir; ?>',
					data: {
						'id': e.detail.id,
						'method': 'delete'
					}
				}).done(function (data) {
					if (data == "1") {
						e.detail.caller.closest("tr").remove();
						messagesys.success("Record deleted successfully");
						confirmPopup.close();
					} else {
						messagesys.failure("Deleting requested record failed");
					}
				}).fail(function (a, b, c) {
					messagesys.failure("Request execution failed, " + b);
				});
			});

			$("#jQmtable").on('click', ".op-remove", function () {
				var $this = $(this);
				var _id = $this.closest("tr").attr("data-id");

				confirmPopup.show({
					id: parseInt(_id),
					caller: $this
				});
			});
		<?php } ?>
	});
</script>