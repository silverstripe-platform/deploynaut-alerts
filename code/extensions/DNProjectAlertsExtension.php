<?php
class DNProjectAlertsExtension extends DataExtension {

	private static $has_many = [
		'AlertContacts' => 'AlertContact'
	];

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

		$list->push(new ArrayData([
			'Link' => $this->owner->Link(DNRootAlertsExtension::ACTION_ALERT),
			'Title' => 'Alerts',
			'IsCurrent' => $this->owner->isSection() && $controller->getAction() == DNRootAlertsExtension::ACTION_ALERT,
			'IsSection' => $this->owner->isSection() && $actionType == DNRootAlertsExtension::ACTION_ALERT
		]));
	}

	public function onAfterDelete() {
		$contacts = $this->owner->AlertContacts();
		if ($contacts && $contacts->exists()) {
			foreach ($contacts as $contact) {
				$contact->delete();
			}
		}
	}

}
