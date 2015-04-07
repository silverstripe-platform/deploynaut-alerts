<?php

class PingdomServiceTest extends SapphireTest {

	public function setUp() {
		Injector::inst()->registerService(new PingdomServiceMock(), 'PingdomService');
		$gw = Injector::inst()->get('PingdomGateway');
		$gw->pingdom->clearCache();
	}

	public function tearDown() {
		Injector::inst()->unregisterNamedObject('PingdomServiceMock');
	}

	public function testGetServiceInstance() {
		/** @var PingdomGateway $gw */
		$gw = Injector::inst()->get('PingdomGateway');
		$this->assertTrue($gw instanceof PingdomGateway);
		$this->assertTrue($gw->pingdom instanceof PingdomServiceMock);
	}

	public function testGetUsers() {
		$gw = Injector::inst()->get('PingdomGateway');
		$contacts = $gw->getContacts();
		$this->assertInternalType('array', $contacts);
		$this->assertEquals(count($contacts), 1, 'there should be one contact from getAllContacts()');
		$this->assertEquals($contacts[0]->type, 'Notification contact', "the contacts 'type' should be 'Notification contact'");
	}

	public function testAddContact() {
		/** @var PingdomGateway $gw */
		$gw = Injector::inst()->get('PingdomGateway');
		$contact = array(
			'name' => 'random user',
			'email' => 'random@silverstripe.com',
			'cellphone' => '64-221234567'
		);

		$gw->addOrModifyContact($contact);

		$contacts = $gw->getContacts();
		$this->assertEquals(count($contacts), 2, 'there should be two contacts in pingdom');
		$this->assertEquals($contacts[1]->email, $contact['email']);
		$this->assertEquals($contacts[1]->name, $contact['name']);
		$this->assertEquals($contacts[1]->cellphone, $contact['cellphone']);
	}

	public function testAddContactNoEmailThrowException() {
		/** @var PingdomGateway $gw */
		$gw = Injector::inst()->get('PingdomGateway');
		$contact = array(
			'name' => 'random user',
			'cellphone' => '64-221234567'
		);

		$this->setExpectedException('RuntimeException');
		$gw->addOrModifyContact($contact);
	}

	public function testAddContactNoNameUsesEmail() {
		/** @var PingdomGateway $gw */
		$gw = Injector::inst()->get('PingdomGateway');
		$contact = array(
			'email' => 'random@silverstripe.com',
		);

		$gw->addOrModifyContact($contact);
		$contacts = $gw->getContacts();
		$this->assertEquals(count($contacts), 2, 'there should be two contacts in pingdom');
		$this->assertEquals($contacts[1]->email, $contact['email']);
		$this->assertEquals($contacts[1]->name, $contact['email']);
		$this->assertEquals($contacts[1]->cellphone, '');
	}

	public function testUpdateContact() {
		/** @var PingdomGateway $gw */
		$gw = Injector::inst()->get('PingdomGateway');
		$contact = array(
			'name' => 'Updated Name',
			'email' => 'stig@silverstripe.com',
			'cellphone' => '64-221000001'
		);

		$gw->addOrModifyContact($contact);

		$contacts = $gw->getContacts();
		$this->assertEquals(count($contacts), 1, 'there should be one contact in pingdom');
		$this->assertEquals($contacts[0]->email, $contact['email']);
		$this->assertEquals($contacts[0]->name, $contact['name']);
		$this->assertEquals($contacts[0]->cellphone, $contact['cellphone']);
	}




}

class PingdomServiceMock implements TestOnly {

	/**
	 * @var array
	 */
	protected $contacts = array();

	/**
	 * @return array
	 */
	public function getContacts() {

		if(!$this->contacts) {
			$first = new stdClass();
			$first->id = 10771596;
			$first->name = "Stig Lindqvist (c)";
			$first->email = "stig@silverstripe.com";
			$first->cellphone = "64-221568043";
			$first->countryiso = "NZ";
			$first->defaultsmsprovider = "nexmo";
			$first->directtwitter = false;
			$first->paused = false;
			$first->type = 'Notification contact';

			$second = new stdClass();
			$second->email = "stig@silverstripe.com";
			$second->id = 578657;
			$second->name = 'Stig Lindqvist (u)';
			$second->type = 'User';
			$this->contacts = array($first, $second);
		}

		return $this->contacts;
	}

	/**
	 * @param array $contact
	 * @return bool
	 */
	public function addContact(array $contact) {
		$stdContact = new stdClass();
		$stdContact->id = mt_rand(10000000, 100000000);
		$stdContact->name = $contact['name'];
		$stdContact->email = $contact['email'];
		$stdContact->cellphone = $contact['cellphone'];
		$stdContact->countryiso = "NZ";
		$stdContact->defaultsmsprovider = "nexmo";
		$stdContact->type = 'Notification contact';
		$this->contacts[] = $stdContact;
		return true;
	}

	/**
	 * @param $id
	 * @param array $contact
	 * @return bool
	 */
	public function modifyContact($id, array $contact) {
		$existingContacts = $this->getContacts();
		foreach($existingContacts as $key => $existingContact) {
			if($existingContact->id == $id) {
				$existingContacts[$key]->name = $contact['name'];
				$existingContacts[$key]->email = $contact['email'];
				$existingContacts[$key]->cellphone = $contact['cellphone'];
				return true;
			}
		}
		return false;
	}

	/**
	 * clears the in-memory cache
	 */
	public function clearCache() {
		$this->contacts = array();
	}
}