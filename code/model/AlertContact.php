<?php
class AlertContact extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)',
		'Email' => 'Varchar(255)',
		'SMSCountryCode' => 'Varchar(2)',
		'SMSCellphone' => 'Varchar(100)',
		'SMSCountryISO' => 'Varchar(2)'
	);

	private static $has_one = array(
		'Project' => 'DNProject'
	);

	private static $summary_fields = array(
		'Name' => 'Name',
		'Email' => 'Email'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->dataFieldByName('SMSCountryCode')->setDescription('Country code, e.g. 64 for New Zealand');
		$fields->dataFieldByName('SMSCellphone')->setDescription('Phone number without country code and leading zero, e.g. 21123456');
		$fields->dataFieldByName('SMSCountryISO')->setDescription('Country ISO code, e.g. NZ for New Zealand');
		return $fields;
	}

}
