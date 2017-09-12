<?php
class DNRootAlertsExtension extends Extension {

	private static $allowed_actions = [
		'alerts',
	];

	const ACTION_ALERT = 'alert';

	private static $dependencies = [
		'alertService' => '%$AlertService'
	];

	public function alerts(SS_HTTPRequest $request) {
		$this->owner->setCurrentActionType(self::ACTION_ALERT);

		$project = $this->owner->getCurrentProject();
		if(!$project) {
			return new SS_HTTPResponse("Project '" . Convert::raw2xml($request->latestParam('Project')) . "' not found.", 404);
		}

		return $this->owner->customise([
			'Title' => 'Alerts',
			'CurrentProject' => $project,
		])->render();
	}

	public function AlertsConfigContent($sha) {
		return $this->alertService->getAlertsConfigContent($this->owner->getCurrentProject(), $sha);
	}

}
