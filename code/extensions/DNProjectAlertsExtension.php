<?php
class DNProjectAlertsExtension extends DataExtension {

	private static $has_many = array(
		'AlertContacts' => 'AlertContact'
	);

	public function updateCMSFields(FieldList $fields) {
		$field = new GridField(
			'AlertContacts',
			'Alert contacts',
			$this->owner->AlertContacts(),
			new GridFieldConfig_RecordEditor()
		);
		$fields->addFieldToTab('Root.AlertContacts', $field);
	}

	public function updateMenu($list) {
		$list->push(new ArrayData(array(
			'Link' => sprintf('naut/project/%s/alerts', $this->owner->Name),
			'Title' => 'Alerts',
			'IsActive' => $this->owner->isCurrent() && Controller::curr()->getAction() == 'alerts'
		)));
	}

}
