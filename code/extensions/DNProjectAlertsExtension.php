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
		$controller = Controller::curr();
		$actionType = $controller->getField('CurrentActionType');

		$list->push(new ArrayData(array(
			'Link' => sprintf('naut/project/%s/alerts', $this->owner->Name),
			'Title' => 'Alerts',
			'IsCurrent' => $this->owner->isSection() && $controller->getAction() == 'alerts',
			'IsSection' => $this->owner->isSection() && $actionType == DNRootAlertsExtension::ACTION_ALERT
		)));
	}

}
