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

<% if $HasAlertsConfigured %>
<h4>Currently configured alerts</h4>
$AlertsConfigContent
<% end_if %>

<h4>Instructions for configuring alerts</h4>

<p>Alerts are sent when a check against an <a href="https://github.com/silverstripe-labs/silverstripe-environmentcheck/">environmentcheck module suite</a> fails with a bad HTTP response status.
You configure these checks by placing a YAML file <code>alerts.yml</code> in the <code>_config</code> directory of your site code. Here's an example of what it looks like:</p>

<pre>
alerts:
  dev-check:
    envcheck-suite: "check"
    environment: "prod"
    contact-groups:
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
<p>These <code>contacts</code> are available to be used for this project:</p>

<ul>
<% loop $CurrentProject.AlertContacts %>
	<li>$Email</li>
<% end_loop %>
	<li>ops</li>
</ul>

<p>Any changes to the <code>alerts.yml</code> file on subsequent deploys will update the effective alerts.
The only exception is any configured alerts using "ops" as a <code>contacts</code> recipient will need approval by SilverStripe Operations before the alert is effective.
You will need to submit a new request to the <a href="naut/project/$CurrentProject.Name/approvealert">alert approval form</a> after your alert has been committed.</p>

<p>To remove a notification, delete the entry from your <code>alerts.yml</code> and re-deploy.</p>
