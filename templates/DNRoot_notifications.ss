<h2>$Project.Title</h2>

<p>This section allows you to configure which notifications should be configured, so that alerts are sent when a check fails.
A check is a URL that is monitored every few minutes for a bad HTTP status in the 5xx range. If the check fails, a notification
is sent to one or more people in a group in deploynaut.</p>

<p>There are two types of notifications:</p>

<ul>
	<li>Checks created by the developer of the site, notifications go to the website team and any clients</li>
	<li>Checks that concern the operations of the site that are important for website uptime, notifications and monitoring of the check go to the SilverStripe Operations team</li>
</ul>

<p>To configure notifications, place a <code>notifications.yml</code> in the <code>_config</code> directory in the root of your site.</p>

<p>Here is an example:</p>

```
INSERT YAML EXAMPLE HERE
```

<p>Once in place, and you deploy the change to the environment, notifications will be configured according to these settings.</p>

<p>Any changes to the `notifications.yml` file on subsequent deploys will update the effective notifications.
The only exception to that is if any notifications that concern the operation of the site using “ops” as a group notification, then SilverStripe Operations will need to review the change before the notification can become effective. You will need to submit a new request to the [notification approval form]().</p>

<p>Current effective notifications:</p>

TODO
<ul>
	<li>http://www.silverstripe.org/dev/check/health (groups: ssorg-prod-developers, ops)</li>
	<li>http://www.silverstripe.org/dev/check/anothersuite (groups: ssorg-prod-developers)</li>
</ul>

<p>To remove a notification, delete the entry from your <code>notifications.yml</code> and re-deploy.</p>
