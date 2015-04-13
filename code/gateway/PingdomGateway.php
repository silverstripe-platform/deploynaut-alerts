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
	 * @var string
	 */
	protected $account = "";

	/**
	 * @var string
	 */
	protected $lastError;

	protected $contactCache = array();

	/**
	 * @param bool $cached - use the in-memory cache
	 * @return array|string
	 */
	public function getNotificationContacts($cached = true) {
		if(!$this->contactCache) {
			$this->contactCache = $this->pingdom->getNotificationContacts();
		}
		return $this->contactCache;
	}

	/**
	 * @param $contactID
	 * @return bool
	 */
	public function getNotificationContact($contactID) {
		$contacts = $this->getNotificationContacts();
		foreach($contacts as $contact) {
			if($contactID == $contact->id) {
				return $contact;
			}
		}

		return false;
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

		$existingContacts = $this->getNotificationContacts(false);


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
		// get all checks, max value of 25000 checks should be good enough for a long time...
		return $this->pingdom->getChecks(25000);
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
	 * @return string
	 */
	public function getLastError() {
		return $this->lastError;
	}

	/**
	 *
	 * @param string $url - http(s)://www.silverstripe.com/test-url
	 * @param array $contacts - array(array('email' => 'email@silverstripe.com', 'name' => 'contact name'))
	 * @param int $resolution - 1, 5, 15, 30, 60 mins
	 * @param bool $pause - set to true to pause this check
	 * @return bool - check successfully added
	 * @throws Exception
	 */
	public function addOrModifyAlert($url, $contacts, $resolution = 5, $pause=false) {

		$params = $this->paramsFromURL($url);

		if(!$params) {
			$this->lastError = "URL ${url} is not a valid check URL";
			return false;
		}

		if(!count($contacts)) {
			$this->lastError = "An check must have at least one contact";
			return false;
		}

		// crude verification that the $contact array is formatted properly
		foreach($contacts as $key => $contact) {
			if(!isset($contact['name'])) {
				$this->lastError = "one contact did not have a 'name' defined";
				return false;
			}
			if(!isset($contact['email'])) {
				$this->lastError = "one contact did not have an 'email' defined";
				return false;
			}
		}

		$params = array_merge($params, array(
			"paused" => $pause,
			"use_legacy_notifications" => true,
			"sendtoemail" => true,
			"sendtosms" => true,
			"resolution" => $resolution,
			"tags" => "tag1,tag2", // doesn't seems like these are getting set
		));

		$existingCheck = $this->findExistingCheck($params);

		if($existingCheck) {
			$existingContacts = array();
			if(property_exists($existingCheck, 'contactids')) {
				$existingContacts = $this->getContactsForCheck($existingCheck->contactids);
			}

			// check if $contacts should be added, changed or not changed at all
			foreach($contacts as $key => $contact) {
				foreach($existingContacts as $existingContact) {
					if($existingContact['email'] == $contact['email']) {
						$contact['id'] = $existingContact['id'];
						if($contact != $existingContact) {
							$contact['status'] = 'change';
						} else {
							$contact['status'] = 'nochange';
						}
					}
					$contacts[$key] = $contact;
				}
			}

			// check if existing contacts should be removed
			foreach($existingContacts as $existingContact) {
				$remove = true;
				foreach($contacts as $contact) {
					if($existingContact['email'] == $contact['email']) {
						$remove = false;
						break;
					}
				}
				if($remove) {
					$contacts[] = array(
						'email' => $existingContact['email'],
						'id' => $existingContact['id'],
						'status' => 'remove',
					);
				}
			}

			$contactIds = array();

			// act on the status of the $contacts
			foreach($contacts as $contact) {

				// if status isn't set, this is a new contact
				if(!isset($contact['status'])) {
					$contactParams = $contact;
					unset($contactParams['status']);
					unset($contactParams['id']);

					$newContact = $this->pingdom->addNotificationContact($contactParams);
					$contactIds[] = $newContact->id;
					continue;
				}

				switch($contact['status']) {
					case "nochange":
						// do nothing, but ensure that this contact is attached to the check
						$contactIds[] = $contact['id'];
						break;
					case "change":
						$contactParams = $contact;
						unset($contactParams['status']);
						unset($contactParams['id']);
						$this->pingdom->modifyNotificationContact($contact['id'], $contactParams);
						$contactIds[] = $contact['id'];
						break;
					case "remove":
						$this->pingdom->removeNotificationContact($contact['id']);
						break;
				}
			}
			$params['contactids'] = implode(',',$contactIds);
			$this->pingdom->modifyCheck($existingCheck->id, $params);
			return true;
		}

		// @todo(stig): garbage collect old checks, however we are going to do that..
		$contactIds = array();
		foreach($contacts as $contact) {
			$contactParams = $contact;
			unset($contactParams['status']);
			unset($contactParams['id']);
			$newContact = $this->pingdom->addNotificationContact($contactParams);
			$contactIds[] = $newContact->id;
		}
		$params['contactids'] = implode(',',$contactIds);
		$params['name'] = $params['url'];
		$params['type'] = 'http';
		$this->pingdom->addCheck($params);
		return true;
	}

	/**
	 * @param array $contactIDs
	 * @return array;
	 */
	public function getContactsForCheck($contactIDs) {
		$existingContacts = array();
		foreach($contactIDs as $contactID) {
			$contact = $this->getNotificationContact($contactID);

			if(!$contact) {
				continue;
			}

			$existingContacts[] = array(
				'name' => $contact->name,
				'email'=> $contact->email,
				'id' => $contactID
			);
		}

		return $existingContacts;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function findExistingCheck($params) {
		$checks = $this->getChecks();

		$url = $params['encryption'] ? 'https://' : 'http://';
		$url.= $params['host'].$params['url'];
		foreach($checks as $check) {
			if($check->hostname != $params['host']) {
				continue;
			}
			$detailedCheck = $this->pingdom->getCheck($check->id);
			$existingUrl = $this->getCheckURL($detailedCheck);
			// we found an existing check
			if($existingUrl == $url) {
				return $detailedCheck;
			}
		}
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
		$url = $matches[3];
		// remove double // from the beginning of the url
		if (substr($url, 0, 2) == '//') {
			$url = substr($url, strlen(1));
		}
		return array(
			"host" => $matches[2],
			"url" => $url,
			"encryption" => ($matches[1] == 'https') ? true:false,
		);
	}

}
