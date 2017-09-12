<div class="content page-header">
	<div class="row">
		<div class="col-md-9">
			<% include Breadcrumb %>
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
    environment: "production"
    contacts:
      - "joe@email.com"
  health-check:
    check_url: "dev/health"
    environment: "uat"
    contacts:
      - "joe@email.com"
      - "jane@email.com"
</pre>


	<h3>Possible values to use in configuration</h3>

	<p>
		These <code>environment</code> values are available:
	</p>

	<ul>
		<% loop $CurrentProject.DNEnvironmentList %>
			<li>$Code</li>
		<% end_loop %>
	</ul>

	<p>
		These <code>contacts</code> are available:
	</p>

	<ul>
		<% loop $CurrentProject.AlertContacts %>
			<li>$Email</li>
		<% end_loop %>
	</ul>

	<p>
		Please raise a <a href="http://helpdesk.silverstripe.com">helpdesk</a> ticket and advise us which email addresses should receive notifications.
	</p>

</div>
