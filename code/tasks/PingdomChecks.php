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
			'name' => 'Updated Name',
			'email' => 'stig@silverstripe.com',
			'cellphone' => '64-221000001',
			'countrycode' => 'NZ-64',
			'countryiso' => 'NZ',
		);

//		$gw->addOrModifyContact($contact);

		$contacts = $gw->getContacts();

		foreach($contacts as $contact) {
			echo $contact->id.' | ';
			echo $contact->name.' | ';
			echo $contact->email.' | ';
			echo (!empty($contact->cellphone)?$contact->cellphone:'----------').' | ';
			echo $contact->countryiso.' | ';
			echo $contact->type;
			echo PHP_EOL;
		}


		echo count($contacts).' contacts'.PHP_EOL;

//		$checks = $pingdom->getChecks();
//		foreach($checks as $check) {
//			$check = $pingdom->getCheck($check->id);
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
