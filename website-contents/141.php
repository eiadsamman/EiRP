<?php
use System\Template\Gremium\Gremium;

if ($fs()->permission->edit && isset($_POST['change-cwk'], $_POST['status'])) {
	$_POST['change-cwk'] = (int) $_POST['change-cwk'];
	$_POST['status']     = (int) $_POST['status'];
	if ($app->db->query("UPDATE calendar_weekends SET cwk_status={$_POST['status']} WHERE cwk_id={$_POST['change-cwk']}")) {
		echo "1";
	} else {
		echo "0";
	}
	exit;
}
?>
<?php
$grem = new Gremium(true);
$grem->header()->serve("<h1>" . $fs()->title . "</h1>");
$grem->article()->open();
?>
<table>
	<thead>
		<tr>
			<td></td>
			<td width="100%">Week day</td>
		</tr>
	</thead>
	<tbody>
		<?php
		$r = $app->db->query("SELECT cwk_status,cwk_name,cwk_id FROM calendar_weekends ORDER BY cwk_id");
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				echo "<tr>";

				echo "<td class=\"checkbox" . ($fs()->permission->edit ? "" : " disabled ") . "\" style=\"min-width:38px;width:38px;\">
				<label>
				<input 
					data-cwk_id = \"{$row['cwk_id']}\" 
					type = \"checkbox\" 
					" . ((int) $row['cwk_status'] == 1 ? " checked " : "") . "
					" . ($fs()->permission->edit ? "" : " disabled ") . "
				/>
				</label></td>";

				echo "<td>{$row['cwk_name']}</td>";
				echo "</tr>";
			}
		}
		?>
	</tbody>
</table>

<?php
$grem->getLast()->close();
unset($grem);
?>
<?php if ($fs()->permission->edit) { ?>
	<script>
		$(document).ready(function (e) {
			$("[data-cwk_id]").on('click', function (e) {
				var $this = $(this),
					_cwk_id = $this.attr("data-cwk_id"),
					_status = $this.prop("checked");
				$this.prop("disabled", true);

				$.ajax({
					url: "<?php echo $fs()->dir; ?>",
					type: "POST",
					data: {
						"change-cwk": _cwk_id,
						"status": _status ? 1 : 0
					}
				}).done(function (data) {
					if (data == "1") {
						messagesys.success("Weekend day updated successfully");
					} else {
						messagesys.failure("Failed to udpate weekend day, try again");
						$this.prop("checked", !_status);
					}
					$this.prop("disabled", false);
				}).fail(function (a, b, c) {
					messagesys.failure(b + " - " + c);
					$this.prop("disabled", false);
				});
			});
		});
	</script>
<?php } ?>