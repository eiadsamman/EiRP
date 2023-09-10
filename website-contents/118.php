<?php
use System\Individual\Attendance\Registration;
include_once("admin/class/attendance.php");
include_once("admin/class/Template/class.template.build.php");

use System\App;
use System\Person\Attendance;
use System\Person\PersonNotFoundException;
use System\Person\PersonResignedException;
use System\Person\ExceptionNotSignedIn;
use System\Person\LocationInvalid;
use System\Person\ExceptionTimeLimit;
use System\Person\AttendanceExceptionDuplicateCheckin;
use Template\Body;

$att = new Registration($app);
$loc = $att->DefaultCheckInternalAccounts($app->user->company->id);



if (isset($_POST['serial'])) {
	$att = new Registration($app);
	try {
		$att->load((int)$_POST['serial']);
		$ratt 	= $att->CheckIn( (int)$_POST['partition']);

		if ($ratt) {
			header("ATT_RESULT: OK");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
			echo $att->info->name;
		} else {
			header("ATT_RESULT: FAIL");
			header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
			echo $att->info->name;
		}
	} catch (\System\Individual\PersonNotFoundException $e) {
		header("ATT_RESULT: NOTFOUND");
		header("ATT_IMAGE_ID: 0");
	} catch (\System\Individual\PersonResignedException $e) {
		header("ATT_RESULT: RESIGNED");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	} catch (\System\Individual\Attendance\ExceptionNotSignedIn $e) {
		header("ATT_RESULT: NOTSIGEND");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	} catch (\System\Individual\Attendance\LocationInvalid $e) {
		header("ATT_RESULT: SECTOR");
		header("ATT_IMAGE_ID: 0");
	} catch (\System\Individual\Attendance\ExceptionTimeLimit $e) {
		header("ATT_RESULT: TIMELIMIT");
		header("ATT_IMAGE_ID: " . ($att->info->photoid ? $att->info->photoid : "0"));
		echo $att->info->name;
	}

	exit;
}

if (isset($_POST['populate'])) {
	$att = new Registration($app);
	$sector = (int)$_POST['populate'];
	$r = $att->ReportOngoingBySector(["company" => $app->user->company->id, "sector" => $sector]);
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$photo = $row['up_id'] != null ? "download/?id={$row['up_id']}&pr=t" : "";
			Body::AttendanceTicketPlot(null, $photo, $row['lbr_id'], $row['usr_firstname']);
		}
	}
	exit;
}

$_TEMPLATE = new Body("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(false);
$_TEMPLATE->SetWidth("800px");

$_TEMPLATE->Title($fs()->title, null, null);
?>


<?php echo $_TEMPLATE->CommandBarStart(); ?>

<div style="display: flex;margin-bottom: 10px;">
	<?php if ($USER->account) { ?>
		<div data-id="<?php echo ($USER->account->id); ?>" class="horzFixed JQLocSelection" style="min-width:150px;width:150px;max-width:150px;height:40px;margin: 4px 10px 4px 0px;">
			<div><?php echo ($USER->account->name); ?></div>
		</div>
	<?php } ?>
	<div class="horzScroll" id="JQSelectSection" style="padding:4px">
		<?php
		foreach ($loc as $lock => $locv) {
			echo "<div class=\"JQLocSelection\" data-id=\"{$locv[0]}\"><div>{$locv[1]}</div></div>";
		}
		?>
	</div>
</div>
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
				<div class="btn-set"><button type="button" style="height: 100px; min-width:100px;" id="jQbuttonBulk">Check-in</button></div>
			</td>
		</tr>
	</tbody>
</table>
<?php

echo $_TEMPLATE->CommandBarEnd();

$_TEMPLATE->ShiftStickyStart(74);


$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Attendance records</span>");
echo $_TEMPLATE->NewFrameBodyStart();
echo "<div id=\"jqOutputOutput\" class=\"att-submitionlist\"></div>";
echo $_TEMPLATE->NewFrameBodyEnd();


$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Current attendance</span>");
echo $_TEMPLATE->NewFrameBodyStart();
echo "<div id=\"jqOutputCurrent\" class=\"att-submitionlist\"></div>";
echo $_TEMPLATE->NewFrameBodyEnd();
?>






<script type="text/javascript">
	$(function() {
		var CurrentSelection = null;
		let _jqOutput = $("#jqOutputOutput"),
			_jqInput = $("#jQserialAdd");
		var ticket = `<div>
				<span class="status l"><div></div></span>
				<span class="image"></span>
				<span class="content"><div class="employee-sid"></div><div class="employee-name">Loading...</div></span>
			</div>`;


		$(".JQLocSelection").on("click", function() {
			$(".JQLocSelection").removeClass("locSelected");
			$(this).addClass("locSelected");
			CurrentSelection = $(this).attr("data-id");
			overlay.show();
			$.ajax({
				url: '<?php echo $fs()->dir; ?>',
				method: 'POST',
				data: {
					'populate': CurrentSelection
				}
			}).done(function(o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				$("#jqOutputCurrent").html($(o));
				_jqOutput.html("");
			}).fail(function(a, b, c) {
				messagesys.failure(b + " " + c);
			}).always(function() {
				overlay.hide();
			});
		});


		var submitserial = function() {
			let inputid = _jqInput.val().trim();
			if (inputid != "") {

				let new_ticket = $(ticket);
				new_ticket.find(".employee-sid").html(inputid);
				_jqOutput.prepend(new_ticket);
				_jqInput.val("");

				$.ajax({
					url: '<?php echo $fs()->dir; ?>',
					method: 'POST',
					data: {
						'serial': inputid,
						'partition': CurrentSelection
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
							new_ticket.find(".employee-name").html("Not found");
						} else if (response == "RESIGNED") {
							new_ticket.find(".employee-name").html(o + "<br />Resigned");
						} else if (response == "NOTSIGEND") {
							new_ticket.find(".employee-name").html(o + "<br />Not signed-in");
						} else if (response == "SECTOR") {
							new_ticket.find(".employee-name").html("Sector error");
						} else if (response == "TIMELIMIT") {
							new_ticket.find(".employee-name").html(o + "<br />Time limited");
							new_ticket.find(".status").addClass("t").html("&#xe94e;");
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

	});
</script>