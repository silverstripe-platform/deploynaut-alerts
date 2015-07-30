<div class="content page-header">
	<ol class="breadcrumb">
		<li><a href="naut/project/$CurrentProject.Name">$CurrentProject.Title</a></li>
	</ol>

	<h1 class="page-heading">Alerts</h1>

	<ul class="nav nav-tabs">
		<li><a href="$CurrentProject.Link/alerts">Overview</a></li>
		<li class="active"><a href="$CurrentProject.Link/approvealert">Alert approval form</a></li>
	</ul>
</div>

<div class="content">
	<div class="text-center">
		<h2>Alerts Approval</h2>
		<p>
			You can use this form to request approval for an alert that has been configured with "ops" as a contact.
		</p>
	</div>

	<div class="col-md-offset-3 col-md-6 alerts-approval-form">
		$AlertApprovalForm
	</div>
</div>