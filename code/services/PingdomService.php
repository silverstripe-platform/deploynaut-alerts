<?php

/**
 * Class PingdomService
 *
 * This class wraps around the \Acquia\Pingdom\PingdomApi. This is necessary because we cannot
 * inject the username/password/token from the yaml config on non SilverStripe objects
 *
 *
 */
class PingdomService extends Object {

	/**
	 * @var \Acquia\Pingdom\PingdomApi
	 */
	protected $api = null;

	/**
	 * @return \Acquia\Pingdom\PingdomApi
	 */
	public function api() {
		if(!$this->api) {
			$cfg = $this->config();
			$username = $cfg->get('username');
			$password = $cfg->get('password');
			$token = $cfg->get('token');
			$this->api = new \Acquia\Pingdom\PingdomApi($username, $password, $token);
			if($cfg->get('account')) {
				$this->api->setAccount($cfg->get('account'));
			}
		}
		return $this->api;
	}

	/**
	 * delegate all calls to the \Acquia\Pingdom\PingdomApi object
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->api(), $name), $arguments);
	}

}