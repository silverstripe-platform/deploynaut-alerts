<?php

class PingdomGatewayTest extends SapphireTest {

	/**
	 * @var \Acquia\Pingdom\PingdomApi
	 */
	protected $api = null;

	public function setUp() {
		// mock the real api
		$this->api = $this->getMock(
			'\Acquia\Pingdom\PingdomApi',
			array('request'), // only mock the request method
			array("user@test.com", "password", "token") // constructor arguments
		);

		Injector::inst()->registerService($this->api, 'PingdomService');
	}

	public function testGetUsers() {
		$result = (object) array('contacts' => array(
			(object) array(
				'email' => 'contact@test.com',
				'id' => 578657,
				'name' => 'Test Contact (u)',
			)
		));

		$this->api->expects($this->once())
			->method('request')
			->with($this->equalTo('GET'), $this->equalTo('notification_contacts'))
			->will($this->returnValue($result));

		$contacts = PingdomGateway::create()->getNotificationContacts();
		$this->assertInternalType('array', $contacts);
		$this->assertEquals(count($contacts), 1, 'there should be one contact from getAllContacts()');
		$this->assertEquals($contacts[0]->email, 'contact@test.com');
	}

	public function testAddContact() {
		$result = (object) array('contacts' => array(
			(object) array(
				'email' => 'contact@test.com',
				'id' => 578657,
				'name' => 'Test Contact (u)',
			)
		));

		$this->api->expects($this->at(0))
			->method('request')
			->with($this->equalTo('GET'), $this->equalTo('notification_contacts'))
			->will($this->returnValue($result));

		$this->api->expects($this->at(1))
			->method('request')
			->with($this->equalTo('POST'), $this->equalTo('notification_contacts'));

		$newContact = array(
			'name' => 'random user',
			'email' => 'random@silverstripe.com',
		);

		PingdomGateway::create()->addOrModifyContact($newContact);
	}

	public function testAddContactNoEmailThrowException() {

		$this->api->expects($this->never())->method('request');

		$this->setExpectedException('RuntimeException');

		PingdomGateway::create()->addOrModifyContact(array('name' => 'random user'));
	}

	public function testAddContactNoNameUsesEmail() {

		$result = (object) array('contacts' => array());

		$this->api->expects($this->at(0))
			->method('request')
			->with($this->equalTo('GET'), $this->equalTo('notification_contacts'))
			->will($this->returnValue($result));

		// expect that name is set to the email address
		$this->api->expects($this->at(1))
			->method('request')
			->with($this->equalTo('POST'), $this->equalTo('notification_contacts'), $this->equalTo(array(
				'email' => 'random@silverstripe.com',
				'name' => 'random@silverstripe.com',
			)));

		$newContact = array(
			'email' => 'random@silverstripe.com',
		);

		PingdomGateway::create()->addOrModifyContact($newContact);

	}

	public function testUpdateContact() {

		$result = (object) array('contacts' => array(
			(object) array(
				'email' => 'contact@test.com',
				'id' => 578657,
				'name' => 'Test Contact (u)',
			)
		));

		$this->api->expects($this->at(0))
			->method('request')
			->with($this->equalTo('GET'), $this->equalTo('notification_contacts'))
			->will($this->returnValue($result));

		// expect that we are using PUT for modifying existing user
		$this->api->expects($this->at(1))
			->method('request')
			->with($this->equalTo('PUT'), $this->equalTo('notification_contacts/578657'))
			->will($this->returnValue((object)array('message' => 'Modification of notification contact was successful!')));

		$contact = array(
			'name' => 'Updated Name (u)',
			'email' => 'contact@test.com'
		);

		PingdomGateway::create()->addOrModifyContact($contact);
	}
}