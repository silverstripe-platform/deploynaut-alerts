<?php

class PingdomGateway extends Object {

	/**
	 * @var array
	 */
	public static $dependencies = array(
		'pingdom' => '%$PingdomService',
	);

	/**
	 * @var \Acquia\Pingdom\PingdomApi
	 */
	public $pingdom;

	/**
	 * @var \Acquia\Pingdom\PingdomApi
	 */
	protected $api = null;

	/**
	 * @return array
	 */
	public function getNotificationContacts() {
		return $this->pingdom->getNotificationContacts();
	}

	/**
	 * Returns a full check URL from a raw Pingdom check output
	 *
	 * @param $check
	 * @return string
	 */
	public function getCheckURL($check) {
		if(!property_exists($check->type, 'http')) {
			return 'not a http check';
		}
		$data = $check->type->http;
		$proto = ($data->encryption)?'https://':'http://';
		$domain = $check->hostname;
		$url = $data->url;

		return $proto.$domain.$url;

	}

	/**
	 * $contact must have an email address in $contact['email']
	 *
	 * @param array $contact
	 * @return mixed
	 */
	public function addOrModifyContact(array $contact) {

		if(empty($contact['email'])) {
			throw new \RuntimeException("notification contact must have an email set");
		}

		if(!isset($contact['name'])) {
			$contact['name'] = $contact['email'];
		}

		$existingContacts = $this->getNotificationContacts();


		$updateId = null;
		foreach($existingContacts as $existingContact) {
			if($existingContact->email == $contact['email']) {
				return $this->pingdom->modifyNotificationContact($existingContact->id, $contact);
			}
		}
		return $this->pingdom->addNotificationContact($contact);
	}

	/**
	 * @param $email
	 * @return bool|string
	 */
	public function removeNotificationContact($email) {
		$existingContacts = $this->getNotificationContacts();
		foreach($existingContacts as $existingContact) {
			if($existingContact->email == $email) {
				return $this->pingdom->removeNotificationContact($existingContact->id);
			}
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function getChecks() {
		return $this->pingdom->getChecks();
	}

	/**
	 * @param $id
	 * @return stdClass
	 */
	public function getCheck($id) {
		return $this->pingdom->getCheck($id);
	}

	/**
	 * @param int $checkId
	 * @param array $parameters
	 * @return string
	 */
	public function modifyCheck($checkId, $parameters) {
		return $this->pingdom->modifyCheck($checkId, $parameters);
	}

	/**
	 *
	 * @param string $url - http(s)://www.silverstripe.com/test-url
	 * @param array $users - array('email@silverstripe.com' => 'user name')
	 * @param int $resolution - 1, 5, 15, 30, 60 mins
	 * @param bool $pause - set to true to pause this check
	 */
	public function addOrModifyAlert($url, $users, $resolution = 5, $pause=false) {
		//@todo, implement
	}

	/**
	 * @param string $url
	 * @return array
	 */
	public function paramsFromURL($url) {
		$pattern = "|^(https?)://([^/]*)(.*)$|";

		preg_match($pattern, trim($url), $matches);

		if(count($matches) != 4) {
			return array();
		}

		return array(
			"name" => $matches[2],
			"url" => $matches[3],
			"encryption" => ($matches[1] == 'https')?true:false,
		);
	}

}