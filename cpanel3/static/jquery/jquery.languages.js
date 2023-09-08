function LN_list(){
	$.ajax({
		url:"ajax/languages.display.php",
		data:"",
		type:"POST"
	}).done(function(data){
		$("#jQLN_output").find("tbody").html(data);
	}).fail(function(a,b,c){
		messagesys.failure(b);
	});
}($,jQuery);


$(function(){
	$("#jQlngAdd").on('click',function(){
		$(this).blur();
		popup.open('ajax/languages.add.php',{'ln_id':0,'method':'add','page':_page,'line':'0'},"POST",function(object){
			object.find("input[type=text]").first().focus();
		});
	});
	$("#jQLN_output").on('click',"td[data-ln_edit]",function(){
		var $this=$(this),
			$tr=$this.closest("tr"),
			_ln_id=$tr.attr("data-ln_id");
		popup.open('ajax/languages.add.php',{'ln_id':_ln_id,'method':'edit','page':_page,'line':'0'},"POST",function(object){
			object.find("input[type=text]").first().focus().select();
		});
	});
	
	$(popup.object()).on('submit',"#frmLanguages",function(e){
		e.preventDefault();
		var $this=$(this);
		if($this.find("input[name=__lng-name]").val().trim()==""){
			messagesys.failure("Language name is required");return false;
		}
		$.ajax({
			url:"ajax/languages.add.do.php",
			type:"POST",
			data: $this.serialize()
		}).done(function(data){
			try{var json=JSON.parse(data);}catch(e){return false;}
			if(json.result){
				popup.hide();
				messagesys.success(json.message);
				LN_list();
			}else{
				messagesys.failure(json.message);
			}
		}).fail(function(a,b,c){
			messagesys.failure(b);
		});
		return false;
	});
	
	
	$("#jQLN_output").on('click',"td[data-ln_check]",function(){
		var $this=$(this),
			$tr=$this.closest("tr"),
			_ln_id=$tr.attr("data-ln_id");
		$.ajax({
			url:"ajax/languages.default.php",
			type:"POST",
			data: {"ln_id":_ln_id}
		}).done(function(data){
			if(data=="1"){
				messagesys.success("Default language set successfully");
				$("#jQLN_output").find("td[data-ln_check]").html("&#xe64b;");
				$this.html("&#xe64a;");
			}else{
				messagesys.failure("Setting default language failed");
			}
		}).fail(function(a,b,c){messagesys.failure(b);});
	});
	
	$("#jQLN_output").on('click',"td[data-ln_delete]",function(){
		var $tr=$(this).closest("tr"),
			_ln_id=$tr.attr("data-ln_id");
		popup.plain("Delete language","<div class=\"cpanel_form\"><div>Are you sure you want to delete this language?</div></div>",
					"<div class=\"btn-set\" style=\"justify-content:flex-end;padding:0px;\">"+
						"<button type=\"submit\" data-ln_id=\""+_ln_id+"\" id=\"jQlanugageConfirmDelete\">Delete</button>"+
						"<button type=\"button\" class=\"jQclosepopup\">Cancel</button></div>");
	});
	
	
	
	$(popup.object()).on('click','#jQlanugageConfirmDelete',function(){
		var _ln_id=$(this).attr("data-ln_id"),
			$tr=$("#jQLN_output").find("tr[data-ln_id="+_ln_id+"]");
		
		$.ajax({
			url:"ajax/languages.delete.php",
			type:"POST",
			data:{"ln_id":_ln_id}
		}).done(function(result){
			if(result=="1"){
				messagesys.success("Language removed successfully");
				$tr.remove();
				popup.hide();
			}else{
				messagesys.failure("Deleting language failed");
			}
		}).fail(function(a,b,c){
			messagesys.failure(b);
		});
	});
	
	$(popup.object()).on('click',"#jQaddlanguageform",function(){
		$(popup.object()).find("#frmLanguages").submit();
	});
	

	
	$(popup.object()).on('click',".jQclosepopup",function(){
		popup.hide();
	});
});