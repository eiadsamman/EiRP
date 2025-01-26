<?php

namespace System\Lib\Upload;


class Template
{

	public static function itemDom($fileID, $fileMime, $fileTitle, $fileSelected = false, $domField = "")
	{
		return "
			<tr>
			<td class=\"checkbox\"><label><input name=\"{$domField}[]\" value=\"$fileID\" type=\"checkbox\"" . ($fileSelected ? "checked=\"checked\"" : "") . " /></label></td>
			<td class=\"op-remove\" data-id=\"$fileID\"><span></span></td>
			<td class=\"content\"><a class=\"js_upload_view\" target=\"_blank\" data-mime=\"$fileMime\" href=\"download/?id=$fileID&pr=v\" 
			>$fileTitle</a></td>
		</tr>";
	}

}