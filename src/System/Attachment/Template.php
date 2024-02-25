<?php

namespace System\Attachment;


class Template
{

	public function __construct()
	{
	}

	public static function itemDom($fileID, $fileMime, $fileTitle, $fileSelected = false, $domField = "")
	{
		return "
			<tr>
			<td class=\"checkbox\"><label><input name=\"{$domField}[]\" value=\"$fileID\" type=\"checkbox\"" . ($fileSelected ? "checked=\"checked\"" : "") . " /></label></td>
			<td class=\"op-remove\" data-id=\"$fileID\"><span></span></td>
			<td class=\"content\"><a class=\"js_upload_view\" target=\"_blank\" data-mime=\"$fileMime\" href=\"download/?id=$fileID&amp;pr=v\" data-href=\"download/?pr=v&amp;id=$fileID\">$fileTitle</a></td>
		</tr>";
	}

}