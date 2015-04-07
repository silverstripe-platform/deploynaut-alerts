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
is sent to the configured recipient contacts.</p>

<p>To configure alerts, place a <code>alerts.yml</code> in the <code>_config</code> directory in the root of your site code.
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

You can use either <code>contacts</code> to specify individual recipients, or <code>contact-groups</code> for entire Deploynaut groups containing recipients.<br>
These groups are available to use:

<ul>
<% loop $AvailableGroups %>
	<li>$Code</li>
<% end_loop %>
</ul>

<p>Once the <code>alerts.yml</code> file is in place, and you deploy the change to an environment, the alerts will be configured according to those settings.</p>

<p>Any changes to the <code>alerts.yml</code> file on subsequent deploys will update the effective alerts.
The only exception is any configured alerts using "ops" as a <code>contact-groups</code> recipient will need approval by SilverStripe Operations before the notification can become effective. You will need to submit a new request to the <a href="naut/project/$CurrentProject.Name/approvealert">alert approval form</a> after your alert has been committed.</p>

<p>To remove a notification, delete the entry from your <code>alerts.yml</code> and re-deploy.</p>
