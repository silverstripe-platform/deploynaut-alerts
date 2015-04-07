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

<p>Alerts are check against configured endpoints of a site that are periodically checked by an external service to ensure
a healthy response of 200. If a check fails by returning a bad HTTP status in the 5xx range, then an alert will be sent to configured recipients.</p>

<p>Alerts are configured by placing a YAML file <code>alerts.yml</code> in the <code>_config</code> directory of your site code.
Here's an example of what it looks like:</p>

<pre>
alerts:
  dev-check:
    url: "http://mysite.com/dev/check"
    environment: "prod"
    contact-groups:
      - "a-group-in-silverstripe"
  homepage-check:
    url: "http://mysite.com"
    environment: "prod"
    contact-groups:
      - "a-group-in-silverstripe"
      - "ops"
  alternate-homepage-check:
    url: "http://mysite.com"
    environment: "prod"
    contacts:
      joe:
        name: "Joe Bloggs"
        email: "joe@mysite.com"
        sms: "+64123456789"
      jane:
        name: "Jane Bloggs"
        email: "jane@mysite.com"
        sms: "+64123456789"
</pre>

<p>Once the file is in place, you can deploy the code and the alerts will be configured at the end of a deployment.</p>

<p>You can use either <code>contacts</code> to specify individual recipients, or <code>contact-groups</code> for entire Deploynaut groups containing recipients.<br>
These groups are available to use on this project:</p>

<ul>
<% loop $AvailableGroups %>
	<li>$Code</li>
<% end_loop %>
</ul>

<p>Any changes to the <code>alerts.yml</code> file on subsequent deploys will update the effective alerts.
The only exception is any configured alerts using "ops" as a <code>contact-groups</code> recipient will need approval by SilverStripe Operations before the alert is effective. You will need to submit a new request to the <a href="naut/project/$CurrentProject.Name/approvealert">alert approval form</a> after your alert has been committed.</p>

<p>To remove a notification, delete the entry from your <code>alerts.yml</code> and re-deploy.</p>
