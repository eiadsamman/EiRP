<?php

use System\Core\FileSystem\Page;

if (isset($_POST['glyph'])) {
	if ($app->db->query("UPDATE pagefile SET trd_attrib4=" . ($_POST['glyph'] == "" ? "NULL" : "'{$_POST['glyph']}'") . " WHERE trd_id=" . ((int)$_POST['id']) . "")) {
		echo '1';
	} else {
		echo '0';
	}
	exit;
}
if (isset($_POST['color'])) {
	if ($app->db->query("UPDATE pagefile SET trd_attrib5=" . ($_POST['color'] == "" ? "NULL" : "'{$_POST['color']}'") . " WHERE trd_id=" . ((int)$_POST['id']) . "")) {
		echo '1';
	} else {
		echo '0';
	}
	exit;
}
$glypharray = array('900', '903', '904', '906', '90a', '90d', '915', '91c', '91f', '920', '922', '923', '924', '926', '92c', '92d', '92e', '92f', '930', '931', '932', '937', '939', '93b', '93c', '93f', '940', '941', '942', '944', '945', '947', '948', '94e', '952', '953', '954', '956', '958', '95c', '95d', '960', '961', '962', '964', '96b', '971', '972', '973', '974', '975', '979', '986', '987', '988', '989', '98d', '98f', '990', '991', '994', '995', '997', '99a', '99b', '99c', '99f', '9ab', '9ac', '9b0', '9b6', '9b7', '9b8', '9bc', '9bd', '9c5', '9c6', '9c9', '9ca', '9cd', '9ce', '9cf', '9d1', '9d2', '9d4', '9d7', '9d9', 'a08', 'a09', 'a0c', 'a0f', 'a10', 'a11', 'a13', 'a14', 'a1c', 'a1d', 'a1e', 'a2f', 'a30', 'a41', 'a42', 'a43', 'a44', 'a4e', 'a52', 'a53', 'a54', 'a56', 'a5a', 'a5b', 'a62', 'a6d', 'a6e', 'a70', 'a7d', 'a7e', 'aa0', 'ade');
?>
<style>
	#glyph>span {
		font-family: icomoon4;
		font-size: 18px;
		display: inline-block;
		padding:10px;
		cursor: pointer;
		border-radius: 3px;
		min-width: 40px;
		text-align: center;
		background-color: var(--root-modal-background-color);
	}

	#glyph>span:hover {
		transform: scale(1.5, 1.5);
		box-shadow: 0px 0px 4px rgba(0, 0, 0, 0.6);
	}

	#glyph {
		width: 620px;
		border: solid 1px #aaa;
		padding: 5px;
		border-radius: 5px 5px 5px 5px;
		display: none;
		position: absolute;
		z-index: 60;
		background-color: var(--root-modal-background-color);
	}

	#colorList {
		width: 313px;
		border: solid 1px #aaa;
		padding: 5px;
		border-radius: 0px 5px 5px 5px;
		display: none;
		position: absolute;
		z-index: 60;
		background-color: var(--root-modal-background-color);
	}

	#colorList>span {
		display: inline-block;
		width: 28px;
		height: 28px;
		margin: 1px;
		border-radius: 3px;
		cursor: pointer;
	}

	#colorList>span:hover {
		transform: scale(1.5, 1.5);
		box-shadow: 0px 0px 4px rgba(0, 0, 0, 0.6);
	}

	.cssGlyph {
		font-family: icomoon4;
		min-width: 50px
	}

	.cssColor>span {
		display: block;
		width: 18px;
		height: 18px;
		border: solid 1px #999;
		border-radius: 3px;
	}
</style>

<div style="position:relative;" id="jQcont">
	<div id="glyph">
		<span data-glyph="">&nbsp;</span>
		<?php
		foreach ($glypharray as $glyph) {
			echo "<span data-glyph=\"{$glyph}\">&#xe{$glyph};</span>";
		}
		?>
		<div class="btn-set" style="text-align:center;margin-top:10px;"><button id="jQcancel">Cancel</button></div>
	</div>

	<div id="colorList">
		<span style="background-color:#fff" data-color=""></span>
		<?php
		$start = 0;
		$step = 6;
		echo "";
		for ($r = $start; $r < 16; $r += $step)
			for ($g = $start; $g < 16; $g += $step)
				for ($b = $start; $b < 16; $b += $step) {
					$color = dechex($r) . dechex($g) . dechex($b);
					echo "<span data-color=\"$color\" style=\"background-color:#$color\"></span>";
				}
		?>
		<div class="btn-set" style="text-align:center;margin-top:10px;"><button id="jQcancelColor">Cancel</button></div>
	</div>
</div>

<?php
function inner(Page $fs, int $id)
{
	static $cnt = 1;
	$check = true;
	$f = new  \System\Core\FileSystem\Data();
	foreach ($fs->children($id) as $file_id => $file) {

		$cnt += $check ? 1 : 0;
		$f = $file;
		if ($f->visible == true) {

			echo "<tr id=\"{$f->id}\"><td>{$f->id}</td>";
			echo "<td class=\"btn-set\">";
			echo "<button data-id=\"{$f->id}\" class=\"cssGlyph\">" . ($f->icon != null ? "&#xe{$f->icon};" : "&nbsp;") . "</button>";
			echo "<button data-id=\"{$f->id}\" class=\"cssColor\"><span style=\"background-color:#" . ($f->color == null ? "fff" : $f->color) . "\"></span></button>";
			echo "</td>";
			echo "<td><div style=\"margin-left:" . (($cnt) * 20) . "px;\"><a href=\"{$f->dir}\" target=\"_blank\">{$f->title}</a></div></td>";
			echo "</tr>";

			inner($fs, (int)$f->id);
		}
		$cnt -= $check ? 1 : 0;
		$check = false;
	}
}
?>
<table class="hover" style="margin-top:15px;">
	<thead>
		<tr>
			<td>#</td>
			<td></td>
			<td width="100%">Page name</td>
		</tr>
	</thead>
	<tbody id="jQoutput">
		<?php

		$f = new  \System\Core\FileSystem\Data();
		foreach ($fs->children(0) as $file_id => $file) {
			$f = $file;
			if ($f->visible == true) {
				echo "<tr id=\"{$f->id}\"><td>{$f->id}</td>";
				echo "<td class=\"btn-set\"><button data-id=\"{$f->id}\" class=\"cssGlyph\">" . ($f->icon != null ? "&#xe{$f->icon};" : "&nbsp;") . "</button><button 
					data-id=\"{$f->id}\" class=\"cssColor\"><span style=\"background-color:#" . ($f->color == null ? "fff" : $f->color) . "\"></span></button></td>";
				echo "<td><a href=\"{$f->dir}\" target=\"_blank\">" . $f->title . "</a></td>";
				echo "</tr>";
				inner($fs, (int)$f->id);
			}
		}
		?>
	</tbody>
</table>
<script>
	$(document).ready(function(e) {
		var $pop = $("#glyph");
		var $col = $("#colorList");
		var $current = null;
		$("#jQcancel").on('click', function() {
			$pop.hide();
		});
		$("#glyph").find("span").on('click', function() {
			var _glyph = $(this).attr("data-glyph");

			$current.html("&#xe" + _glyph + ";");
			$.ajax({
				url: "",
				data: {
					'id': $current.attr("data-id"),
					'glyph': _glyph
				},
				type: "POST"
			}).done(function(data) {
				if (data == '1') {
					messagesys.success("Updated successfully");
				} else {
					messagesys.failure("Failed to updated");
				}
				$pop.hide();
			});
		});
		$("#colorList").find("span").on('click', function() {
			var _color = $(this).attr("data-color");
			$current.find("span").css("background-color", "#" + (_color == "" ? "fff" : _color));
			$.ajax({
				url: "",
				data: {
					'id': $current.attr("data-id"),
					'color': _color
				},
				type: "POST"
			}).done(function(data) {
				if (data == '1') {
					messagesys.success("Updated successfully");
				} else {
					messagesys.failure("Failed to updated");
				}
				$col.hide();
			});
		});
		$(".cssGlyph").on('click', function(e) {
			$pop.hide();
			$col.hide();
			$current = $(this);
			var pos = {
				x: 0,
				y: 0,
				h: 0
			};
			var temp = $(this).offset();
			var cont = $("#jQcont").offset();
			pos.x = temp.left - cont.left;
			pos.y = temp.top - cont.top;
			pos.h = $(this).outerHeight();
			$pop.css({
				'display': 'block',
				'left': pos.x,
				'top': pos.y + pos.h
			});
		});
		$(".cssColor").on('click', function(e) {
			$pop.hide();
			$col.hide();
			$current = $(this);
			var pos = {
				x: 0,
				y: 0,
				h: 0
			};
			var temp = $(this).offset();
			var cont = $("#jQcont").offset();
			pos.x = temp.left - cont.left;
			pos.y = temp.top - cont.top;
			pos.h = $(this).outerHeight();
			$col.css({
				'display': 'block',
				'left': pos.x,
				'top': pos.y + pos.h
			});
		});
		$("#jQcancelColor").on('click', function() {
			$col.hide();
		});
	});
</script>