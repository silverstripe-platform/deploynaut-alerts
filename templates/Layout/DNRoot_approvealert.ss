<div class="content page-header">
	<div class="row">
		<div class="col-md-9">
			<% include Breadcrumb %>

			<ul class="nav nav-tabs">
				<li><a href="$CurrentProject.Link/alerts">Overview</a></li>
				<li class="active"><a href="$CurrentProject.Link/approvealert">Alert approval form</a></li>
			</ul>
		</div>
	</div>
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