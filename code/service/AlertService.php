<?php
use Symfony\Component\Yaml\Yaml;

class AlertService {

	private static $dependencies = array(
		'gateway' => '%$PingdomGateway'
	);

	/**
	 * Output the raw content of the alerts.yml file from HEAD of a bare repository.
	 * @return null|string
	 */
	public function getAlertsConfigContent() {
		return shell_exec(sprintf(
			'cd %s && git show --format=raw HEAD:_config/alerts.yml',
			$this->getCurrentProject()->getLocalCVSPath()
		));
	}

	/**
	 * Given a project and environment, sync the alerts configuration with the alerts gateway.
	 *
	 * @param DNProject $project
	 * @param DNEnvironment $environment
	 * @param DeploynautLogFile $log
	 *
	 * @return boolean
	 */
	public function sync($project, $environment, $log) {
		$config = $this->getAlertsConfigContent();
		if(!$config) {
			$log->write('Skipping alert configuration. No alerts.yml found in site code.');
			return false;
		}

		// check if there's anything in the alerts.yml for this environment
		$configArr = Yaml::parse($config);
		if(!isset($configArr['alerts'])) {
			$log->write('ERROR: Malformed alerts.yml. Missing "alerts" key.');
			return false;
		}

		foreach($config['alerts'] as $alertName => $alertConfig) {
			// validate we have an environment set for the alert
			if(!isset($alertConfig['environment'])) {
				$log->write(sprintf('ERROR: Malformed alerts.yml. Missing "environment" key for alert "%s".', $alertName));
				return false;
			}

			// validate we have an environmentcheck suite name to check
			if(!isset($alertConfig['envcheck-suite'])) {
				$log->write(sprintf('ERROR: Malformed alerts.yml. Missing "envcheck-suite" key for alert "%s".', $alertName));
				return false;
			}

			// validate we have contacts for the alert
			if(!isset($alertConfig['contacts'])) {
				$log->write(sprintf('ERROR: Malformed alerts.yml. Missing "contacts" key for alert "%s".', $alertName));
				return false;
			}

			// validate that each value in the config is valid, build up a list of contacts we'll use later
			$contacts = new ArrayList();
			foreach($alertConfig['contacts'] as $contactEmail) {
				if($contactEmail == 'ops') continue; // ignore the "ops" one, we handle that as a special case later

				$contact = AlertContact::get()->filter('Email', $contactEmail)->first();
				if(!($contact && $contact->exists())) {
					$log->write(sprintf('ERROR: No such contact "%s" for alert "%s".', $contactEmail));
					return false;
				}

				$contacts->push($contact);
			}

			// validate the environment specified in the alert actually exists
			if(!DNEnvironment::get()->filter('Name', $alertConfig['environment'])->first()) {
				$log->write('ERROR: Invalid environment "%s" in alerts.yml.');
				return false;
			}

			// the alert has an environment that matches the environment we're deploying to now. Configure the alerts.
			if($alertConfig['environment'] == $environment->Name) {
				$log->write(sprintf('Configuring alert "%s" for %s:%s from alerts.yml', $alertName, $project->Name, $environment->Name));
			}

			$url = sprintf('%s/dev/check/%s', $environment->URL, $alertConfig['envcheck-suite']);

			// todo: send the data to PingdomGateway

		}
	}

}
