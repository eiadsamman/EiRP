(function ($) {
	$.Upload = function (options) {
		var settings = jQuery.extend({
			upload_url: null,
			objectHandler: null,
			domselector: null,
			dombutton: null,
			list_button: null,
			emptymessage: null,
			relatedpagefile: 0,
			multiple: false,
			inputname: "uploads",
			onupload: function () { },
			ondelete: function () { },
			delete_method: "permanent", /*recycle|permanent*/
			listfiles: {},
			domhandler: null,
			align: "left"

		}, options);


		var _objectHandler = $("<div />"),
			_emptymessage = $("<b />").html(settings.emptymessage),
			_liststate = false;

		var _AddListItem = function (id, name, size = false, checked = false, mime = "image") {
			var $up_record = $("<span />");
			var $up_state = $("<span class=\"upload_record_pointer\" />");
			var $up_operation = $("<span class=\"btn-set\" />");
			var $up_filedet = $("<span class=\"upload_file_details\" />");
			$up_state.append($up_operation);
			$up_record.append($up_state);
			$up_record.append($up_filedet);
			$up_operation.html("<label class=\"btn-checkbox\"><input type=\"checkbox\" " + (checked ? " checked=\"checked\" " : "") + " name=\"" + settings.inputname + "[]\" value=\"" + id + "\" /><span></span></label>" +
				"<button type=\"button\" data-id=\"" + id + "\" class=\"js_up_delete bnt-remove\"></button>");
			$up_filedet.html("<a class=\"js_upload_view\" target=\"_blank\" data-mime=\"" + mime + "\" href=\"download/?id=" + id + "&pr=v\" data-href=\"download/?pr=v&id=" + id + "\">" + name + "</a>");
			_objectHandler.append($up_record);

			updateCount();
		}

		var _newupload = function (file) {
			if (settings.objectHandler.find("input:checkbox:checked").length >= 1 && !settings.multiple) {
				alert("Multiple uploads aren't allowed");
				return;
			}

			var formData = new FormData();
			var $up_record = $("<span />");
			var $up_state = $("<span class=\"upload_record_pointer\" />");
			var $up_operation = $("<span class=\"btn-set\" />");
			var $up_filedet = $("<span class=\"upload_file_details\" />");

			$up_state.append($up_operation);
			$up_record.append($up_state);
			$up_record.append($up_filedet);
			$up_operation.html("<span>0%</span>");
			$up_filedet.html("<b>" + file.name + "</b>&nbsp;&nbsp;&nbsp;[" + (file.size / 1024).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + "KB]");

			_objectHandler.append($up_record);

			formData.append("file", file, file.name);
			formData.append("upload_file", true);
			formData.append("pagefile", settings.relatedpagefile);

			updateCount();
			$.ajax({
				type: "POST",
				url: settings.upload_url + "/?up",
				xhr: function () {
					var myXhr = $.ajaxSettings.xhr();
					if (myXhr.upload) {
						myXhr.upload.addEventListener('progress', function (event) {
							var percent = 0,
								position = event.loaded || event.position,
								total = event.total;
							if (event.lengthComputable) {
								percent = Math.ceil(position / total * 100);
								$up_operation.html("<span style=\"background:linear-gradient(90deg,#ffffff " + percent + "%,#dddddd 0%);\">" + percent + "%</span>");
							} else {
								$up_operation.html("<span style=\"background:linear-gradient(90deg,#ffffff 0%,#dddddd 0%);\">0?</span>");
							}
						}, false);
						myXhr.upload.addEventListener('load', function (event) {
						}, false);
						myXhr.upload.addEventListener('timeout', function (event) {
						}, false);
						myXhr.upload.addEventListener('abort', function (event) {
							$up_operation.html("<span style=\"color:#f04;\">Aborted</span>");
						}, false);
					} else {
						$up_operation.html("<span style=\"color:#f04;\">Error</span>");
					}
					return myXhr;
				},
				success: function (output) {
					var json = null;
					/* console.log(output); */
					try {
						json = JSON.parse(output);
					} catch (e) {
						/* console.log(e); */
						$up_operation.html("<span style=\"color:#f04;\" title=\"Parsing output error\">Failed</span>");
						updateCount();
						return false;
					}
					if (json == null) {
						/* console.log("Empty Response"); */
						$up_operation.html("<span style=\"color:#f04;\" title=\"Server refused to receive the file\">Failed</span>");
						updateCount();
						return false;
					}
					if (json.result == 0) {
						/* console.log("Server Response Error \n" + output); */
						$up_operation.html("<span style=\"color:#f04;\" title=\"Server refused to receive the file\">Failed</span>");
						updateCount();
						return false;
					}
					if (typeof (settings.onupload) == "function") {
						settings.onupload.call(this, { "id": json.id, "size": json.size, "name": json.name, "mime": json.mime })
					}
					$up_operation.html("<label class=\"btn-checkbox\"><input type=\"checkbox\" checked=\"checked\" name=\"" + settings.inputname + "[]\" value=\"" + json.id + "\" /><span></span><div></div></label>" +
						"<button type=\"button\" data-id=\"" + json.id + "\" class=\"js_up_delete bnt-remove\"></button>");
					$up_filedet.html("<a class=\"js_upload_view\" target=\"_blank\" data-mime=\"" + json.mime + "\" href=\"download/?id=" + json.id + "&pr=v\" data-href=\"download/?pr=v&id=" + json.id + "\">" + json.name + "</a>");
					updateCount();
				},
				error: function (e, b, c) {
					if (b == "timeout") {
						$up_operation.html("<span style=\"color:#f04;\">Timeout, uploading failed</span>");
					} else {
						up_operation.html("<span style=\"color:#f04;\">" + c + "</span>");
					}
				},
				async: true,
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				timeout: 60000
			});
			return this;
		}

		var updateCount = function () {
			var _count = settings.objectHandler.children().children("span").length;
			var _countsel = settings.objectHandler.find('input:checkbox:checked').length;
			settings.list_button.find("span").html(_countsel + " / " + _count);
			if (_count <= 0) {
				_emptymessage.show();
				toggleUploadList(false);
			} else {
				_emptymessage.hide();
			}
			if (_countsel >= 1 && !settings.multiple) {
				settings.dombutton.prop("disabled", true);
				settings.objectHandler.find('input:checkbox:not(:checked)').prop("disabled", true);
			} else {
				settings.dombutton.prop("disabled", false);
				settings.objectHandler.find('input:checkbox').prop("disabled", false);
			}
		}
		var setUploadListPosition = function () {
			return false;
			if (settings.align == "right") {
				_objectHandler.css({
				});
			} else {
				_objectHandler.css({
				});
			}
		}
		var setZIndex = function (raise = false) {
			return false;
			if (raise) {
				_objectHandler.css("z-index", "9");
				settings.list_button.css("z-index", "10");
			} else {
				_objectHandler.css("z-index", "8");
				settings.list_button.css("z-index", "8");
			}
		}

		var toggleUploadList = function (state) {
			if (state !== undefined) {
				if (state === true) {
					_liststate = true;
					setZIndex(true);
					setUploadListPosition();
					_objectHandler.show();
				} else if (state === false) {
					_liststate = false;
					setZIndex(false);
					_objectHandler.hide();
				}
			} else {
				if (_liststate == false) {
					_liststate = true;
					setZIndex(true);
					setUploadListPosition();
					_objectHandler.show();
				} else {
					_liststate = false;
					setZIndex(false);
					_objectHandler.hide();
				}
			}
		}

		var output = {
			'new': function (file) {
				var _this = this;
				var newupload = _newupload(file);
				toggleUploadList(true);
			},
			'show': function () {
				toggleUploadList(true);
			},
			'clear': function () {
				settings.objectHandler.children().children("span").remove();
				updateCount();
			},
			'update': function () {
				updateCount();
			},
			'clean': function () {
				settings.objectHandler.children().children("span").each(function () {
					var _this = $(this);
					if ($("input[type=checkbox]", _this).prop("checked") == true) {
						_this.remove();
					}
				});
				updateCount();
			},
			'AddListItem': function (id, name, size = false, checked = false, mime = "image") {
				_AddListItem(id, name, size, checked, mime);
			},
			'init': function () {
				var _this = this;
				if (settings.domhandler != null) {
					_objectHandler = settings.domhandler;
					settings.objectHandler.append(_objectHandler);
				} else {
					settings.objectHandler.append(_objectHandler);
				}
				settings.objectHandler.on('click', '.js_upload_view', function (e) {
					var mime = $(this).attr("data-mime");
					var viewsrc = $(this).attr("data-href");
					if (mime == "image") {
						e.preventDefault();
						popup.show("<div style=\"text-align:center\"><img style=\"max-width:600px;\" src=\"" + viewsrc + "\" /></div>");
						return false;
					}
				});
				settings.objectHandler.on('change', 'input:checkbox', function () {
					updateCount();
				});
				settings.objectHandler.on('click', '.js_up_delete', function () {

					var rowobject = $(this);
					var up_id = $(this).attr("data-id");
					if (settings.delete_method == "permanent") {
						if (!confirm('Are you sure you want to permanently delete this file?')) {
							return false;
						}
						$ajax = $.ajax({
							data: {
								'method': 'remove_attachment',
								'id': ~~up_id,
							},
							url: settings.upload_url,
							type: "POST"
						}).done(function (data) {
							if (data == "1") {
								rowobject.parent().parent().parent().remove();
								updateCount();
							}
						});
					} else if (settings.delete_method == "recycle") {
						rowobject.parent().parent().parent().remove();
						updateCount();
					}
				});
				settings.list_button.on("click", function () {
					toggleUploadList();
				});
				_objectHandler.append(_emptymessage);
				settings.dombutton.on('click', function () {
					settings.domselector.trigger("click");
				});
				settings.domselector.on("change", function (e) {
					var filelist = e.target.files;
					for (i = 0; i < filelist.length; i++) {
						_this.new(filelist[i]);
					}
					toggleUploadList(true);
				});
				$(document).mouseup(function (e) {
					if ((settings.list_button.is(e.target) || settings.list_button.has(e.target).length !== 0) ||
						(_objectHandler.is(e.target) || _objectHandler.has(e.target).length !== 0)) {
					} else {
						toggleUploadList(false);
					}
				});
			}
		}
		output.init();
		return output;
	};
})(jQuery);