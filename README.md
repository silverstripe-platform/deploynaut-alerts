# Deploynaut Alerts

## Environment configuration

The following sets up to whom a request for 24/7 support for checks should be sent to.

	define('DEPLOYNAUT_OPS_EMAIL', 'ops@silverstripe.com');
	define('DEPLOYNAUT_OPS_EMAIL_FROM', 'deploy@silverstripe.com');

The following is used by the pingdom api to create notification contacts and checks

	define('PINGDOM_USERNAME', 'user@silverstripe.com');
	define('PINGDOM_PASSWORD', 'secret_password');
	define('PINGDOM_API_KEY', 'secret_token'); // this can be created at https://my.pingdom.com/account/appkeys