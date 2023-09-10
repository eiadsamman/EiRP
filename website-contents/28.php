<?php
if (!isset($_POST['employees']) || !is_array($_POST['employees'])) {
	if (!isset($_GET['id'])) {
		exit;
	}
}

$printverified = isset($_POST['verified']) && $_POST['verified'] == '1' ? true : false;
?>
<style>
	div.printerform {
		margin: 1px;
	}

	div.printerform>div {
		display: inline-block;
		max-width: 54mm;
		min-width: 54mm;
		height: 85.6mm;
		max-height: 85.6mm;
		position: relative;
		overflow: hidden;


		--b: 0.3px;
		--c: #ccc;
		--w: 5px;
		border: var(--b) solid #0000;
		--_g: #0000 90deg, var(--c) 0;
		--_p: var(--w) var(--w) border-box no-repeat;
		background:
			url("<?= $app->http_root; ?>/idcard-bg.jpg") 0 100% / auto 100% no-repeat,
			conic-gradient(from 90deg at top var(--b) left var(--b), var(--_g)) 0 0 / var(--_p),
			conic-gradient(from 180deg at top var(--b) right var(--b), var(--_g)) 100% 0 / var(--_p),
			conic-gradient(from 0deg at bottom var(--b) left var(--b), var(--_g)) 0 100% / var(--_p),
			conic-gradient(from -90deg at bottom var(--b) right var(--b), var(--_g)) 100% 100% / var(--_p);

	}

	div.printerform>div>div.logo {
		display: block;
		position: absolute;
		top: 10px;
		left: 0px;
		width: 100%;
		height: 35px;
		background-image: url("<?= $app->http_root; ?>download/?id=<?= $USER->company->logo; ?>&pr=t");
		background-position: 10% 50%;
		background-size: auto 100%;
		background-repeat: no-repeat;
		z-index: 1;
	}

	div.printerform>div>div.photo {
		position: absolute;
		top: 165px;
		left: -25px;
		width: 40mm;
		height: 40mm;
		background-position: 50% 50%;
		background-size: 100% auto;
		background-repeat: no-repeat;
		border-radius: 50%;
		z-index: 3;
		mix-blend-mode: multiply;
	}

	div.printerform>div>h1 {
		position: absolute;
		top: 85px;
		right: 15px;
		left: 15px;
		font-size: 1.4em;
		height: 160px;
		margin: 0;
		padding: 0;
		text-align: right;
		line-height: 1.2em;
		overflow: hidden;
	}

	div.job {
		position: absolute;
		font-size: 13px;
		text-align: right;
		right: 0;
		color: #666;
	}

	div.printerform>div>div.serial {
		position: absolute;
		top: 60px;
		left: 0px;
		right: 0px;
		text-align: center;

	}

	div.printerform>div>div.idtag {
		position: absolute;
		right: 0px;
		left: 0px;
		font-size: 1.5em;
		bottom: 40px;
		text-align: right;
		padding-right: 13px;
		font-family: arial;
		z-index: 2;
		background-color: rgba(255, 255, 255, 0.6);
	}
</style>
<div class="printerform">
	<?php

	$idlist = array();
	if (is_array($_POST['employees'])) {
		foreach ($_POST['employees'] as $k => $v) {
			$idlist[] = (int)$v;
		}
	}
	if (isset($_GET['id'])) {
		$idlist[] = (int)$_GET['id'];
	}


	if ($r = $app->db->query("
	SELECT 
		usr_id,
		usr_id,usr_firstname,usr_lastname,
		_labour_type.lty_id,_labour_type.lty_name,_labour_type.lsc_name,_labour_type.lsc_color,
		lsf_id,lsf_name,usr_attrib_i3,usr_images_list, up_id
	FROM
		labour 
			JOIN users ON usr_id=lbr_id
			LEFT JOIN uploads ON (up_pagefile=" . $app::FILE['Person']['Photo'] . " ) AND up_rel=lbr_id AND up_deleted=0
			LEFT JOIN 
				(SELECT lty_id,lty_name,lsc_name,lsc_color FROM labour_type JOIN labour_section ON lty_section=lsc_id) AS _labour_type ON _labour_type.lty_id=lbr_type
			LEFT JOIN labour_shifts ON lsf_id=lbr_shift
	WHERE
		usr_id IN (" . implode(",", $idlist) . ")
	")) {
		while ($row = $r->fetch_assoc()) {
			$personalPhoto = "";
			if (!is_null($row['up_id']) && (int)$row['up_id'] != 0) {
				$personalPhoto = " style=\"background-image:url('{$app->http_root}" . $tables->pagefile_info(187, null, "directory") . "?id={$row['up_id']}&pr=v')\"";
			} else {
				$personalPhoto = " style=\"background-image:url('{$app->http_root}/user.jpg')\"";
			}

			$fsize = (mb_strlen($row['usr_firstname'] . " " . $row['usr_lastname'], "UTF8") > 20 ? "style=\"font-size:1.2em\"" : "");
			echo "<div>";
			echo "<div class=\"logo\"></div>";
			echo "<div class=\"photo\" {$personalPhoto}></div>";
			echo "<div class=\"section\" style=\"background-color:#{$row['lsc_color']}\"></div>";
			echo "<h1 $fsize>{$row['usr_firstname']} {$row['usr_lastname']}" . "<div class=\"job\">{$row['lsc_name']}, {$row['lty_name']}</div></h1>";

			echo "";
			echo "<div class=\"serial\"><img src=\"{$app->http_root}" . $fs(15)->dir . "/?c={$row['usr_id']}&f=1&t=13\" /></div>";
			echo "<div class=\"idtag\">" . (string)$row['usr_id'] . "</div>";
			echo "</div>";
		}
	}

	echo "<br />";
	?>
</div>