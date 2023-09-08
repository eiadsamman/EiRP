(function($){
	$.overlay = function(options) {
		var settings = jQuery.extend({
			message:"Loading...",
			className:"loading_overlay",
			backgroundObject:$("#body-content, #template-sidePanel")
		}, options);
		var container=$("<span />"),
			vertical=$("<span />"),
			content=$("<div />"),
			timer=null,
			hideTrigger=false;
		var output= {
			'show':function(){
				hideTrigger=false;
				timer = setTimeout(function() {
					if(!hideTrigger){
						settings.backgroundObject.addClass("blur");
						container.css("display","block");
					}
				}, 100);
				return output;
			},
			'hide':function(){
				hideTrigger=true;
				clearTimeout(timer);
				container.css("display","none");
				settings.backgroundObject.removeClass("blur");
				return output;
			},
			'init':function(){
				var _this=this;
				container.addClass(settings.className);
				container.append(vertical);
				container.append(content);
				content.html("\
					<span style=\"display:inline-block;vertical-align:middle\">"+settings.message+"</span>\
					<div class=\"css-progress-bar\"><span></span></div>\
				");
				$("body").prepend(container);
			}
		}
		output.init();
		return output;
	};
})(jQuery);
var overlay=null;
$(document).ready(function(e) {
	overlay=$.overlay({
		message:"Loading, please wait..."
	});
});