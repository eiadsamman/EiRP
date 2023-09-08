(function($){
	$.popup = function(options) {
		var settings = jQuery.extend({
			onshow:function(){},
			onhide:function(){},
			onboundaryclick:function(fn){
				fn.hide();
			},
			loading:"Loading..."
		}, options);
		var plugin=this,
			container=$("<span />"),
			vertical=$("<span />"),
			content=$("<div />"),
			zbody=$("body"),
			status=null;
		var hide=function(){
			if(typeof(settings.onhide)=="function"){
				settings.onhide.call(this);
			}
		}
		
		var output= {
			'show':function(data){
				content.html(data);
				container.show();
				return output;
			},
			'hide':function(){
				content.empty();
				container.hide();
				return output;
			},
			'self':function(){
				return content;
			},
			'onboundaryclick':function(fn){
				settings.onouterareaclick=fn;
			},
			'init':function(){
				var _this=this;
				container.addClass("jqpopup");
				container.append(vertical);
				container.append(content);
				
				$("body").prepend(container);
				if(1==1){/*Disable close on clicking out of window*/
					container.on('click',function(e){
						if(e.target==container[0]){
							settings.onboundaryclick.call(this,_this);
						}
					});
				}
				content.html("");
			}
		}
		output.init();
		return output;
	};
})(jQuery);
var popup=null;
$(document).ready(function(e) {
	popup=$.popup();
});
