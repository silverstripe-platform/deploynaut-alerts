<?php
use Symfony\Component\Yaml\Yaml;

class AlertService {

	private static $dependencies = array(
		'gateway' => '%$PingdomGateway'
	);

	/**
	 * Output the raw content of the alerts.yml file from HEAD of a bare repository.
	 * @param DNProject $project
	 * @return null|string
	 */
	public function getAlertsConfigContent($project) {
		return shell_exec(sprintf(
			'cd %s && git show --format=raw HEAD:_config/alerts.yml',
			$project->getLocalCVSPath()
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
		$content = $this->getAlertsConfigContent($project);
		if(!$content) {
			$log->write('Skipping alert configuration. No alerts.yml found in site code.');
			return false;
		}

		// check if there's anything in the alerts.yml for this environment
		$config = Yaml::parse($content);
		if(!isset($config['alerts'])) {
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
			$contactsList = new ArrayList();
			foreach($alertConfig['contacts'] as $contactEmail) {
				// special case for ops
				if($contactEmail == 'ops') {
					$contact = new ArrayData(array(
						'Name' => 'SilverStripe Operations Team',
						'Email' => DEPLOYNAUT_OPS_EMAIL,
						'SMS' => null
					));
				} else {
					$contact = AlertContact::get()->filter('Email', $contactEmail)->first();
					if(!($contact && $contact->exists())) {
						$log->write(sprintf('ERROR: No such contact "%s" for alert "%s".', $contactEmail));
						return false;
					}
				}

				$contactsList->push($contact);
			}

			// validate the environment specified in the alert actually exists
			if(!DNEnvironment::get()->filter('Name', $alertConfig['environment'])->first()) {
				$log->write('ERROR: Invalid environment "%s" in alerts.yml.');
				return false;
			}

			// the alert has an environment that matches the environment we're deploying to now. Configure the alerts.
			if($alertConfig['environment'] == $environment->Name) {
				$log->write(sprintf('Configuring alert "%s" from alerts.yml', $alertName));
			}

			$contacts = array();
			$paused = false;

			foreach($contactsList as $contact) {
				// alerts that concern ops are not effective immediately, but paused until ops have approved the alert
				if($contact == 'ops') {
					$paused = true;
				}

				$contacts[] = array(
					'name' => $contact->Name,
					'email' => $contact->Email,
					'sms' => $contact->SMS
				);
			}

			$result = $this->gateway->addOrModifyAlert(
				sprintf('%s/dev/check/%s', $environment->URL, $alertConfig['envcheck-suite']),
				$contacts,
				5, // the check interval in minutes
				$paused
			);

			if(!$result) {
				$log->write(sprintf('Failed to configure alert "%s"', $alertName));
				return false;
			}

			if($paused) {
				$log->write(sprintf(
					'Sucessfully configured alert "%s", but has been disabled pending approval. Please contact SilverStripe Operations Team to have it approved',
					$alertName
				));
			} else {
				$log->write(sprintf('Successfully configured alert "%s"', $alertName));
			}

			return true;
		}
	}

}
