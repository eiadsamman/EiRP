(function($){
	$.popup = function(options) {
		var settings = jQuery.extend({
			loading:"Loading..."
		}, options);
		var plugin=this,
			$PP_container=$("<span />"),
			$PP_vertical=$("<span />"),
			$PP_content=$("<div />"),
			$PP_content2=$("<span />"),
			$PP_header=$("<h1 />"),
			$PP_footer=$("<h2 />"),
			$PP_zbody=$("body"),
			status=false,
			ajax=null;
		var output= {
			'plain':function(header,content,footer){
				status=true;
				$PP_content2.html(content);
				$PP_header.html(header);
				if(!!footer){$PP_footer.html(footer);}
				$PP_container.show();
				return output;
			},
			'open':function(url,data,type,ondone){
				status=true;
				$PP_content2.html("<div style=\"margin:10px;text-align:center\">Loading...</div>");
				$PP_container.show();
				ajax = $.ajax({
					url:url,
					data:data,
					type:type
				}).done(function(ajax_output_data){
					$jx_output=$(ajax_output_data);
					
					if(!!$jx_output.find("#__jx_title")){
						$PP_header.html($jx_output.find("#__jx_title").html());
					}
					if(!!$jx_output.find("#__jx_body")){
						$PP_content2.html($jx_output.find("#__jx_body").html());
					}
					if(!!$jx_output.find("#__jx_footer")){
						$PP_footer.html($jx_output.find("#__jx_footer").html());
					}
					if(typeof(ondone)=="function"){
						ondone.call(this,$PP_content);
					}
				}).fail(function(a,b,c){
					if(b=="abort"){
					}else{
						messagesys.failure(b);
					}
				});
				return output;
			},
			'hide':function(){
				status=false;
				$PP_content2.html("");
				$PP_header.html("");
				$PP_footer.html("");
				$PP_container.hide();
				if(!!ajax){
					ajax.abort();
				}
				return output;
			},
			'init':function(){
				var _this=this;
				status=false;
				$PP_container.addClass("jqpopup");
				$PP_container.append($PP_vertical);
				$PP_container.append($PP_content);
				
				$PP_content.append($PP_header);
				$PP_content.append($PP_content2);
				$PP_content.append($PP_footer);
				
				$("body").prepend($PP_container);
				$PP_container.on('click',function(e){
					if(e.target==$PP_container[0]){
						//_this.hide();
					}
				});
				$("body").on('keydown',function(e){
					if(e.which==27 && status){
						output.hide();
					}
				});
				$PP_content2.html("");
			},
			'object':function(){
				return $PP_content;
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






























































