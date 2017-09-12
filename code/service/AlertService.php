<?php
use \Symfony\Component\Process\Process;
use \Symfony\Component\Yaml\Yaml;

class AlertService {

	private static $dependencies = [
		'gateway' => '%$PingdomGateway'
	];

	/**
	 * @var PingdomGateway
	 */
	public $gateway;

	public function setGateway($gateway) {
		$this->gateway = $gateway;
	}

	/**
	 * Output the raw content of the .alerts.yml file from HEAD of a bare repository.
	 * @param DNProject $project
	 * @param string $sha
	 * @return null|string
	 */
	public function getAlertsConfigContent($project, $sha) {
		$command = sprintf('git show --format=raw %s:.alerts.yml', $sha);
		$process = new Process($command, $project->getLocalCVSPath());
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
	 * @param DNEnvironment $environment
	 * @param string $sha
	 * @param DeploynautLogFile $log
	 * @param DNProject $project
	 *
	 * @return bool
	 */
	public function sync($environment, $sha, $log, $project) {
		$content = $this->getAlertsConfigContent($project, $sha);
		if(!$content) {
			$log->write('Skipping alert configuration. No .alerts.yml found in site code.');
			return false;
		}

		try {
			$config = Yaml::parse($content);
		} catch(Symfony\Component\Yaml\Exception\ParseException $e) {
			$log->write(sprintf('WARNING: Failed to configure alerts. Could not parse .alerts.yml. %s', $e->getMessage()));
			return false;
		}

		if(!isset($config['alerts'])) {
			$log->write('WARNING: Failed to configure alerts. Misconfigured .alerts.yml. Missing "alerts" key.');
			return false;
		}

		foreach($config['alerts'] as $alertName => $alertConfig) {
			$valid = $this->validateAlert($alertName, $alertConfig, $project, $log);
			if(!$valid) continue;

			// the alert has an environment that matches the environment we're deploying to now. Configure the alerts.
			if($alertConfig['environment'] == $environment->Code) {
				$log->write(sprintf('Configuring alert "%s" from .alerts.yml', $alertName));
			} else {
				$log->write(sprintf(
					'Failed to configure alert "%s" for environment "%s". Does not apply to this environment ("%s")',
					$alertName,
					$alertConfig['environment'],
					$environment->Code
				));

				// skip to the next alert in the configuration
				continue;
			}

			$contacts = [];
			$paused = false;

			foreach($alertConfig['contacts'] as $contactEmail) {
				// alerts that concern ops are not effective immediately, but paused until ops have approved the alert
				// special case for ops
				if($contactEmail == 'ops') {
					$paused = true;

					// @todo add the ops 24/7 phone number here?
					$contacts[] = [
						'name' => sprintf('SilverStripe Operations Team <%s>', DEPLOYNAUT_OPS_EMAIL),
						'email' => DEPLOYNAUT_OPS_EMAIL
					];
				} else {
					// this should never return false, as validateAlert() checks that it exists prior
					$contact = $project->AlertContacts()->filter('Email', $contactEmail)->first();

					$contacts[] = [
						'name' => sprintf('%s <%s>', $contact->Name, $contact->Email),
						'email' => $contact->Email,
						'cellphone' => $contact->SMSCellphone,
						'countrycode' => $contact->SMSCountryCode,
						'countryiso' => $contact->SMSCountryISO
					];
				}
			}

			try {
				$result = $this->gateway->addOrModifyAlert(
					sprintf('%s/%s', $environment->URL, $alertConfig['check_url']),
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
					'Successfully configured alert "%s". If this is newly configured, the alert will be paused. Please contact SilverStripe Operations Team to have it approved',
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
			$log->write(sprintf(
				'WARNING: Failed to configure alert "%s". Missing "environment" key in .alerts.yml. Skipped.',
				$name
			));
			return false;
		}

		// validate we have an environmentcheck suite name to check
		if(!isset($config['check_url'])) {
			$log->write(sprintf(
				'WARNING: Failed to configure alert "%s". Missing "check_url" key in .alerts.yml. Skipped.',
				$name
			));
			return false;
		}

		// validate we have contacts for the alert
		if(!isset($config['contacts'])) {
			$log->write(sprintf(
				'WARNING: Failed to configure alert "%s". Missing "contacts" key in .alerts.yml. Skipped.',
				$name
			));
			return false;
		}

		// validate that each value in the config is valid, build up a list of contacts we'll use later
		foreach($config['contacts'] as $contactEmail) {
			// special case for ops
			if($contactEmail == 'ops') continue;

			$contact = $project->AlertContacts()->filter('Email', $contactEmail)->first();
			if(!($contact && $contact->exists())) {
				$log->write(sprintf(
					'WARNING: Failed to configure alert "%s". No such contact "%s". Skipped.',
					$name,
					$contactEmail
				));
				return false;
			}
		}

		// validate the environment specified in the alert actually exists
		if(!DNEnvironment::get()->filter('Code', $config['environment'])->first()) {
			$log->write(sprintf(
				'WARNING: Failed to configure alert "%s". Invalid environment "%s" in .alerts.yml. Skipped.',
				$name,
				$config['environment']
			));
			return false;
		}

		return true;
	}

}
