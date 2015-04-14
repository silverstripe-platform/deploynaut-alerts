<?php
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class AlertService {

	private static $dependencies = array(
		'gateway' => '%$PingdomGateway'
	);

	/**
	 * Output the raw content of the .alerts.yml file from HEAD of a bare repository.
	 * @param DNProject $project
	 * @return null|string
	 */
	public function getAlertsConfigContent($project) {
		$process = new Process('git show --format=raw HEAD:.alerts.yml', $project->getLocalCVSPath());
		$process->run();

		// we don't care if the command wasn't successful, which would be caused by a missing .alerts.yml
		// sync() will take care of outputting the "No .alerts.yml found" error message to the user.
		if(!$process->isSuccessful()) {
			return false;
		}

		return $process->getOutput();
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
			$log->write('Skipping alert configuration. No .alerts.yml found in site code.');
			return false;
		}

		try {
			$config = Yaml::parse($content);
		} catch(Symfony\Component\Yaml\Exception\ParseException $e) {
			$log->write(sprintf('ERROR: Could not parse .alerts.yml. %s', $e->getMessage()));
			return false;
		}

		if(!isset($config['alerts'])) {
			$log->write('ERROR: Misconfigured .alerts.yml. Missing "alerts" key.');
			return false;
		}

		foreach($config['alerts'] as $alertName => $alertConfig) {
			$valid = $this->validateAlert($alertName, $alertConfig, $project, $log);
			if(!$valid) continue;

			// the alert has an environment that matches the environment we're deploying to now. Configure the alerts.
			if($alertConfig['environment'] == $environment->Name) {
				$log->write(sprintf('Configuring alert "%s" from .alerts.yml', $alertName));
			} else {
				$log->write(sprintf(
					'Skipping alert "%s" for environment "%s". Does not apply to this environment ("%s")',
					$alertName,
					$alertConfig['environment'],
					$environment->Name
				));

				// skip to the next alert in the configuration
				continue;
			}

			$contacts = array();
			$paused = false;

			foreach($alertConfig['contacts'] as $contactEmail) {
				// alerts that concern ops are not effective immediately, but paused until ops have approved the alert
				// special case for ops
				if($contactEmail == 'ops') {
					$paused = true;

					// @todo add the ops 24/7 phone number here?
					$contacts[] = array(
						'name' => sprintf('SilverStripe Operations Team <%s>', DEPLOYNAUT_OPS_EMAIL),
						'email' => DEPLOYNAUT_OPS_EMAIL
					);
				} else {
					// this should never return false, as validateAlert() checks that it exists prior
					$contact = $project->AlertContacts()->filter('Email', $contactEmail)->first();

					$contacts[] = array(
						'name' => sprintf('%s <%s>', $contact->Name, $contact->Email),
						'email' => $contact->Email,
						'cellphone' => $contact->SMSCellphone,
						'countrycode' => $contact->SMSCountryCode,
						'countryiso' => $contact->SMSCountryISO
					);
				}
			}

			try {
				$result = $this->gateway->addOrModifyAlert(
					sprintf('%s/dev/check/%s', $environment->URL, $alertConfig['envcheck-suite']),
					$contacts,
					5, // the check interval in minutes
					$paused
				);
			} catch(\Exception $e) {
				$log->write(sprintf('Failed to configure alert "%s" due to alert service API failure %s .', $alertName, $e->getMessage()));
				continue;
			}

			if(!$result) {
				$log->write(sprintf('Failed to configure alert "%s". Error: %s', $alertName, $this->gateway->getLastError()));
				continue;
			}

			if($paused) {
				$log->write(sprintf(
					'Successfully configured alert "%s", but has been disabled pending approval. Please contact SilverStripe Operations Team to have it approved',
					$alertName
				));
			} else {
				$log->write(sprintf('Successfully configured alert "%s"', $alertName));
			}
		}

		return true;
	}

	/**
	 * Validate a specific alert configuration from configuration YAML is correct.
	 *
	 * @param string $name
	 * @param array $config
	 * @param DNProject $project
	 * @param DeploynautLogFile $log
	 * @return boolean
	 */
	public function validateAlert($name, $config, $project, $log) {
		// validate we have an environment set for the alert
		if(!isset($config['environment'])) {
			$log->write(sprintf('ERROR: Misconfigured .alerts.yml. Missing "environment" key for alert "%s".', $name));
			return false;
		}

		// validate we have an environmentcheck suite name to check
		if(!isset($config['envcheck-suite'])) {
			$log->write(sprintf('ERROR: Misconfigured .alerts.yml. Missing "envcheck-suite" key for alert "%s".', $name));
			return false;
		}

		// validate we have contacts for the alert
		if(!isset($config['contacts'])) {
			$log->write(sprintf('ERROR: Misconfigured .alerts.yml. Missing "contacts" key for alert "%s".', $name));
			return false;
		}

		// validate that each value in the config is valid, build up a list of contacts we'll use later
		foreach($config['contacts'] as $contactEmail) {
			// special case for ops
			if($contactEmail == 'ops') continue;

			$contact = $project->AlertContacts()->filter('Email', $contactEmail)->first();
			if(!($contact && $contact->exists())) {
				$log->write(sprintf('ERROR: No such contact "%s" for alert "%s".', $contactEmail, $name));
				return false;
			}
		}

		// validate the environment specified in the alert actually exists
		if(!DNEnvironment::get()->filter('Name', $config['environment'])->first()) {
			$log->write(sprintf('ERROR: Invalid environment "%s" in .alerts.yml.', $config['environment']));
			return false;
		}

		return true;
	}

}
