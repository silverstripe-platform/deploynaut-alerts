<?php
class DNRootAlertsExtension extends Extension {

	private static $allowed_actions = array(
		'alerts'
	);

	public function getCurrentProject() {
		return $this->owner->DNProjectList()->filter('Name', $this->owner->getRequest()->latestParam('Project'))->first();
	}

	public function alerts(SS_HTTPRequest $request) {
		$project = $this->getCurrentProject();
		if(!$project) {
			return new SS_HTTPResponse("Project '" . Convert::raw2xml($request->latestParam('Project')) . "' not found.", 404);
		}

		return $this->owner->customise(array(
			'Title' => 'Alerts',
			'Project' => $project,
			'CurrentProject' => $project,
		))->renderWith(array('DNRoot_alerts', 'DNRoot'));
	}

	/**
	 * Output the raw content of the alerts.yml file from a bare repository.
	 * @return null|string
	 */
	public function AlertsConfigContent() {
		return shell_exec(sprintf(
			'cd %s && git show --format=raw HEAD:_config/alerts.yml',
			$this->getCurrentProject()->getLocalCVSPath()
		));
	}

	/**
	 * Is the alerts.yml file been configured, in that it's accessible and returning some content?
	 * @return boolean
	 */
	public function HasAlertsConfigured() {
		$output = $this->AlertsConfigContent();
		if(!$output) return false;
		return true;
	}

	/**
	 * List all {@link Group} that are available to use in the current project.
	 * @return ArrayList
	 */
	public function AvailableGroups() {
		$list = new ArrayList();
		$project = $this->getCurrentProject();

		foreach($project->Viewers() as $viewerGroup) {
			$list->push($viewerGroup);
		}

		foreach($project->DNEnvironmentList() as $env) {
			$fields = $env->many_many();
			foreach($fields as $field => $class) {
				if($class != 'Group') continue;
				foreach($env->$field() as $envGroup) {
					$list->push($envGroup);
				}
			}
		}

		// the "ops" group isn't actually a group, but is still a valid option
		// to use in alerts.yml as it's a special case to use "ops" as a group
		$list->push(new ArrayData(array(
			'Code' => 'ops'
		)));

		$list->removeDuplicates();

		return $list;
	}

}
