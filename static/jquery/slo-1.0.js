(function($){
	/*NEXT: mouse hover and navigation*/
	$.fn.slo = function(options) {
		var slosettings = jQuery.extend({
			thumbsNumber:10,
			onselect:function(){},
			onblur:function(){},
			ondeselect:function(){},
			limit:5
		}, options);
		var $jq=this;

		var output= {
			'clear':function(){
				$jq.each(function(){
					var _this=this;
					_this.$output.css("display","none");
					_this._status='idle';
					_this.$slo_input.val("");
					_this.$slo_owner.val("");
					_this.$slo_owner.parent().removeClass("valid").addClass("unvalid");
					if(typeof(slosettings.ondeselect)=="function"){
						slosettings.ondeselect.call(this,{object:_this.$slo_owner,});
					}
				});
			},
			'set':function(value,id){
				$jq.each(function(){
					var _this=this;
					_this.$output.css("display","none");
					_this._status='idle';
					_this.$slo_input.val(value);
					_this.$slo_owner.val(id);
					_this.$slo_owner.parent().removeClass("unvalid").addClass("valid");
				});
			},
			'init':function(){
				$jq.each(function(){
					var _this=this;
					this.$slo_owner=$(this);
					
					this._role			= this.$slo_owner.attr('data-slo');
					this._limit			= slosettings.limit;
					this._default		= this.$slo_owner.attr('data-slodefaultid');
					this.$ajax			= $.ajax();
					this.$wrapper		= $("<span />");
					this.$output		= $("<div />");
					this.$slo_input		= $("<input type=\"hidden\" />");
					this._prev			= null;
					this._status		= null;
					this.stamped		= false,
					this.focusfix		= false;
					
						
					var slohide = function (){
						_this.$output.css("display","none");
						_this.$slo_owner.removeClass("listvisible");
					}
					var sloshow = function (){
						_this.$output.css("display","block");
						_this.$slo_owner.addClass("listvisible");
					}
					var slofocus = function(displaylist){
						if(displaylist != undefined && displaylist!=true){return false;}
						_this.$ajax.abort();
						_this.$ajax=$.ajax({
							type:'POST',
							url:'slo',
							data:{'role':_this._role,'query':_this.$slo_owner.val(),'limit':_this._limit}
						}).done(function(data){
							if(data!=""){
								_this._status='up';
								_this.$output.html(data);
								_this.$output.find(">div:first-child").addClass("active");
								sloshow();
							}else{
								_this._status='idle';
								slohide();
							}
						}).fail(function(a,b,c){
							_this._status='idle';
						});
					}
					
					this.$slo_input.attr("name",_this.$slo_owner.attr("name")+"[1]");
					this.$slo_owner.attr("name",_this.$slo_owner.attr("name")+"[0]");
					this.$slo_owner.attr("autocomplete","off");
					this.$slo_input.val(_this._default);
					
					this.$wrapper.addClass("cssSLO_wrap");
					this.$wrapper.css({
						'display':_this.$slo_owner.css('display'),
						'width':_this.$slo_owner[0].style.width,
						'max-width':_this.$slo_owner[0].style.maxWidth,
						'min-width':_this.$slo_owner[0].style.minWidth,
					});
					
					this.$output.addClass("cssSLO_output");
					this.$output.css({
						'top':'100%'
					});
					this.$output.html();
					
					this.$slo_owner.wrap(this.$wrapper);
					this.$slo_owner.parent().append(this.$output);
					this.$slo_owner.parent().append(this.$slo_input);
					
					if(this._default!=undefined && this._default!=""){
						_this.$slo_owner.parent().removeClass("unvalid").addClass("valid");
						_this.stamped=true;
					}
					this._status='idle';
					
					this.$slo_owner.on('input propertychange paste',function(e){
						if(_this._prev==$(this).val()){
							_this._status='idle';
							return;
						}else{
							if(_this.stamped==true){
								_this._status='idle';
								_this.$slo_input.val("");
								_this.$slo_owner.parent().removeClass("valid").addClass("unvalid");
								if(typeof(slosettings.ondeselect)=="function"){
									slosettings.ondeselect.call(this,{object:_this.$slo_owner});
								}
							}
							_this.stamped=false;
						}
						_this.$ajax.abort();
						_this.$ajax=$.ajax({
							type:'POST',
							url:'slo',
							data:{'role':_this._role,'query':_this.$slo_owner.val(),'limit':_this._limit}
						}).done(function(data){
							if(data!=""){
								_this._status='up';
								_this.$output.html(data);
								_this.$output.find(">div:first-child").addClass("active");
								sloshow();
							}else{
								_this._status='idle';
								slohide();
							}
						}).fail(function(a,b,c){
							_this._status='idle';
						});
					}).on('focus',function(e){
						if($(this).is(":focus")){
							return false;
						}else{
							if(_this.focusfix==true){_this.focusfix=false;return false;}
							slofocus();
						}
					}).on('keydown',function(e){
						var keycode = (e.keyCode ? e.keyCode : e.which);
						if(keycode==27 && _this._status=='idle'){
							_this.$slo_input.val("");
							_this.$slo_owner.val("");
							_this.stamped=false;
							_this.$slo_owner.parent().removeClass("valid").addClass("unvalid");
							if(typeof(slosettings.ondeselect)=="function"){
								slosettings.ondeselect.call(this,{object:_this.$slo_owner,});
							}
							return;
						}
						if(keycode==27 || keycode==9){
							_this._status='idle';
							slohide();
							return;
						}
						if(keycode==13 && _this._status=="up"){
							e.preventDefault();
							var $current=_this.$output.find(">div.active");
							$current.trigger("click");
							_this.focusfix=false;
							_this.$slo_owner.select();
							return false;
						}
						if(keycode==40 && _this._status=="up"){
							e.preventDefault();
							var $current=_this.$output.find(">div.active");
							if($current.next().length>0){
								$current.next().addClass("active");
								$current.removeClass("active");
							}else{
								//_this.$output.find(">div:first-child").addClass("active");
							}
							
							return false;
						}else if(keycode==40 && _this._status=="idle"){
							_this.$slo_owner.trigger("click");
						}
						
						if(keycode==38 && _this._status=="up"){
							e.preventDefault();
							var $current=_this.$output.find(">div.active");
							if($current.prev().length>0){
								$current.prev().addClass("active");
								$current.removeClass("active");
							}else{
								//_this.$output.find(">div:last-child").addClass("active");
							}
							return false;
						}else if(keycode==38 && _this._status=="idle"){
							_this.$slo_owner.trigger("click");
						}
					}).on('click',function(e){
						if($(this).is(":focus")){
							_this.focusfix==true;
							slofocus(true);
						}else{
							return false;
						}
					});
					
					
					this.$output.on('click'," > div",function(){
						var $clicked=$(this);
						_this.$slo_owner.parent().addClass("valid").removeClass("unvalid");
						_this.$slo_owner.val($clicked.find("p.cssSLO_rvl").html());
						_this.$slo_input.val($clicked.find("p.cssSLO_rid").html());
						_this._prev=_this.$slo_owner.val();
						_this._status="idle";
						_this.stamped=true;
						if(typeof(slosettings.onselect)=="function"){
							var pass={
								object:_this.$slo_owner,
								value:_this.$slo_owner.val(),
								hidden:_this.$slo_input.val(),
								text:$clicked.find("span").html()
							};
							slosettings.onselect.call(this,pass);
						}
						slohide();
						_this.focusfix=true;
						slofocus(false);
						_this.$slo_owner.select();
					});
					$(document).mousedown(function (e){
						var container = _this.$slo_owner.parent();
						if (!container.is(e.target) && container.has(e.target).length === 0){
							slohide();
						}
					});
				});
			}
		}
		output.init();
		return output;
	};
})(jQuery);