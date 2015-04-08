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

}