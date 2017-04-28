<?php

if(!defined('DEPLOYNAUT_OPS_EMAIL')) {
	throw new RuntimeException('You must set DEPLOYNAUT_OPS_EMAIL in _ss_environment.php');
}
if(!defined('DEPLOYNAUT_OPS_EMAIL_FROM')) {
	throw new RuntimeException('You must set DEPLOYNAUT_OPS_EMAIL_FROM in _ss_environment.php');
}
if(!defined('PINGDOM_USERNAME')) {
	throw new RuntimeException('You must set PINGDOM_USERNAME in _ss_environment.php');
}
if(!defined('PINGDOM_PASSWORD')) {
	throw new RuntimeException('You must set PINGDOM_PASSWORD in _ss_environment.php');
}
if(!defined('PINGDOM_API_KEY')) {
	throw new RuntimeException('You must set PINGDOM_API_KEY in _ss_environment.php');
}

Config::inst()->update('PingdomGateway', 'username', PINGDOM_USERNAME);
Config::inst()->update('PingdomGateway', 'password', PINGDOM_PASSWORD);
Config::inst()->update('PingdomGateway', 'key', PINGDOM_API_KEY);
