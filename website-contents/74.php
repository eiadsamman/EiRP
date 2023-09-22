<?php

$company_hr = array();

$r = $app->db->query(
	"SELECT COUNT(1) AS company_count,lbr_company
	FROM labour_track  JOIN labour ON lbr_id = ltr_usr_id
	WHERE ltr_otime IS NULL
	GROUP BY lbr_company
	"
);

if ($r && $r->num_rows > 0) {
	while ($row = $r->fetch_assoc()) {
		if (!isset($company_hr[$row['lbr_company']])) {
			$company_hr[$row['lbr_company']] = array();
		}
		$company_hr[$row['lbr_company']][0] = $row['company_count'];
	}
}


$r = $app->db->query(
	"SELECT COUNT(1) AS company_count,comp_id, comp_name
	FROM 
		labour 
			JOIN companies ON comp_id = lbr_company
	WHERE lbr_resigndate IS NULL
	GROUP BY comp_id
	"
);

if ($r && $r->num_rows > 0) {
	while ($row = $r->fetch_assoc()) {
		if (!isset($company_hr[$row['comp_id']])) {
			$company_hr[$row['comp_id']] = array();
		}
		$company_hr[$row['comp_id']][1] = $row['company_count'];
		$company_hr[$row['comp_id']][2] = $row['comp_name'];
	}
}



if (sizeof($company_hr) > 0) {
	$cnt = 0;
	echo "<div class=\"widgetWQU\"><div>";
	echo "<h1>Companies Manpower:</h1>";
	echo "<div>";
	foreach ($company_hr as $comp => $data) {
		$account_title = "Comapny";

		echo "<div><div class=\"btn-set\" style=\"flex-wrap: nowrap; \">";
		echo "<span class=\"nofetch flex\">{$data[2]}</span>";
		echo "<input type=\"text\" style=\"width:70px;text-align:right;\" readonly=\"readonly\" tabindex=\"-1\" value=\"{$data[0]}\" />";
		echo "<input type=\"text\" style=\"width:70px;text-align:right;\" readonly=\"readonly\" tabindex=\"-1\" value=\"{$data[1]}\" />";
		echo "</div></div>";
	}
	echo "</div></div></div>";
}