<h2>$Project.Title</h2>

<% if $CurrentProject %>
	<ul class="nav nav-tabs">
		<% loop $CurrentProject.Menu %>
		<li<% if $IsActive %> class="active"<% end_if %>><a href="$Link">$Title</a></li>
		<% end_loop %>
	</ul>
<% end_if %>

<h3>Current alerts configured</h3>

<% if $HasAlertsConfigured %>
<ul>
	<li>Not done yet</li>
</ul>
<% else %>
	<p>No alerts configured.</p>
<% end_if %>

<h3>Configuring alerts</h3>

<p>This section allows you to configure which alerts should be configured, so that alerts are sent when a check fails.
A check is a URL that is monitored every few minutes for a bad HTTP status in the 5xx range. If the check fails, a notification
is sent to one or more people in a group in deploynaut.</p>

<p>There are two types of alerts:</p>

<ul>
	<li>Checks created by the developer of the site, alerts go to the website team and any clients</li>
	<li>Checks that concern the operations of the site that are important for website uptime, alerts and monitoring of the check go to the SilverStripe Operations team</li>
</ul>

<p>To configure alerts, place a <code>alerts.yml</code> in the <code>_config</code> directory in the root of your site.</p>

<p>Here is an example:</p>

```
```

<p>These group codes are available to use in your <code>alerts.yml</code>:</p>

<ul>
<% loop $AvailableGroups %>
	<li>$Code</li>
<% end_loop %>
</ul>

<p>Once in place, and you deploy the change to the environment, alerts will be configured according to these settings.</p>

<p>Any changes to the `alerts.yml` file on subsequent deploys will update the effective alerts.
The only exception to that is if any alerts that concern the operation of the site using “ops” as a group notification, then SilverStripe Operations will need to review the change before the notification can become effective. You will need to submit a new request to the [notification approval form]().</p>

<p>To remove a notification, delete the entry from your <code>alerts.yml</code> and re-deploy.</p>
