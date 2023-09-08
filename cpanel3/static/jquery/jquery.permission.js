function PR_list(){
	$.ajax({
		url:"ajax/permisson.display.php",
		data:"",
		type:"POST"
	}).done(function(data){
		$("#jQPR_output").find("tbody").html(data);
	}).fail(function(a,b,c){
		messagesys.failure(b);
	});
}($,jQuery);


$(function(){
	$("#jQperAdd").on('click',function(){
		$(this).blur();
		popup.open('ajax/permission.add.php',{'pr_id':0,'method':'add','page':_page,'line':'0'},"POST",function(object){
			object.find("input[type=text]").first().focus();
		});
	});
	$("#jQPR_output").on('click',"td[data-pf_edit]",function(){
		var $this=$(this),
			$tr=$this.closest("tr"),
			_pr_id=$tr.attr("data-pr_id");
		popup.open('ajax/permission.add.php',{'pr_id':_pr_id,'method':'edit','page':_page,'line':'0'},"POST",function(object){
			object.find("input[type=text]").first().focus().select();
		});
	});
	$(popup.object()).on('submit',"#frmPermission",function(e){
		e.preventDefault();
		var $this=$(this);
		if($this.find("input[name=__per-title]").val().trim()==""){
			messagesys.failure("Permission name is required");return false;
		}
		var _serz=$(this).serialize();
		$.ajax({
			url:"ajax/permission.add.do.php",
			type:"POST",
			data: _serz
		}).done(function(data){
			try{var json=JSON.parse(data);}catch(e){return false;}
			if(json.result){
				popup.hide();
				messagesys.success(json.message);
				PR_list();
			}else{
				messagesys.failure(json.message);
			}
		}).fail(function(){});
		return false;
	});
	
	$(popup.object()).on('click','#jQpermissionConfirmDelete',function(){
		var _pr_id=$(this).attr("data-pr_id"),
			$tr=$("#jQPR_output").find("tr[data-pr_id="+_pr_id+"]");
		$.ajax({
			url:"ajax/permission.delete.php",
			type:"POST",
			data:{"pr_id":_pr_id}
		}).done(function(result){
			if(result=="1"){
				messagesys.success("Permission removed successfully");
				$tr.remove();
				popup.hide();
			}else{
				messagesys.failure("Deleting permission failed");
			}
		}).fail(function(a,b,c){
			messagesys.failure(b);
		});
	});
	
	
	
	$(popup.object()).on('click',"#jQaddpermissionsbutton",function(){
		$(popup.object()).find("#frmPermission").submit();
	});
	
	
	
	
	$("#jQPR_output").on('click',"td[data-pf_delete]",function(){
		var $tr=$(this).closest("tr"),
			_pr_id=$tr.attr("data-pr_id");
		popup.plain("Delete permission","<div class=\"cpanel_form\"><div>Are you sure you want to delete this permission?</div></div>",
					"<div class=\"btn-set\" style=\"justify-content:flex-end;padding:0px;\">"+
						"<button type=\"submit\" data-pr_id=\""+_pr_id+"\" id=\"jQpermissionConfirmDelete\">Delete</button>"+
						"<button type=\"button\" class=\"jQclosepopup\">Cancel</button></div>"
				);
	});
	
	
	$(popup.object()).on('click',".jQclosepopup",function(){
		popup.hide();
	});
});

















