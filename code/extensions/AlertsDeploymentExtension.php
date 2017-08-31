<?php
class AlertsDeploymentExtension extends Extension {

	private static $dependencies = [
		'alertService' => '%$AlertService'
	];

	/**
	 * After a successful deployment, configure the Pingdom alerts
	 * configured from the .alerts.yml in the site code repository.
	 *
	 * @param DNEnvironment $environment
	 * @param string $sha
	 * @param DeploynautLogFile $log
	 * @param DNProject $project
	 * @param array $options
	 */
	public function deployEnd($environment, $sha, $log, $project, $options) {
		$this->alertService->sync($environment, $sha, $log, $project);
	}

}
