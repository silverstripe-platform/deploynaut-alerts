<?php

class PingdomGateway extends Object
{
    /**
     * @var \Acquia\Pingdom\PingdomApi
     */
    protected $client;

    /**
     * @config
     *
     * @var string
     */
    protected $username;

    /**
     * @config
     *
     * @var string
     */
    protected $password;

    /**
     * @config
     *
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $account = '';

    /**
     * @var string
     */
    protected $lastError;

    /**
     * @var array
     */
    protected $contactCache = [];

    public function getClient()
    {
        if ($this->client === null) {
            $this->client = new \Acquia\Pingdom\PingdomApi(
                $this->config()->username,
                $this->config()->password,
                $this->config()->key
            );
        }

        return $this->client;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param bool $cached - use the in-memory cache
     *
     * @return array|string
     */
    public function getNotificationContacts($cached = true)
    {
        if (!$this->contactCache) {
            $this->contactCache = $this->getClient()->getNotificationContacts();
        }

        return $this->contactCache;
    }

    /**
     * @param $contactID
     *
     * @return bool|stdClass
     */
    public function getNotificationContact($contactID)
    {
        $contacts = $this->getNotificationContacts();
        foreach ($contacts as $contact) {
            if ($contactID == $contact->id) {
                return $contact;
            }
        }

        return false;
    }

    /**
     * Returns a full check URL from a raw Pingdom check output.
     *
     * @param $check
     *
     * @return string
     */
    public function getCheckURL($check)
    {
        if (!property_exists($check->type, 'http')) {
            return false;
        }

        $data = $check->type->http;
        $proto = ($data->encryption) ? 'https://' : 'http://';
        $domain = $check->hostname;
        $url = $data->url;

        return $proto.$domain.$url;
    }

    /**
     * $contact must have an email address in $contact['email'].
     *
     * @param array $contact
     *
     * @return mixed
     */
    public function addOrModifyContact(array $contact)
    {
        if (empty($contact['email'])) {
            throw new \RuntimeException('notification contact must have an email set');
        }

        if (!isset($contact['name'])) {
            $contact['name'] = $contact['email'];
        }

        $existingContacts = $this->getNotificationContacts(false);

        $updateId = null;
        foreach ($existingContacts as $existingContact) {
            if ($existingContact->email == $contact['email']) {
                return $this->getClient()->modifyNotificationContact($existingContact->id, $contact);
            }
        }

        return $this->getClient()->addNotificationContact($contact);
    }

    /**
     * @param $email
     *
     * @return bool|string
     */
    public function removeNotificationContact($email)
    {
        $existingContacts = $this->getNotificationContacts();
        foreach ($existingContacts as $existingContact) {
            if ($existingContact->email == $email) {
                return $this->getClient()->removeNotificationContact($existingContact->id);
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getChecks()
    {
        // get all checks, max value of 25000 checks should be good enough for a long time...
        return $this->getClient()->getChecks(25000);
    }

    /**
     * @param $id
     *
     * @return stdClass
     */
    public function getCheck($id)
    {
        return $this->getClient()->getCheck($id);
    }

    /**
     * @param int   $checkId
     * @param array $parameters
     *
     * @return string
     */
    public function modifyCheck($checkId, $parameters)
    {
        return $this->getClient()->modifyCheck($checkId, $parameters);
    }

    /**
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param string $url        - http(s)://www.silverstripe.com/test-url
     * @param array  $contacts   - array(array('email' => 'email@silverstripe.com', 'name' => 'contact name'))
     * @param int    $resolution - 1, 5, 15, 30, 60 mins
     * @param bool   $pause      - set to true to pause this check
     * @param array  $tags       - list of tags to attach
     *
     * @throws Exception
     *
     * @return bool - check successfully added
     */
    public function addOrModifyAlert($url, $contacts, $resolution = 5, $pause = false, $tags)
    {
        $params = $this->paramsFromURL($url);

        if (!$params) {
            $this->lastError = "URL ${url} is not a valid check URL";

            return false;
        }

        if (!count($contacts)) {
            $this->lastError = 'An check must have at least one contact';

            return false;
        }

        // crude verification that the $contact array is formatted properly
        foreach ($contacts as $key => $contact) {
            if (!isset($contact['name'])) {
                $this->lastError = "one contact did not have a 'name' defined";
                echo 'no name';

                return false;
            }
            if (!isset($contact['email'])) {
                $this->lastError = "one contact did not have an 'email' defined";
                echo 'no email';

                return false;
            }
        }

        $params = array_merge($params, [
            'paused' => $pause,
            'use_legacy_notifications' => true,
            'sendtoemail' => true,
            'sendtosms' => true,
            'resolution' => $resolution,
            'tags' => implode(',', $tags), // doesn't seems like these are getting set
        ]);

        $existingCheck = $this->findExistingCheck($params);

        if ($existingCheck) {
            echo 'we think this exists';
            $existingContacts = [];
            if (property_exists($existingCheck, 'contactids')) {
                $existingContacts = $this->getContactsForCheck($existingCheck->contactids);
            }

            // check if $contacts should be added, changed or not changed at all
            foreach ($contacts as $key => $contact) {
                foreach ($existingContacts as $existingContact) {
                    if ($existingContact['email'] == $contact['email']) {
                        $contact['id'] = $existingContact['id'];
                        if ($contact != $existingContact) {
                            $contact['status'] = 'change';
                        } else {
                            $contact['status'] = 'nochange';
                        }
                    }
                    $contacts[$key] = $contact;
                }
            }

            // don't attempt to pause a modification of a check that concerns ops
            // as it may have been manually unpaused.
            // @todo this would be better done in the AlertService somehow, if it
            // knew about the existing contacts.
            foreach ($existingContacts as $existingContact) {
                if ($existingContact['email'] == DEPLOYNAUT_OPS_EMAIL) {
                    unset($params['paused']);
                    break;
                }
            }

            // check if existing contacts should be removed
            foreach ($existingContacts as $existingContact) {
                $remove = true;
                foreach ($contacts as $contact) {
                    if ($existingContact['email'] == $contact['email']) {
                        $remove = false;
                        break;
                    }
                }
                if ($remove) {
                    $contacts[] = [
                        'email' => $existingContact['email'],
                        'id' => $existingContact['id'],
                        'status' => 'remove',
                    ];
                }
            }

            $contactIds = [];

            // act on the status of the $contacts
            foreach ($contacts as $contact) {
                // if status isn't set, this is a new contact
                if (!isset($contact['status'])) {
                    $contactParams = $contact;
                    unset($contactParams['status']);
                    unset($contactParams['id']);

                    $newContact = $this->getClient()->addNotificationContact($contactParams);
                    $contactIds[] = $newContact->id;
                    continue;
                }

                switch ($contact['status']) {
                    case 'nochange':
                        // do nothing, but ensure that this contact is attached to the check
                        $contactIds[] = $contact['id'];
                        break;
                    case 'change':
                        $contactParams = $contact;
                        unset($contactParams['status']);
                        unset($contactParams['id']);
                        $this->getClient()->modifyNotificationContact($contact['id'], $contactParams);
                        $contactIds[] = $contact['id'];
                        break;
                    case 'remove':
                        $this->getClient()->removeNotificationContact($contact['id']);
                        break;
                }
            }
            $params['contactids'] = implode(',', $contactIds);

            $this->getClient()->modifyCheck($existingCheck->id, $params);

            return $existingCheck->id;
        }

        echo 'we think this doesnt exist';

        // @todo(stig): garbage collect old checks, however we are going to do that..
        $contactIds = [];
        foreach ($contacts as $contact) {
            $contactParams = $contact;
            unset($contactParams['status']);
            unset($contactParams['id']);
            $newContact = $this->getClient()->addNotificationContact($contactParams);
            $contactIds[] = $newContact->id;
        }
        $params['contactids'] = implode(',', $contactIds);
        $params['name'] = $params['url'];
        $params['type'] = 'http';

        var_dump($params);

        return $this->getClient()->addCheck($params);
    }

    /**
     * @param array $contactIDs
     *
     * @return array;
     */
    public function getContactsForCheck($contactIDs)
    {
        $existingContacts = [];
        foreach ($contactIDs as $contactID) {
            $contact = $this->getNotificationContact($contactID);

            if (!$contact) {
                continue;
            }

            $existingContacts[] = [
                'name' => property_exists($contact, 'name') ? $contact->name : '',
                'email' => property_exists($contact, 'email') ? $contact->email : '',
                'cellphone' => property_exists($contact, 'cellphone') ? $contact->cellphone : '',
                'countrycode' => property_exists($contact, 'countrycode') ? $contact->countrycode : '',
                'countryiso' => property_exists($contact, 'countryiso') ? $contact->countryiso : '',
                'id' => $contactID,
            ];
        }

        return $existingContacts;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function findExistingCheck($params)
    {
        $checks = $this->getChecks();

        $url = $params['encryption'] ? 'https://' : 'http://';
        $url .= $params['host'].$params['url'];
        foreach ($checks as $check) {
            if ($check->hostname != $params['host']) {
                continue;
            }
            $detailedCheck = $this->getClient()->getCheck($check->id);
            $existingUrl = $this->getCheckURL($detailedCheck);
            // we found an existing check
            if ($existingUrl == $url) {
                return $detailedCheck;
            }
        }
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public function paramsFromURL($url)
    {
        $pattern = '|^(https?)://([^/]*)(.*)$|';
        preg_match($pattern, trim($url), $matches);
        if (count($matches) != 4) {
            return [];
        }
        $url = $matches[3];
        // remove double // from the beginning of the url
        if (substr($url, 0, 2) == '//') {
            $url = substr($url, strlen(1));
        }

        if (empty($url)) {
            $url = $matches[2];
        }

        return [
            'host' => $matches[2],
            'url' => $url,
            'encryption' => ($matches[1] == 'https') ? true : false,
        ];
    }

    public function pauseCheck($checkID)
    {
        return $this->getClient()->pauseCheck($checkID);
    }

    public function unpauseCheck($checkID)
    {
        return $this->getClient()->unpauseCheck($checkID);
    }
}
