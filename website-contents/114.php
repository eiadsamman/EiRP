<?php

use System\Finance\Asset;
use System\Finance\Liability;
use \System\Finance\Term;

echo "<br />";


//$check = ($t instanceof Liability);



?>
</pre>
<script type="speculationrules">
  {
	"prerender": [
	  {
		"where": {
		  "and": [
			{ "href_matches": "/*" },
			{ "not": { "href_matches": "/logout" } },
			{ "not": { "href_matches": "/*\\?*(^|&)add-to-cart=*" } },
			{ "not": { "selector_matches": ".no-prerender" } },
			{ "not": { "selector_matches": "[rel~=nofollow]" } }
		  ]
		}
	  }
	],
	"prefetch": [
	  {
		"urls": ["index"],
		"requires": ["anonymous-client-ip-when-cross-origin"],
		"referrer_policy": "no-referrer"
	  }
	]
  }
</script>

<?php
exit;
//use System\IO\RecordManager;
/* 
$t = new System\IO\RecordManager\Text();
var_export($t->getInputType()); */