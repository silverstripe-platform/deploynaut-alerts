<h2>$Project.Title</h2>

<% if $CurrentProject %>
	<ul class="nav nav-tabs">
		<% loop $CurrentProject.Menu %>
		<li<% if $IsActive %> class="active"<% end_if %>><a href="$Link">$Title</a></li>
		<% end_loop %>
	</ul>
	<ul class="nav level-2">
		<li><a href="naut/project/$CurrentProject.Name/approvealert">Alert approval form</a></li>
	</ul>
<% end_if %>

<p>You can use this form to approve an alert that has been configured with "ops" as a recipient.</p>

$AlertApprovalForm
