<h1>Attendance check-out documentation:</h1>

<h2>Introduction</h2>
<p>
	This page descripes employees attendance check-out operations<br />
	Keys required for this operations:<br />
<ul>
	<li>Permissions for related tasks, SLO objects and Downloading access</li>
	<li>A valid company with access permissions</li>
	<li>A company selected for current operations</li>
	<li>Employees list within the company selected</li>
</ul>
</p>
<h2>Procedures</h2>
<h3>Company settings</h3>
<p>System administrator should create and maintance companies running on this system following <a href="<?php echo $app->http_root . $fs(147)->dir; ?>">Companies page</a></p>

<h3>Linking required company to your account</h3>
<p>System administrator should link companies to your account through <a href="<?php echo $app->http_root . $fs(5)->dir; ?>">Users page</a></p>

<h2>Troubleshooting</h2>
<h3>The page is not loading:</h3>
<p>Permissions denided, a required system setting is invalid or missing</p>

<h3>Employee field is empty:</h3>
<p>
<ul>
	<li>Permissions denided for SLO objects</li>
	<li>No company linked or selected to your account</li>
	<li>Selected campany holds no employee</li>
</ul>
</p>

<h3>No photos loading for checked employee:</h3>
<p>
<ul>
	<li>Permissions denided for downloading images</li>
	<li>No personal photo link to the employee</li>
</ul>
</p>

<i>2021-04-07T13:30GMT</i>