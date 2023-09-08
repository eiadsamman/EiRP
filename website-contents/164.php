<?php 
	$q=base64_encode(serialize($_POST));
?>
<form id="jQsaveform">
	<textarea name="save_query" style="display:none;"><?php echo $q;?></textarea>
	<div class="btn-set" style="margin:10px;">
	<span>Save query</span><input type="text" name="save_name" id="jQsave_name" style="min-width:200px;" /><button type="button"id="jQsave_submit">Save</button><button type="button" id="jQsave_cancel">Cancel</button>
	</div>
</form>