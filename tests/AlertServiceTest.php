<?php
class AlertServiceTest extends DeploynautTest {

	protected $log = null;

	protected $gateway = null;

	static $fixture_file = 'AlertServiceTest.yml';

	public function setUp() {
		parent::setUp();

		$api = $this->getMock(
			'\Acquia\Pingdom\PingdomApi',
			['request'], // only mock the request method
			["user@test.com", "password", "token"] // constructor arguments
		);

		$this->gateway = $this->getMock('PingdomGateway');
		$this->gateway->setClient($api);

		$logFile = 'test-logs';
		touch(DEPLOYNAUT_LOG_PATH . '/' . $logFile);
		file_put_contents(DEPLOYNAUT_LOG_PATH . '/' . $logFile, '');
		$this->log = new DeploynautLogFile($logFile);
	}

	public function testBrokenConfig() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('SpyAlertServiceMissingEnvironmentConfig');
		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertContains('WARNING: Failed to configure alert "dev-check". Missing "environment" key in .alerts.yml. Skipped.', $this->log->content());
	}

	public function testMissingAlertContact() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('SpyAlertServiceInvalidAlertContactConfig');
		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertContains('WARNING: Failed to configure alert "dev-check". No such contact "nonexistant-contact@email.com". Skipped.', $this->log->content());
	}

	public function testSkipAlertsForNonApplicableEnvironment() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-uat');

		$this->gateway->expects($this->once())
			->method('addOrModifyAlert')
			->with('http://mysite-uat.com/dev/check/check', [
				['name' => 'Joe Bloggs <joe@email.com>', 'email' => 'joe@email.com', 'cellphone' => '21123456', 'countrycode' => '64', 'countryiso' => 'NZ'],
			], 5, false)
			->will($this->returnValue(true));

		$service = Injector::inst()->create('SpyAlertServiceGoodConfig');
		$service->setGateway($this->gateway);

		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertNotContains('Failed to configure alert "dev-check"', $this->log->content());
		$this->assertContains('Failed to configure alert "health-check" for environment "prod". Does not apply to this environment ("uat")', $this->log->content());
		$this->assertContains('Successfully configured alert "dev-check"', $this->log->content());
	}

	public function testGoodConfigConfiguresAlerts() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$this->gateway->expects($this->once())
			->method('addOrModifyAlert')
			->with('http://mysite.com/dev/check/health', [
				['name' => 'Joe Bloggs <joe@email.com>', 'email' => 'joe@email.com', 'cellphone' => '21123456', 'countrycode' => '64', 'countryiso' => 'NZ'],
				['name' => 'Jane Bloggs <jane@email.com>', 'email' => 'jane@email.com', 'cellphone' => null, 'countrycode' => null, 'countryiso' => null],
				['name' => sprintf('SilverStripe Operations Team <%s>', DEPLOYNAUT_OPS_EMAIL), 'email' => DEPLOYNAUT_OPS_EMAIL]
			], 5, true)
			->will($this->returnValue(true));

		$service = Injector::inst()->create('SpyAlertServiceGoodConfig');
		$service->setGateway($this->gateway);

		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertNotContains('Failed to configure alert "health-check"', $this->log->content());
		$this->assertContains('Failed to configure alert "dev-check" for environment "uat". Does not apply to this environment ("prod")', $this->log->content());
		$this->assertContains('Successfully configured alert "health-check". If this is newly configured, the alert will be paused. Please contact SilverStripe Operations Team to have it approved', $this->log->content());
	}

	public function testNoConfig() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('TestAlertService');
		$service->setGateway($this->gateway);

		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertFalse($result);
		$this->assertContains('Skipping alert configuration. No .alerts.yml found in site code', $this->log->content());
	}

	public function testConfigMissingAlerts() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('SpyAlertServiceMissingAlerts');
		$service->setGateway($this->gateway);

		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertFalse($result);
		$this->assertContains('WARNING: Failed to configure alerts. Misconfigured .alerts.yml. Missing "alerts" key.', $this->log->content());
	}

	public function testConfigMalformed() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('SpyAlertServiceMalformedConfig');
		$service->setGateway($this->gateway);

		$result = $service->sync($environment, 'master', $this->log, $project);

		$this->assertFalse($result);
		$this->assertContains('WARNING: Failed to configure alerts. Could not parse .alerts.yml. Unable to parse at line 1 (near "asdkjahr23434564uwerea").', $this->log->content());
	}

}

class TestAlertService extends AlertService {

	public function getAlertsConfigContent($project, $sha) {
		return null;
	}

}

class SpyAlertServiceMalformedConfig extends TestAlertService {

	public function getAlertsConfigContent($project, $sha) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-malformed.yml');
	}

}

class SpyAlertServiceMissingAlerts extends TestAlertService {

	public function getAlertsConfigContent($project, $sha) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-missing.yml');
	}

}

class SpyAlertServiceMissingEnvironmentConfig extends TestAlertService {

	public function getAlertsConfigContent($project, $sha) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-broken-missing-environment.yml');
	}

}

class SpyAlertServiceInvalidAlertContactConfig extends TestAlertService {

	public function getAlertsConfigContent($project, $sha) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-broken-invalid-contacts.yml');
	}

}

class SpyAlertServiceGoodConfig extends TestAlertService {

	public function getAlertsConfigContent($project, $sha) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-good.yml');
	}

}
