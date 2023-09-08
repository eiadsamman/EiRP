<?php

include_once("admin/class/attendance.php");
include_once("admin/class/Template/class.template.build.php");

use System\Pool;
use System\Person\Attendance;
use System\Person\PersonNotFoundException;
use System\Person\PersonResignedException;
use System\Person\NotSignedInException;
use System\Person\AttendanceSectorException;
use System\Person\AttendanceTimeLimitException;
use System\Person\AttendanceDuplicateCheckin;
use Template\TemplateBuild;

if (isset($_POST['bulk'])) {
	exit;
}


if (isset($_POST['serial'])) {
	$att = new Attendance($sql);
	$att->SetDefaultCheckInAccount($USER->company->id);
	try {
		$att->load((int)$_POST['serial']);
		$ratt 	= $att->CheckIn($USER->info->id, null);

		if ($ratt) {
			header("ATT_RESULT: OK");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
			echo $att->info->name;
		} else {
			header("ATT_RESULT: FAIL");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
			echo $att->info->name;
		}
	} catch (PersonNotFoundException $e) {
		header("ATT_RESULT: NOTFOUND");
		header("ATT_IMAGE_ID: 0");
	} catch (PersonResignedException $e) {
		header("ATT_RESULT: RESIGNED");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	} catch (NotSignedInException $e) {
		header("ATT_RESULT: NOTSIGEND");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	} catch (AttendanceSectorException $e) {
		header("ATT_RESULT: SECTOR");
		header("ATT_IMAGE_ID: 0");
	} catch (AttendanceTimeLimitException $e) {
		header("ATT_RESULT: TIMELIMIT");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	} catch (AttendanceDuplicateCheckin $e) {
		header("ATT_RESULT: DUPLICATE");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	}

	exit;
}


$att = new Attendance($sql);
$defaultaccount = $att->DefaultCheckInAccount($USER->company->id);
if (!$defaultaccount) {
	echo "<h3>Selected company isn't valid or no linked account for check-in operations</h3>";
	if ($__helper) {
		echo "See documentations for this error at: <a href=\"{$__helper['directory']}\"/>{$__helper['title']}</a>, or contact system administrator";
	}
	exit;
}

$_TEMPLATE = new TemplateBuild("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(false);
$_TEMPLATE->SetWidth("800px");
$_TEMPLATE->Title($pageinfo['title'], null, null);


echo $_TEMPLATE->CommandBarStart(); ?>
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bom-table">
	<tbody>
		<tr>
			<td width="100%" colspan="2">
				<div class="btn-set"><input type="number" id="jQserialAdd" autocomplete="off" class="flex" placeholder="Serial Number" /><button type="button" style="min-width:100px;" id="jQserialSubmit">Submit</button></div>
			</td>
		</tr>
		<tr style="display:none">
			<td width="100%">
				<div class="btn-set"><textarea rows="5" placeholder="Serial Numbers" id="jQserialBulk" class="flex" style="height: 100px;resize: none;"></textarea></div>
			</td>
			<td>
				<div class="btn-set"><button type="button" style="height: 100px;min-width:100px;" id="jQbuttonBulk">Check-in</button></div>
			</td>
		</tr>
	</tbody>
</table>
<?php
echo $_TEMPLATE->CommandBarEnd();
$_TEMPLATE->ShiftStickyStart(16);
$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Attendance records</span>");
echo $_TEMPLATE->NewFrameBodyStart();
echo "<div id=\"jqOutput\" class=\"att-submitionlist\"></div>";
echo $_TEMPLATE->NewFrameBodyEnd();
?>


<script>
	$(function() {
		let _jqOutput = $("#jqOutput"),
			_jqInput = $("#jQserialAdd");
		var ticket = `<div>
				<span class="status l"><div></div></span>
				<span class="image"></span>
				<span class="content"><div class="employee-sid"></div><div class="employee-name">Loading...</div></span>
			</div>`;

		var submitserial = function() {
			let inputid = _jqInput.val().trim();
			if (inputid != "") {

				let new_ticket = $(ticket);
				new_ticket.find(".employee-sid").html(inputid);
				_jqOutput.prepend(new_ticket);
				_jqInput.val("");

				$.ajax({
					url: '<?php echo $pageinfo['directory']; ?>',
					method: 'POST',
					data: {
						'serial': inputid
					}
				}).done(function(o, textStatus, request) {
					let response = request.getResponseHeader('ATT_RESULT');

					let responseimage = request.getResponseHeader('ATT_IMAGE_ID');
					if (responseimage && responseimage != 0) {
						new_ticket.find(".image").css("background-image", `url('download/?id=${responseimage}&pr=t')`);
					} else {
						new_ticket.find(".image").css("background-image", `url('static/images/user-r.jpg')`);
					}

					if (response == "OK") {
						new_ticket.find(".employee-name").html(o);
						new_ticket.find(".status").removeClass("l").addClass("s").html("&#xf00c");
					} else {
						new_ticket.find(".status").removeClass("l").addClass("f").html("&#xf00d");
						new_ticket.find(".image").css("filter", "grayscale(100%)");
						if (response == "FAIL") {
							new_ticket.find(".employee-name").html(o);
						} else if (response == "NOTFOUND") {
							new_ticket.find(".employee-name").html("<span style=\"color:#f03\">موظف غير موجود</span>");
						} else if (response == "RESIGNED") {
							new_ticket.find(".employee-name").html(o + "<br /><span style=\"color:#f03\">موظف مفصول</span>");
						} else if (response == "NOTSIGEND") {
							new_ticket.find(".employee-name").html(o + "<br />Not signed-in!");
						} else if (response == "SECTOR") {
							new_ticket.find(".employee-name").html("Sector error");
						} else if (response == "TIMELIMIT") {
							new_ticket.find(".employee-name").html(o + "<br /><span style=\"color:#f03\">تسجيل ضمن زمن قصير</span>");
							new_ticket.find(".status").addClass("t").html("&#xe94e;");
						} else if (response == "DUPLICATE") {
							new_ticket.find(".employee-name").html(o + "<br /><span style=\"color:#f03\">تسجيل دخول مكرر</span>");
						}
					}

				}).fail(function(a, b, c) {
					messagesys.failure(b + " " + c);
				}).always(function() {
					_jqInput.focus().select();
				});
			}
		}

		_jqInput.on('keydown', function(e) {
			if ((e.keyCode ? e.keyCode : e.which) == 13) {
				submitserial();
			}
		});

		$("#jQserialSubmit").on("click", function(e) {
			submitserial();
		});

		_jqInput.focus();
	});
</script>