<?php
class AlertsDeploymentExtension extends Extension {

	/**
	 * After a successful deployment, configure the Pingdom alerts
	 * configured from the alerts.yml in the site code repository.
	 *
	 * @param DNEnvironment $environment
	 * @param string $sha
	 * @param DeploynautLogFile $log
	 * @param DNProject $project
	 */
	public function deployEnd($environment, $sha, $log, $project) {
		Injector::inst()->get('AlertService')->sync($environment, $log);
	}

}
