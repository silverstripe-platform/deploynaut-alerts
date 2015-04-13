<?php
class AlertContact extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)',
		'Email' => 'Varchar(255)'
	);

	private static $has_one = array(
		'Project' => 'DNProject'
	);

	private static $summary_fields = array(
		'Name' => 'Name',
		'Email' => 'Email'
	);

}
