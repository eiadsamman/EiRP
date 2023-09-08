(function($){
	$.msgsys = function(options) {
		var settings = jQuery.extend({
			timeout:3000,
			fadeuration:150,
			timeoutperletter:70,
			onshow:function(){},
			onhide:function(){}
			
		}, options);
		var plugin=this,
			container=$("<b />"),
			messagetype=$("<div />"),
			message=$("<div />"),
			icon=$("<span />"),
			zbody=$("body"),
			status='idle',
			timeouthandler=null,
			timeouttime=0;
		
		var show=function(type,value){
			timeouttime=value.length*settings.timeoutperletter;
			timeouttime=timeouttime<1000?1000:timeouttime;
			status='up';
			messagetype.removeClass("success failure").addClass(type);
			message.html(value);
			
			if(typeof(settings.onshow)=="function"){
				settings.onshow.call(this);
			}
			container.css({'display':'block'}).animate({
				'opacity':1
			},settings.fadeuration,'easeInQuint',function(){
				status='show';
				timeouthandler=setTimeout(function(){hide();},timeouttime);
			});
		}
		var hide=function(){
			clearTimeout(timeouthandler);
			if(typeof(settings.onhide)=="function"){
				settings.onhide.call(this);
			}
			container.stop().animate({
				'opacity':0
			},settings.fadeuration,'easeOutQuint',function(){
				status='idle';
				$(this).css({'display':'none'});
			});
		}
		var hideshow=function(type,value){
			clearTimeout(timeouthandler);
			container.stop().animate({
				'opacity':0
			},settings.fadeuration,'easeOutQuint',function(){
				show(type,value);
			});
			return;
		}
		var output= {
			'success':function(value){
				if(status=='show' || status=='up'){
					hideshow("success",value);
					return output;
				}
				show("success",value);
				
				return output;
			},
			'failure':function(value){
				if(status=='show' || status=='up'){
					hideshow("failure",value);
					return output;
				}
				show("failure",value);
				
				return output;
			},
			'init':function(){
				container.addClass("messagesys");
				messagetype.append(icon);
				messagetype.append(message);
				container.append(messagetype);
				container.css({'opacity':0,'display':'none'})
				messagetype.on('click',function(){
					hide();
				});
				$("body").prepend(container);
				
			}
		}
		output.init();
		return output;
	};
})(jQuery);
var messagesys=null;
$(document).ready(function(e) {
	messagesys=$.msgsys({
		onshow:function(){
			$(".header-ribbon").css({'opacity':'1'});
		},
		onhide:function(){
			$(".header-ribbon").css({'opacity':'1'});
		}
	});
});