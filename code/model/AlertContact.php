<?php
class AlertContact extends DataObject {

	private static $db = array(
		'Email' => 'Varchar(255)',
		'SMS' => 'Varchar(100)'
	);

	private static $has_one = array(
		'Project' => 'DNProject'
	);

	private static $summary_fields = array(
		'Email' => 'Email'
	);

}
