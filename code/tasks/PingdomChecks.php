<?php

class PingdomChecks extends BuildTask {


	/**
	 * Implement this method in the task subclass to
	 * execute via the TaskRunner
	 */
	public function run($request) {

		/** @var PingdomGateway $gw */
		$gw = Injector::inst()->get('PingdomGateway');

		$contact = array(
			'name' => 'Stig Lindqvist',
			'email' => 'stig2@silverstripe.com',
			'cellphone' => '221000001',
			'countrycode' => '64',
			'countryiso' => 'NZ',
		);

		$gw->addOrModifyContact($contact);

		$contacts = $gw->getNotificationContacts();

		foreach($contacts as $contact) {
			echo $contact->id . ' | ';
			echo $contact->name . ' | ';
			echo $contact->email . ' | ';
			echo ( !empty($contact->cellphone) ? $contact->cellphone : '----------' ).' | ';
			echo $contact->countryiso . ' | ';
			echo PHP_EOL;
		}

		echo count($contacts) . ' contacts' . PHP_EOL;

		$gw->removeNotificationContact('stig2@silverstripe.com');

		$contacts = $gw->getNotificationContacts();

		foreach($contacts as $contact) {
			echo $contact->id . ' | ';
			echo $contact->name . ' | ';
			echo $contact->email . ' | ';
			echo ( !empty($contact->cellphone) ? $contact->cellphone : '----------' ).' | ';
			echo $contact->countryiso . ' | ';
			echo PHP_EOL;
		}

		//		$checks = $gw->getChecks();
//		foreach($checks as $check) {
//			$check = $gw->getCheck($check->id);
//			var_dump($check);
//		}
	}

	function createCheck($pingdom) {
//		$t = $pingdom->addCheck(array(
//			'name' => 'testcheck',
//			'host' => 'lemon.stojg.se',
//			'use_legacy_notifications' => true,
//			'resolution' => 1,
//			'type' => 'http',
//			'url' => '/',
//			'encryption' => true,
////			'port' => '443',
////			'auth' => '',
////			'shouldcontain' => '',
////			'shouldnotcontain' => '',
////			'postdata' => '',
////			'auth' => '',
//		));
//		echo $t.PHP_EOL;
	}
}
