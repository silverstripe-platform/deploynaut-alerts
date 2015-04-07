<?php
class DNProjectAlertsExtension extends DataExtension {

	public function updateMenu($list) {
		$list->push(new ArrayData(array(
			'Link' => sprintf('naut/project/%s/alerts', $this->owner->Name),
			'Title' => 'Alerts',
			'IsActive' => Controller::curr()->getAction() == 'alerts'
		)));
	}

}
