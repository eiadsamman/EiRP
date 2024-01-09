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


		var dom_placeholder = $("<div />"),
			_liststate = false;

		const container = $("<table class=\"bom-table hover\"><tbody></tbody></table>");
		let containerbody = null;
		const itemtemplate = $(`
			<tr>
				<td class="checkbox"><label><input type="checkbox" /></label></td>
				<td class="op-remove"><span></span></td>
				<td class="content">...</td>
			</tr>
		`);
		const itemloading = $(`
			<tr>
				<td class="progress" colspan="2">0%</td>
				<td class="content">...</td>
			</tr>
		`);
		const itemempty  = $(`
			<tr>
				<td colspan=\"3\">${settings.emptymessage}</td>
			</tr>
		`);

		var _AddListItem = function (id, name, size = false, checked = false, mime = "image") {
			const clone = itemtemplate.clone();
			clone.find(".checkbox input").prop("checked", checked).attr("name", settings.inputname + "[]").val(id);
			clone.find(".op-remove").attr("data-id", id);
			clone.find(".content").html("<a class=\"js_upload_view\" target=\"_blank\" data-mime=\"" + mime + "\" href=\"download/?id=" + id + "&pr=v\" data-href=\"download/?pr=v&id=" + id + "\">" + name + "</a>");
			containerbody.prepend(clone);
			updateCount();
		}

		var _newupload = function (file) {
			if (settings.objectHandler.find("input:checkbox:checked").length >= 1 && !settings.multiple) {
				alert("Multiple uploads aren't allowed");
				return;
			}

			var formData = new FormData();

			const clone = itemloading.clone();
			clone.find(".content").html(file.name + "&nbsp;&nbsp;[" + (file.size / 1024).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + "KB]");
			containerbody.prepend(clone);


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
								clone.find(".progress").html(percent + "%");
							} else {
								clone.find(".progress").html("0%");
							}
						}, false);
						myXhr.upload.addEventListener('load', function (event) {
						}, false);
						myXhr.upload.addEventListener('timeout', function (event) {
						}, false);
						myXhr.upload.addEventListener('abort', function (event) {
							up_operation.html("<span style=\"color:#f04;\">Aborted</span>");
						}, false);
					} else {
						up_operation.html("<span style=\"color:#f04;\">Error</span>");
					}
					return myXhr;
				},
				success: function (output) {
					var json = null;
					try {
						json = JSON.parse(output);
					} catch (e) {
						up_operation.html("<span style=\"color:#f04;\" title=\"Parsing output error\">Failed</span>");
						updateCount();
						return false;
					}
					if (json == null) {
						up_operation.html("<span style=\"color:#f04;\" title=\"Server refused to receive the file\">Failed</span>");
						updateCount();
						return false;
					}
					if (json.result == 0) {
						up_operation.html("<span style=\"color:#f04;\" title=\"Server refused to receive the file\">Failed</span>");
						updateCount();
						return false;
					}
					if (typeof (settings.onupload) == "function") {
						settings.onupload.call(this, { "id": json.id, "size": json.size, "name": json.name, "mime": json.mime })
					}
					clone.remove();
					_AddListItem(json.id, json.name, 0, true, json.mime);
					updateCount();
				},
				error: function (e, b, c) {
					if (b == "timeout") {
						up_operation.html("<span style=\"color:#f04;\">Timeout, uploading failed</span>");
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

		const updateCount = function () {
			let _count = containerbody.children("tr").length - 1;
			let _countsel = containerbody.find('input:checkbox:checked').length;
			settings.list_button.find("span").html(_countsel + " / " + _count);

			if (_count <= 0) {
				itemempty.show();
				toggleUploadList(false);
			} else {
				itemempty.hide();
			}
			if (_countsel >= 1 && !settings.multiple) {
				settings.dombutton.prop("disabled", true);
				containerbody.find('input:checkbox:not(:checked)').prop("disabled", true);
			} else {
				settings.dombutton.prop("disabled", false);
				containerbody.find('input:checkbox').prop("disabled", false);
			}
		}
		const setUploadListPosition = function () {
			return false;
			if (settings.align == "right") {
				dom_placeholder.css({
				});
			} else {
				dom_placeholder.css({
				});
			}
		}
		const setZIndex = function (raise = false) {
			return false;
			if (raise) {
				dom_placeholder.css("z-index", "9");
				settings.list_button.css("z-index", "10");
			} else {
				dom_placeholder.css("z-index", "8");
				settings.list_button.css("z-index", "8");
			}
		}
		const toggleUploadList = function (state) {
			if (state !== undefined) {
				if (state === true) {
					_liststate = true;
					setZIndex(true);
					setUploadListPosition();
					dom_placeholder.show();
				} else if (state === false) {
					_liststate = false;
					setZIndex(false);
					dom_placeholder.hide();
				}
			} else {
				if (_liststate == false) {
					_liststate = true;
					setZIndex(true);
					setUploadListPosition();
					dom_placeholder.show();
				} else {
					_liststate = false;
					setZIndex(false);
					dom_placeholder.hide();
				}
			}
		}

		var output = {
			'new': function (file) {
				_newupload(file);
				toggleUploadList(true);
			},
			'show': function () {
				toggleUploadList(true);
			},
			'clear': function () {
				containerbody.children("tr").remove();
				updateCount();
			},
			'update': function () {
				updateCount();
			},
			'clean': function () {
				containerbody.children("tr").each(function () {
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
					dom_placeholder = settings.domhandler;
					settings.objectHandler.append(dom_placeholder);
				} else {
					settings.objectHandler.append(dom_placeholder);
				}

				/* Check if container already added to DOM */
				if (dom_placeholder.find("table").length == 0)
					dom_placeholder.append(container);
				containerbody = dom_placeholder.find("tbody")


				containerbody.on('click', '.js_upload_view', function (e) {
					var mime = $(this).attr("data-mime");
					var viewsrc = $(this).attr("data-href");
					if (mime == "image") {
						e.preventDefault();
						popup.show("<div style=\"text-align:center\"><img style=\"max-width:600px;\" src=\"" + viewsrc + "\" /></div>");
						return false;
					}
				});
				containerbody.on('change', 'input:checkbox', function () {
					updateCount();
				});
				containerbody.on('click', '.op-remove', function () {
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
								rowobject.parent().remove();
								updateCount();
							}
						});
					} else if (settings.delete_method == "recycle") {
						rowobject.parent().remove();
						updateCount();
					}
				});
				settings.list_button.on("click", function () {
					toggleUploadList();
				});

				containerbody.append(itemempty);
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
						(dom_placeholder.is(e.target) || dom_placeholder.has(e.target).length !== 0)) {
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