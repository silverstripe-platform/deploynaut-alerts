<?php

class PingdomGateway extends Object {

	/**
	 * @var array
	 */
	public static $dependencies = array(
		'pingdom' => '%$PingdomService',
	);

	/**
	 * @var PingdomService
	 */
	public $pingdom;

	/**
	 * @return array
	 */
	public function getContacts() {
		$allUsers = $this->pingdom->getContacts();

		$contacts = array();

		foreach($allUsers as $user) {
			if($user->type != 'Notification contact') {
				continue;
			}
			$contacts[] = $user;
		}
		return $contacts;
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

		if(!isset($contact['cellphone'])) {
			$contact['cellphone'] = '';
		}

		if(!isset($contact['name'])) {
			$contact['name'] = $contact['email'];
		}

		$existingContacts = $this->getContacts();

		$updateId = null;
		foreach($existingContacts as $existingContact) {
			if($existingContact->email == $contact['email']) {
				return $this->pingdom->modifyContact($existingContact->id, $contact);
			}
		}

		return $this->pingdom->addContact($contact);
	}

}