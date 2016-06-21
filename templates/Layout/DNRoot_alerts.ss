<div class="content page-header">
	<div class="row">
		<div class="col-md-9">
			<% include Breadcrumb %>

			<ul class="nav nav-tabs">
				<li class="active"><a href="$CurrentProject.Link/alerts">Overview</a></li>
				<li><a href="$CurrentProject.Link/approvealert">Alert approval form</a></li>
			</ul>
		</div>
	</div>
</div>

<div class="content">

	<% if $AlertsConfigContent(master) %>
		<h3>Currently configured alerts on master branch</h3>
		<pre>$AlertsConfigContent(master)</pre>
	<% end_if %>

	<h3>Configuring alerts</h3>

	<p>
		Alerts are sent when a check fails with a non-200 HTTP response status. You configure these checks by placing a <code>.alerts.yml</code> file in the root of your site code.

	<p>
		Once the file is committed, you can deploy the code and the alerts will be configured at the end of the deployment.
	</p>

	<p>
		<a class="btn btn-primary" role="button" data-toggle="collapse" href="#alertExample" aria-expanded="false" aria-controls="alertExample">
			See an example
		</a>
	</p>


<pre class="collapse" id="alertExample">
alerts:
  dev-check:
    check_url: "dev/check"
    environment: "prod"
    contacts:
      - "joe@email.com"
  health-check:
    check_url: "dev/health"
    environment: "prod"
    contacts:
      - "joe@email.com"
      - "jane@email.com"
      - "ops"
</pre>


	<h3>Possible values to use in configuration</h3>

	<p>
		These <code>environment</code> values are available:
	</p>

	<ul>
		<% loop $CurrentProject.DNEnvironmentList %>
			<li>$Name</li>
		<% end_loop %>
	</ul>

	<p>
		These <code>contacts</code> are available:
	</p>

	<ul>
		<% loop $CurrentProject.AlertContacts %>
			<li>$Email</li>
		<% end_loop %>
		<li>ops</li>
	</ul>

	<p>
		Please raise a <a href="http://helpdesk.silverstripe.com" target="_blank">helpdesk</a> ticket and advise us which email addresses should receive notifications.
	</p>

	<h3>Using "ops" as a contact</h3>

	<p>
		This is a special case so that developers can alert SilverStripe Operations Team when a check fails. This means
		if the alert is received by ops, they will look into the problem when the alert is received. This should be used
		<strong>only</strong> for critical checks that concern the uptime of the site.
	</p>

	<p>
		In order to use the ops contact and have it be effective, the check needs to be approved by the SilverStripe
		Operations Team. If you commit the check and deploy it, the check will be created but be paused by default. In
		order to get it started, please submit a new request to the
		<a href="naut/project/$CurrentProject.Name/approvealert">alert approval form</a>.
	</p>

</div>
