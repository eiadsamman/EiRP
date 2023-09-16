<?php
set_time_limit(60 * 3);
ini_set('memory_limit', '512M');
$ulib = new System\IO\AttachLib($app);

//Upload plugin requires (style/style.upload.css) & (jquery/uploader-1.0.js) files

/***Delete not used previous session uploads***/

/* $r = $app->db->query("SELECT up_id FROM uploads WHERE up_user={$app->user->info->id} AND up_rel=0 AND up_deleted=0 AND up_sessid != '" . session_id() . "';");
if ($r) {
	while ($row_release = $r->fetch_assoc()) {
		try {
			$dr  = @unlink($app->root . "uploads/" . $row_release['up_id']);
			$dr_v = @unlink($app->root . "uploads/" . $row_release['up_id'] . "_v");
			$dr_t = @unlink($app->root . "uploads/" . $row_release['up_id'] . "_t");
		} catch (Exception $e) {
			//Ignore
		}
	}
	$app->db->query("UPDATE uploads SET up_deleted=1 WHERE  up_user={$app->user->info->id} AND up_rel=0 AND up_deleted=0 AND up_sessid!='" . session_id() . "';");
} */


$accepted_mimes = array(
	"image/jpeg", "image/gif", "image/bmp", "image/png",
);
$resize_mimes = array(
	"image/jpeg"
);
if (isset($_GET['up']) && !isset($_POST['upload_file'])) {
	$outjson = array(
		"result" => 0,
		"msg" => "No files received at server side",
	);
	echo json_encode($outjson);
	exit;
}


if (isset($_POST['method']) && $_POST['method'] == "remove_attachment") {
	if ($ulib->delete($_POST['id'])) {
		echo "1";
	} else {
		echo "0";
	}

	exit;
}

if (isset($_POST, $_POST['upload_file']) && $_POST['upload_file'] == "true" && !empty($_FILES)) {
	$outjson = array(
		"result" => 0,
		"msg" => "",
	);
	if ($_FILES['file']['error'] > 0) {
		$outjson = array(
			"result" => 0,
			"msg" => "No files sent to the server, " . print_r($_FILES['file']['error'], true),
		);
	} else {
		$app->db->autocommit(false);

		$up_size = (float)$_FILES['file']['size'];
		$up_name = stripcslashes($_FILES['file']['name']);
		$up_pagefile = (int)$_POST['pagefile'];


		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$up_mime_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
		if (!$finfo || !$up_mime_type) {
			$outjson = array(
				"result" => 0,
				"msg" => "File type is invalid",
			);
		}
		finfo_close($finfo);

		$r = $app->db->query("INSERT INTO uploads (up_pagefile,up_rel,up_user,up_date,up_size,up_name,up_downloads,up_active,up_deleted,up_mime,up_sessid) 
									VALUES ($up_pagefile,0,{$app->user->info->id},FROM_UNIXTIME(" . time() . "),$up_size,'$up_name',0,0,0,'$up_mime_type','" . session_id() . "');");
		if (true === $r) {
			$up_id = $app->db->insert_id;
			$up_res = move_uploaded_file($_FILES['file']['tmp_name'], $app->root . "uploads/" . $up_id);
			if ($up_res) {

				$app->db->commit();
				$outjson = array(
					"result" => 1,
					"id" => $up_id,
					"size" => number_format($up_size / 1024, 2, ".", ",") . "KB",
					"name" => $up_name,
					"mime" => $up_mime_type,
				);

				try {
					//Reduce image size
					if (in_array($up_mime_type, $accepted_mimes)) {
						if (in_array($up_mime_type, $resize_mimes)) {
							$image = new System\IO\SimpleImage();

							if ($image->load($app->root . "uploads/" . $up_id) !== false) {
								$image->FixOrientation();

								/* IT CONSUMING THE SPACE, MAKE IT SMALLER*/

								$height = $image->getHeight();
								$width = $image->getWidth();

								if ($width >= $height && $width > 2000) {
									$image->resizeToWidth(2000);
								} else {
									$image->resizeToHeight(2000);
								}
								$image->save($app->root . "uploads/" . $up_id);


								if ($width >= $height && $width > 800) {
									$image->resizeToWidth(800);
								} elseif ($width < $height && $height > 800) {
									$image->resizeToHeight(800);
								}
								$image->save($app->root . "uploads/" . $up_id . "_v");

								if ($width > $height) {
									$image->resizeToHeight(200);
								} else {
									$image->resizeToWidth(200);
								}
								$image->crop(200, 200, 0.5, 0.5, array(255, 255, 255));
								$image->save($app->root . "uploads/" . $up_id . "_t");

								$image->destroy();
								if (file_exists($_FILES['file']['tmp_name']))
									@unlink($_FILES['file']['tmp_name']);
							}
						} else {
							copy($app->root . "uploads/" . $up_id, $app->root . "uploads/" . $up_id . "_v");
							copy($app->root . "uploads/" . $up_id, $app->root . "uploads/" . $up_id . "_t");
						}
					}
				} catch (Exception $e) {
				}
			} else {
				$app->db->rollback();
				$outjson = array(
					"result" => 0,
					"msg" => "Uploading attachment to the server failed",
				);
			}
		} else {
			$app->db->rollback();
			$outjson = array(
				"result" => 0,
				"msg" => "Unable to update database record",
			);
		}
	}
	echo json_encode($outjson);
	exit;
}

/*
<div class="btn-set">
	<input type="file" id="js_uploader_btn" multiple="multiple" accept="*//*" capture="camera" />
	<button id="js_upload_trigger">Attachments</button>
	<span id="js_upload_list"></span>
	<button id="js_upload_count"><span>0</span> files</button>
</div>

<script type="text/javascript">
var Upload=null;
$(document).ready(function(e) {
	Upload=$.Upload({
		objectHandler:$("#js_upload_list"),
		domselector:$("#js_uploader_btn"),
		dombutton:$("#js_upload_trigger"),
		list_button:$("#js_upload_count"),
		emptymessage:"[No files uploaded]",
		upload_url:"<?php echo $fs(186)->dir;?>",
		relatedpagefile:666
		}
	);
});
</script>
*/