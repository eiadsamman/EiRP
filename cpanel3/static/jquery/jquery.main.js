/*Fetch trace bar*/

if(window.history && window.history.pushState){
	p_history=true;
	window.history.pushState({"p":p_previous,"s":p_listing},null,null);
}
function PF_trace(p,s,h){
	$("#jQaddchild,#jQeditchild,#jQmovechild,#jQdeletechild,#jQPF_searchField").prop('disabled',true);
	$.ajax({
		url:"ajax/file.trace.php",
		type:"POST",
		data:{'p':p,'s':~~s}
	}).done(function(data) {
		try{
			var json=JSON.parse(data);
		}catch(e){
			noti.display('Parsing output failed',false);return false;
		}
		if(json.result==true && json.search==false){
			$("#jQpagefile_id").html(json.id);
			$("#jQcount").html(json.count+" pages");
			$("#jQtracer").html("<div>"+json.trace+"</div>");
			$("#jQaddchild,#jQeditchild,#jQmovechild,#jQdeletechild").prop('disabled',false).attr("data-pf_id",json.id).css("display","block");
			$("#jQclearSearch").css("display","none");
			$("#jQopenchild").css("display","block").attr("href",_baseurl+json.directory);
			$("#jQPF_searchField").prop("disabled",false);
			if(!!~~json.root){
				$("#jQeditchild,#jQmovechild,#jQdeletechild").prop('disabled',true);
			}
			if(p_history && !!!h){
				window.history.pushState({"p":p,"s":false},null,'m_pagefile/?p='+p);
			}
		}else if(json.result==true && json.search==true){
			$("#jQpagefile_id").html(json.id);
			$("#jQcount").html(json.count+" pages");
			$("#jQtracer").html("Searching terms: "+json.message);
			$("#jQaddchild,#jQeditchild,#jQmovechild,#jQdeletechild").prop('disabled',true).css("display","none");
			$("#jQclearSearch").css("display","block");
			$("#jQopenchild").css("display","none");
			$("#jQPF_searchField").prop("disabled",false);
			if(p_history && !!!h){
				window.history.pushState({"p":p,"s":true},null,'m_pagefile/?s='+p);
			}
		}
		
	}).fail(function(){});
}($,jQuery);

/*Fetch pagefiles list*/
function PF_list(p,s,h){
	if(!~~s){pf_p=p;pf_method=1;}else{pf_s=p;pf_method=2;}
	PF_trace(p,s,h);
	$.ajax({
		url:"ajax/file.display.php",
		data:{"p":p,"s":~~s},
		type:"POST"
	}).done(function(data) {
		$("#jQPF_output").find("tbody").html(data);
	}).fail(function(){});
}($,jQuery);


if(p_history){
	window.onpopstate = function(e){
		if(e.state){
			PF_list(e.state.p,!!e.state.s,true);
		}
	};
}

$(function(){
	/*Pagefile links emulator*/
	$("#jQPF_output").on('click',".jQPF_emu",function(e){
		e.preventDefault();
		PF_list($(this).attr("data-href"));
		return false;
	});
	$("#jQtracer").on('click',".jQPF_emu",function(e){
		e.preventDefault();
		PF_list($(this).attr("data-href"));
		return false;
	});
	
	/*Clear search*/
	$("#jQclearSearch").on('click',function(){
		PF_list(pf_p);
	});
	
	/*Search field*/
	$("#jQPF_searchForm").on('submit',function(e){
		e.preventDefault();
		if($("#jQPF_searchField").val().trim()==""){return false;}
		PF_list($("#jQPF_searchField").val(),true);
		return false;
	});
	
	/*Refresh Button*/
	$("#jQrefresh").on('click',function(){
		if(pf_method==1){
			PF_list(pf_p);
		}else{
			PF_list(pf_s,true);
		}
	});
	
	/*Add-Edit-Delete Controlls*/
	$("#jQaddchild").on('click',function(){
		var $this=$(this),_pf_id=$this.attr("data-pf_id");
		$this.blur();
		popup.open('ajax/file.add.php',{'pf_id':_pf_id,'method':'add','page':_page,'line':'0'},"POST",function(object){
			object.find("input[type=text]").first().focus();
			object.find(".rwicon").on("click",function(){
				let altstate=$(this).attr("data-state");
				$(this).attr("data-state",altstate=="0"?"1":"0");
				object.find("input[data-colid="+$(this).attr("data-colid")+"]").prop("checked",altstate==0?true:false);
			});
		});
	});
	$("#jQmovechild").on('click',function(){
		var $this=$(this),_pf_id=$this.attr("data-pf_id");
		$this.blur();
		popup.open('ajax/file.move.php',{'pf_id':_pf_id,'method':'move','page':_page,'line':'0'},"POST",function(object){});
	});
	$("#jQdeletechild").on('click',function(){
		var $this=$(this),_pf_id=$this.attr("data-pf_id");
		$this.blur();
		popup.open('ajax/file.delete.php',{'pf_id':_pf_id,'method':'delete','page':_page,'line':'0'},"POST",function(object){
			object.find("#jQdeleteformbutton").focus();
		});
	});
	$("#jQeditchild").on('click',function(){
		/*HERE*/
		var $this=$(this),_pf_id=$this.attr("data-pf_id");
		$this.blur();
		
		popup.open('ajax/file.add.php',{'pf_id':_pf_id,'method':'edit','page':_page,'line':'0'},"POST",function(object){
			object.find("input[type=text]").first().focus();
			object.find(".rwicon").on("click",function(){
				let altstate=$(this).attr("data-state");
				$(this).attr("data-state",altstate=="0"?"1":"0");
				object.find("input[data-colid="+$(this).attr("data-colid")+"]").prop("checked",altstate==0?true:false);
			});
		});
	});
	
	$("#jQPF_output").on('click',"[data-pf_delete]",function(){
		var $this=$(this),
			$tr=$this.closest("tr"),
			_pf_id=$tr.attr("data-pf_id");
		popup.open('ajax/file.delete.php',{'pf_id':_pf_id,'method':'delete','page':_page,'line':'1'},"POST",function(object){
			object.find("#jQdeleteformbutton").focus();
		});
	});
	$("#jQPF_output").on('click',"[data-pf_edit]",function(){
		/*HERE*/
		var $this=$(this),
			$tr=$this.closest("tr"),
			_pf_id=$tr.attr("data-pf_id");
		popup.open('ajax/file.add.php',{'pf_id':_pf_id,'method':'edit','page':_page,'line':'1'},"POST",function(object){
			object.find("input[type=text]").first().focus();
			object.find(".rwicon").on("click",function(){
				let altstate=$(this).attr("data-state");
				$(this).attr("data-state",altstate=="0"?"1":"0");
				object.find("input[data-colid="+$(this).attr("data-colid")+"]").prop("checked",altstate==0?true:false);
			});
		});
	});
		
	/*Add|Edit*/
	$(popup.object()).on('submit',"#frmMain",function(e){
		e.preventDefault();
		serialize=$(this).serialize();
		$.ajax({
			url:"ajax/file.add.do.php",
			type:"POST",
			data: serialize
		}).done(function(data){
			try{var json=JSON.parse(data);}catch(e){return false;}
			if(json.result){
				popup.hide();
				messagesys.success(json.message);
				if(~~json.line==1){
					PF_list(pf_p);
				}else{
					PF_list(json.directory);
				}
			}else{
				messagesys.failure(json.message);
			}
		}).fail(function(){});
		return false;
	});
	
	/*Delete*/
	$(popup.object()).on('submit',"#frmDeletePageFile",function(e){
		
		e.preventDefault();
		serialize=$(this).serialize();
		var post=$.ajax({
			url:"ajax/file.delete.do.php",
			type:"POST",
			data:serialize
		}).done(function(data){
			try{var json=JSON.parse(data);}catch(e){return false;}
			if(json.result){
				messagesys.success(json.message);
				popup.hide();
				if(~~json.line!=0){
					$("#jQPF_output").find("tr[data-pf_id="+json.line+"]").remove();
					PF_trace(json.directory);
				}else{
					PF_list(json.directory);
				}
			}else{
				messagesys.failure(json.message);
			}
		}).fail(function(a,b,c){
			messagesys.failure(c);
		});
		return false;
	});
	
	/*Sort page files*/
	$("#jQPF_output > tbody").sortable({
		handle : '.orderHandle',
		items: 'tr' ,
		opacity: 1 ,
		axis: 'y' ,
		start:function(event, ui){
			ui.item.addClass("ui-sortable-start");
		},
		stop:function(event,ui){
			ui.item.removeClass("ui-sortable-start");
		},
		update : function (event,ui) {
			ui.item.removeClass("ui-sortable-start");
			var neworder=[];
			 $(this).find("tr").each(function(index, element) {
				neworder.push($(this).attr("data-pf_id"));
			});
			$.ajax({
				url:"ajax/file.order.php",
				data:{"order":neworder},
				type:"POST"
			}).done(function(data) {
				if(data=="1"){
					messagesys.success("Order updated successfully");
				}else{
					messagesys.failure("Updating order failed");
				}
			}).fail(function(){});
		}
	});
	
	/*Visible*/
	$("#jQPF_output").on('click',"[data-pf_visible]",function(e){
		var _pf_id=$(this).closest("tr").attr("data-pf_id");
		$this=$(this);
		$(".tableOverLay").css({'display':'block'});
		$.ajax({
			url:"ajax/file.visible.php",
			data:{'id':_pf_id},
			type:"POST",
		}).done(function(data) {
			if(data=='3'){
				messagesys.failure("Changing visibility failed");
			}else{
				messagesys.success("Visibility changed successfully");
				$this.html((data==1?"&#xe62e;":"&#xe631;"));
			}
			$(".tableOverLay").css({'display':'none'});
		}).fail(function() {$(".tableOverLay").css({'display':'none'});});
		return false;
	});
	
	/*Access*/
	$("#jQPF_output").on('click',"[data-pf_access]",function(e){
		var _pf_id=$(this).closest("tr").attr("data-pf_id");
		$this=$(this);
		$(".tableOverLay").css({'display':'block'});
		$.ajax({
			url:"ajax/file.enable.php",
			data:{'id':_pf_id},
			type:"POST",
		}).done(function(data) {
			if(data=='3'){
				messagesys.failure("Changing accessibility failed");
			}else{
				messagesys.success("Accessibility changed successfully");
				$this.html((data==1?"&#xe622;":"&#xe621;"));
			}
			$(".tableOverLay").css({'display':'none'});
		}).fail(function() {$(".tableOverLay").css({'display':'none'});});
		return false;
	});
	
	/*Popup cloes button*/
	$(popup.object()).on('click',".jQclosepopup",function(){
		popup.hide();
	});
	$(popup.object()).on('click',"#jQformsubmit",function(){
		$("#frmMain").submit();
	});
	
	$(popup.object()).on('click',"#jQdeleteformbutton",function(){
		$(popup.object()).find("#frmDeletePageFile").submit();
	});
	
	$(popup.object()).on('click',"#jQmoveformbutton",function(){
		$(popup.object()).find("#frmMovePageFile").submit();
	});
	
	
	
	
	
	/*Move*/
	$("#jQPF_output").on('click',"[data-pf_move]",function(){
		var $this=$(this),
			$tr=$this.closest("tr"),
			_pf_id=$tr.attr("data-pf_id");
		popup.open('ajax/file.move.php',{'pf_id':_pf_id,'method':'move','page':_page,'line':'1'},"POST",function(object){});
	});
	
	$(popup.object()).on('click',"#jQpagefiletree span > i",function(e){
		var $this=$(this),
			$span=$this.parent(),
			_pf_id=$span.attr("data-pf_id"),
			_pf_dir=$span.attr("data-pf_dir"),
			_pf_ign=!!~~$span.attr("data-ignore"),
			_pf_exp=!!~~$span.attr("data-expanded"),
			_pf_ftc=!!~~$span.attr("data-fetched"),
			_pf_abl=!!~~$span.attr("data-expandable"),
			_pf_lod=!!~~$span.attr("data-loading");
			
		
		
		$("#jQnewPF_id").val(_pf_id);
		$("#jQnewPF_dir").val(_pf_dir);
		if(_pf_lod){return false;}
		if(_pf_ign){return false;}
		
		if(!_pf_exp && !_pf_ftc && _pf_abl){
			var $newdiv=$("<div><b>Loading...</b></div>");
			$span.attr("data-loading","1");
			$span.append($newdiv);
			$.ajax({
				url:'ajax/file.move.php',
				type:"POST",
				data:{"fetch-child":_pf_id}
			}).done(function(result){
				if(result!=""){
					$span.attr("data-expanded","1");
					$span.attr("data-fetched","1");
					$span.removeAttr("data-loading");
					$span.removeClass("expand").addClass("collapse");
					$newdiv.html(result);
				}
			}).fail(function(a,b,c){messagesys.failure(b);});
		}else if(!_pf_exp && _pf_abl){
			$span.find(" > div").css("display","block");
			$span.attr("data-expanded","1");
			$span.removeClass("expand").addClass("collapse");
		}else if(_pf_exp && _pf_abl){
			$span.find(" > div").css("display","none");
			$span.attr("data-expanded","0");
			$span.removeClass("collapse").addClass("expand");
		}
	});
	
	$(popup.object()).on('submit',"#frmMovePageFile",function(e){
		e.preventDefault();
		serialize=$(this).serialize();
		var post=$.ajax({
			url:"ajax/file.move.do.php",
			type:"POST",
			data:serialize
		}).done(function(data){
			try{var json=JSON.parse(data);}catch(e){return false;}
			if(json.result){
				messagesys.success(json.message);
				popup.hide();
				if(~~json.line!=0){
					$("#jQPF_output").find("tr[data-pf_id="+json.line+"]").remove();
					PF_trace(json.directory);
				}else{
					PF_list(json.directory);
				}
			}else{
				messagesys.failure(json.message);
			}
		}).fail(function(){});
		return false;
	});
	
	
});