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

<h3>Alerts</h3>

<% if $AlertsConfigContent %>
<h4>Currently configured alerts</h4>
<pre>$AlertsConfigContent</pre>
<% end_if %>

<h4>Configuring alerts</h4>

<p>Alerts are sent when a check against an <a href="https://github.com/silverstripe-labs/silverstripe-environmentcheck/">environmentcheck module suite</a> fails with a bad HTTP response status.
You configure these checks by placing a <code>.alerts.yml</code> file in the root of your site code. Here's an example of what it looks like:</p>

<pre>
alerts:
  dev-check:
    envcheck-suite: "check"
    environment: "prod"
    contacts:
      - "joe@email.com"
  health-check:
    envcheck-suite: "health"
    environment: "prod"
    contacts:
      - "joe@email.com"
      - "jane@email.com"
      - "ops"
</pre>

<p>Once the file is committed, you can deploy the code and the alerts will be configured at the end of the deployment.</p>

<h4>Possible values to use in configuration</h4>

<p>These <code>contacts</code> are available:</p>

<ul>
<% loop $CurrentProject.AlertContacts %>
	<li>$Email</li>
<% end_loop %>
	<li>ops</li>
</ul>

<p>Please contact the <a href="http://helpdesk.silverstripe.com">SilverStripe Operations Team</a> if you would like to add another contact to the list above.</p>

<p>These <code>environment</code> values are available:</p>

<ul>
<% loop $CurrentProject.DNEnvironmentList %>
	<li>$Name</li>
<% end_loop %>
</ul>

<h4>Using "ops" as a contact</h4>

<p>This is a special case so that developers can alert SilverStripe Operations Team when a check fails. This means if the alert is received by ops, they will look into the problem when the alert is received. This should be used only for critical checks that concern the uptime of the site.</p>

<p>In order to use the ops contact and have it be effective, the check needs to be approved by the SilverStripe Operations Team. If you commit the check and deploy it, the check will be created but
be paused by default. In order to get it started, please submit a new request to the <a href="naut/project/$CurrentProject.Name/approvealert">alert approval form</a>.</p>
