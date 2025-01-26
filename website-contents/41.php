<?php

$__type = array(
	1 => \System\Controller\Personalization\Identifiers::SystemProductiontrackSection->value,
	2 => \System\Controller\Personalization\Identifiers::SystemProductiontrackMaterial->value
);

function PopulateConfigs(&$app)
{
	$arrOutput = array(
		"stations" => array(),
		"materials" => array(),
	);
	$r = $app->db->query("SELECT cob_id, cob_hash FROM cobject WHERE cob_checked=1;");
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$arrOutput['stations'][$row['cob_hash']] = $row['cob_id'];
		}
	}
	$r = $app->db->query("SELECT cot_id, cot_init FROM cobjecttype WHERE cot_mastercarton=1;");
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$arrOutput['materials'][$row['cot_init']] = $row['cot_id'];
		}
	}
	return $arrOutput;
}


function FetchCountDay(&$app, &$__type)
{
	$s__type1 = 0;
	$s__type2 = 0;
	$rr = $app->db->query("SELECT usrset_value FROM user_settings WHERE usrset_usr_id={$app->user->id} AND usrset_type=\"{$__type[1]}\" AND usrset_usr_defind_name=\"UNIQUE\";");
	if ($rr) {
		if ($row = $rr->fetch_assoc()) {
			$s__type1 = $row['usrset_value'];
		}
	}
	$rr = $app->db->query("SELECT usrset_value FROM user_settings WHERE usrset_usr_id={$app->user->id} AND usrset_type=\"{$__type[2]}\" AND usrset_usr_defind_name=\"UNIQUE\";");
	if ($rr) {
		if ($row = $rr->fetch_assoc()) {
			$s__type2 = $row['usrset_value'];
		}
	}
	$output = 0;
	$qfetchCount = "SELECT
			COUNT(ctr_userid) AS _count, cot_capacity
		FROM
			cobjecttrack 
				LEFT JOIN cobjecttype ON cot_id=$s__type2
		WHERE
			ctr_workorder=$s__type1 AND ctr_prt_id=$s__type2 AND 
			date(ctr_time) = '" . date("Y-m-d") . "'
		";

	$rfetchCount = $app->db->query($qfetchCount);
	if ($rfetchCount) {
		if ($rfetchCountRow = $rfetchCount->fetch_assoc()) {
			$output = (int) $rfetchCountRow['_count'] . " * " . (int) $rfetchCountRow['cot_capacity'] . " = " . ((int) $rfetchCountRow['_count'] * (int) $rfetchCountRow['cot_capacity']);
		}
	}
	return $output;
}

function ChangeUserTypes(&$app, &$__type, $type, $typeid)
{
	$output = false;
	$type = $type == 1 ? 1 : 2;
	$type = $__type[$type];
	$r = $app->db->query("
		INSERT INTO 
			user_settings 
			(usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) 
		VALUES 
			({$app->user->id},\"{$type}\",\"UNIQUE\",$typeid,NOW()) 
		ON DUPLICATE KEY UPDATE 
			usrset_value = $typeid, usrset_time = NOW()
			");
	if ($r) {
		$output = true;
	} else {
		$output = false;
	}
	return (bool) $output;
}

if (isset($_POST['method'], $_POST['type'], $_POST['id']) && $_POST['method'] == "change") {
	$chres = ChangeUserTypes($app, $__type, (int) $_POST['type'], (int) $_POST['id']);
	if ($chres == true) {
		echo FetchCountDay($app, $__type);
	} else {
		echo "false";
	}
	exit;
}

if (isset($_POST['method'], $_POST['serial']) && $_POST['method'] == "stream") {

	$s__type1 = 0;
	$s__type2 = 0;
	$rr = $app->db->query("SELECT usrset_value FROM user_settings WHERE usrset_usr_id={$app->user->info->id} AND usrset_type=\"{$__type[1]}\" AND usrset_usr_defind_name=\"UNIQUE\";");
	if ($rr) {
		if ($row = $rr->fetch_assoc()) {
			$s__type1 = $row['usrset_value'];
		}
	}
	$rr = $app->db->query("SELECT usrset_value FROM user_settings WHERE usrset_usr_id={$app->user->info->id} AND usrset_type=\"{$__type[2]}\" AND usrset_usr_defind_name=\"UNIQUE\";");
	if ($rr) {
		if ($row = $rr->fetch_assoc()) {
			$s__type2 = $row['usrset_value'];
		}
	}


	$output = array(
		"result" => 0,
		"instruct" => "0",
		"confid" => 0
	);
	$serial = trim($_POST['serial']);

	$popConfigs = PopulateConfigs($app);

	if (isset($popConfigs['stations'][$serial])) {
		if (ChangeUserTypes($app, $__type, 1, $popConfigs['stations'][$serial])) {
			$output['result'] = 2;
			$output['confid'] = $popConfigs['stations'][$serial];
			$output['instruct'] = FetchCountDay($app, $__type);
		}
	}
	if (isset($popConfigs['materials'][$serial])) {
		if (ChangeUserTypes($app, $__type, 2, $popConfigs['materials'][$serial])) {
			$output['result'] = 3;
			$output['confid'] = $popConfigs['materials'][$serial];
			$output['instruct'] = FetchCountDay($app, $__type);
		}
	}
	if ($output['result'] != 0) {
		echo json_encode($output);
		exit;
	}

	//Search for previous inputs within the latest hour
	$rprevious = $app->db->query("
		SELECT 
			count(ctr_id) AS cprevious
		FROM 
			 cobjecttrack 
		WHERE
			ctr_serial=\"$serial\" AND ctr_workorder=$s__type1 AND ctr_prt_id=$s__type2 AND ctr_time>FROM_UNIXTIME(" . mktime(date("H") - 1, date("i"), date("s"), date("m"), date("d"), date("Y")) . ")
		");
	if ($rprevious) {
		if ($rowprevious = $rprevious->fetch_assoc()) {
			if ((int) $rowprevious['cprevious'] > 0) {
				$output['result'] = 7;
				echo json_encode($output);
				exit;
			}
		}
	}

	$q = ("INSERT INTO cobjecttrack (ctr_userid, ctr_workorder, ctr_prt_id, ctr_qty, ctr_serial,ctr_type,ctr_time) VALUES ({$app->user->info->id},$s__type1,$s__type2,1,\"$serial\",0,FROM_UNIXTIME(" . time() . ") );");

	if ($app->db->query($q)) {
		$output['result'] = 1;
		$output['instruct'] = FetchCountDay($app, $__type);
	} else {
		$output['result'] = 9;
	}

	echo json_encode($output);
	exit;
}
?>
<style type="text/css">
	.horzScroll {
		display: -webkit-box;
		display: -moz-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		overflow-x: auto;
		padding: 0px 1px;
		position: relative;
		left: 0px;
		right: 0px;
		-webkit-overflow-scrolling: touch;
		align-items: stretch;

	}

	.horzScroll>div {
		flex: 1;
		display: inline-block;
		white-space: nowrap;
		text-align: center;
		height: 100px;

		margin-left: -1px;
		min-width: 150px;
		border: solid 1px #ccc;
		background-color: #fff;
		background-image: -moz-linear-gradient(top, #fff, #f7f7fa);
		background-image: -ms-linear-gradient(top, #fff, #f7f7fa);
		background-image: -o-linear-gradient(top, #fff, #f7f7fa);
		background-image: -webkit-linear-gradient(top, #fff, #f7f7fa);
		background-image: linear-gradient(top, #ffffff 0, #f7f7fa 100%);
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
		z-index: 0;
		position: relative;
	}

	.horzScroll>div>div {
		padding-top: 40px;
	}

	.horzScroll>div:hover,
	.horzScroll>div:focus {
		background-color: #eee;
		background-image: -moz-linear-gradient(top, #fafafc, #eee);
		background-image: -ms-linear-gradient(top, #fafafc, #eee);
		background-image: -o-linear-gradient(top, #fafafc, #eee);
		background-image: -webkit-linear-gradient(top, #fafafc, #eee);
		background-image: linear-gradient(top, #fafafc 0, #eee 100%);
		border-color: #999;
		z-index: 1;
	}

	.horzScroll>div.selected>div:before {
		font-family: icomoon2;
		display: block;
		content: "\f00c";
		position: absolute;
		width: 30px;
		height: 24px;
		top: 10px;
		left: 10px;
		border-radius: 20px;
		color: #fff;
		padding-top: 6px;
		background-color: #06c;

		z-index: 2;
	}

	.horzScroll>div:first-child {
		border-radius: 4px 0px 0px 4px;
	}

	.horzScroll>div:last-child {
		border-radius: 0px 4px 4px 0px;
	}

	.horzScroll>div:only-child {
		border-radius: 4px;
	}

	.listShots {
		border-radius: 5px;
		border: solid 1px #ccc;
		margin-top: 15px;
		padding: 0px 15px;
		max-height: 400px;
		overflow-y: auto;
	}

	.listShots>span {
		display: block;
		margin: 8px 0px;
	}

	#JQInputField {
		font-size: 20px;
	}

	#JQDIVOutput>span.p {
		color: #888;
	}

	#JQDIVOutput>span.s {
		color: #396;
	}

	#JQDIVOutput>span.f {
		color: #f03;
	}

	#JQDIVOutput>span.c {
		color: #06c;
	}
</style>
<div style="position: absolute;left:15px;right:15px;">
	<div class="horzScroll" id="JQSelectSection">
		<?php
		$resStations = $app->db->query("SELECT cob_id,cob_serial,cob_hash,usrset_value FROM cobject 
										LEFT JOIN user_settings ON cob_id = usrset_value AND usrset_usr_id={$app->user->info->id} AND usrset_type=\"{$__type[1]}\"
										WHERE cob_checked=1");
		if ($resStations) {
			while ($row = $resStations->fetch_assoc()) {
				$sel = $row['usrset_value'] == null ? "" : " class=\"selected\" ";
				echo "<div data-id=\"{$row['cob_id']}\" $sel><div>" . $row['cob_serial'] . "</div></div>";
			}
		}
		?>
	</div>

	<div class="horzScroll" id="JQSelectMat" style="margin-top: 20px;">
		<?php
		$r = $app->db->query("SELECT cot_id,cot_name,cot_init ,usrset_value FROM cobjecttype 
									LEFT JOIN user_settings ON cot_id = usrset_value AND usrset_usr_id={$app->user->info->id} AND usrset_type=\"{$__type[2]}\"
									WHERE cot_mastercarton=1");
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				$sel = $row['usrset_value'] == null ? "" : " class=\"selected\" ";
				echo "<div data-id=\"{$row['cot_id']}\" $sel><div>" . $row['cot_name'] . " </div></div>";
			}
		}
		?>
	</div>

	<div style="position: relative;left:0px;right: 0px;bottom: 0px;margin-top: 15px;">
		<div class="btn-set"><button id="JQInputButtonSubmit" type="button">Submit</button><span
				id="JQHTMLSpanCount">Count
				<?php echo FetchCountDay($app, $__type); ?>
			</span></div>
		<div class="btn-set" style="margin-top: 10px;"><textarea id="JQInputField" class="flex"
				style="width: 100%;height: 150px"></textarea></div>
		<div class="btn-set"></div>
		<div class="listShots" id="JQDIVOutput">
		</div>
	</div>
</div>

<script type="text/javascript">
	"use strict";
	$(document).ready(function (e) {
		var _htmlInputTextSerial = $("#JQInputField");
		var _intSectionID = 0;
		var _intMaterialID = 0;
		var _htmlDivOutput = $("#JQDIVOutput");
		var _htmlSpanCount = $("#JQHTMLSpanCount");

		$("#JQSelectSection > div").on("click", function () {
			_htmlInputTextSerial.focus().select();
			_intSectionID = ~~$(this).attr("data-id");
			funChangeSecMat(1, _intSectionID, $("#JQSelectSection > div"), $(this));
		});

		$("#JQSelectMat > div").on("click", function () {
			_htmlInputTextSerial.focus().select();
			_intMaterialID = ~~$(this).attr("data-id");
			funChangeSecMat(2, _intMaterialID, $("#JQSelectMat > div"), $(this));
		});

		var funChangeSecMat = function (type, id, objs, obj) {
			$.ajax({
				data: "method=change&type=" + type + "&id=" + id + "",
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				if (data != "false") {
					objs.removeClass("selected");
					obj.addClass("selected");
					_htmlSpanCount.html("Count " + data);
				}
			});
		};

		var funStreamInput = function (input, obj) {
			$.ajax({
				data: "method=stream&serial=" + input + "",
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				var json = null;
				try {
					json = JSON.parse(data);
				} catch (e) { }

				if (json.result == 1) {
					obj.removeClass("p").addClass("s");
					_htmlSpanCount.html("Count " + json.instruct);
				} else if (json.result == 2) {
					obj.removeClass("p").addClass("c");
					obj.html("Station Changed");
					$("#JQSelectSection > div").removeClass("selected");
					$("#JQSelectSection > div[data-id=" + json.confid + "]").addClass("selected");
					_htmlSpanCount.html("Count " + json.instruct);
				} else if (json.result == 3) {
					obj.removeClass("p").addClass("c");
					obj.html("Material Changed");
					$("#JQSelectMat > div").removeClass("selected");
					$("#JQSelectMat > div[data-id=" + json.confid + "]").addClass("selected");
					_htmlSpanCount.html("Count " + json.instruct);
				} else if (json.result == 7) {
					obj.removeClass("p").addClass("f");
				} else {
					obj.removeClass("p").addClass("f");
				}
			}).fail(function (a, b, c) {
				obj.removeClass("p").addClass("f");
			});
		}

		var funInputHandler = function (input) {
			var _valline = input.split("\n");
			var lineprep = null;
			for (var line in _valline) {
				lineprep = _valline[line].trim();
				if (lineprep != "") {
					var obj = $("<span />");
					obj.addClass("p");
					obj.html(lineprep);
					_htmlDivOutput.prepend(obj);
					funStreamInput(lineprep, obj);
				}
			}
		}
		$("#JQInputButtonSubmit").on("click", function () {
			funInputHandler(_htmlInputTextSerial.val());
			_htmlInputTextSerial.val("");
			_htmlInputTextSerial.focus();
		});
		_htmlInputTextSerial.on("keyup", function (e) {
			var keycode = (e.keyCode ? e.keyCode : e.which);
			var $this = $(this);
			if (keycode == 13) {
				funInputHandler($this.val());
				$this.val("");
			}
		});
	});
</script>