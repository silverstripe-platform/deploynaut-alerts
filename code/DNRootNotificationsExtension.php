<?php
class DNRootNotificationsExtension extends Extension {

	private static $allowed_actions = array(
		'notifications'
	);

	public function getCurrentProject() {
		return $this->owner->DNProjectList()->filter('Name', $this->owner->getRequest()->latestParam('Project'))->first();
	}

	public function notifications(SS_HTTPRequest $request) {
		$project = $this->getCurrentProject();
		if(!$project) {
			return new SS_HTTPResponse("Project '" . Convert::raw2xml($request->latestParam('Project')) . "' not found.", 404);
		}

		return $this->owner->customise(array(
			'Title' => 'Notifications',
			'Project' => $project,
			'CurrentProject' => $project,
		))->renderWith(array('DNRoot_notifications', 'DNRoot'));
	}

}
