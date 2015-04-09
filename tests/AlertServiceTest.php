<?php
class AlertServiceTest extends SapphireTest {

	protected $log = null;

	protected $mockGateway = null;

	static $fixture_file = 'AlertServiceTest.yml';

	public function setUp() {
		parent::setUp();

		$this->mockGateway = $this->getMock('PingdomGateway');
		$this->mockGateway->expects($this->any())
			->method('addOrModifyAlert')
			->will($this->returnValue(true));

		$logFile = 'test-logs';
		touch(DEPLOYNAUT_LOG_PATH . '/' . $logFile);
		file_put_contents(DEPLOYNAUT_LOG_PATH . '/' . $logFile, '');
		$this->log = new DeploynautLogFile($logFile);
	}

	public function testBrokenConfig() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('SpyAlertServiceMissingEnvironmentConfig');
		$result = $service->sync($project, $environment, $this->log);

		$this->assertFalse($result);
		$this->assertContains('ERROR: Malformed alerts.yml. Missing "environment" key for alert "dev-check"', $this->log->content());
	}

	public function testMissingAlertContact() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$service = Injector::inst()->create('SpyAlertServiceInvalidAlertContactConfig');
		$result = $service->sync($project, $environment, $this->log);

		$this->assertFalse($result);
		$this->assertContains('ERROR: No such contact "nonexistant-contact@email.com" for alert "dev-check"', $this->log->content());
	}

	public function testSkipAlertsForNonApplicableEnvironment() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-uat');

		$this->mockGateway->expects($this->once())
			->method('addOrModifyAlert')
			->with('http://mysite-uat.com/dev/check/check', array(
				array('name' => 'Joe Bloggs', 'email' => 'joe@email.com', 'sms' => null),
			), 5, false)
			->will($this->returnValue(true));

		$service = Injector::inst()->create('SpyAlertServiceGoodConfig');
		$service->setGateway($this->mockGateway);

		$result = $service->sync($project, $environment, $this->log);

		$this->assertTrue($result);

		$this->assertNotContains('Failed to configure alert "dev-check"', $this->log->content());
		$this->assertNotContains('Failed to configure alert "health-check"', $this->log->content());
		$this->assertContains('Skipping alert "health-check" for environment "prod". Does not apply to this environment ("uat")', $this->log->content());
		$this->assertContains('Successfully configured alert "dev-check"', $this->log->content());
	}

	public function testGoodConfigConfiguresAlerts() {
		$project = $this->objFromFixture('DNProject', 'test-project');
		$environment = $this->objFromFixture('DNEnvironment', 'test-environment-prod');

		$this->mockGateway->expects($this->once())
			->method('addOrModifyAlert')
			->with('http://mysite.com/dev/check/health', array(
				array('name' => 'Joe Bloggs', 'email' => 'joe@email.com', 'sms' => null),
				array('name' => 'Jane Bloggs', 'email' => 'jane@email.com', 'sms' => null),
				array('name' => 'SilverStripe Operations Team', 'email' => DEPLOYNAUT_OPS_EMAIL, 'sms' => null)
			), 5, true)
			->will($this->returnValue(true));

		$service = Injector::inst()->create('SpyAlertServiceGoodConfig');
		$service->setGateway($this->mockGateway);
		$result = $service->sync($project, $environment, $this->log);

		$this->assertTrue($result);

		$this->assertNotContains('Failed to configure alert "health-check"', $this->log->content());
		$this->assertNotContains('Failed to configure alert "dev-check"', $this->log->content());
		$this->assertContains('Skipping alert "dev-check" for environment "uat". Does not apply to this environment ("prod")', $this->log->content());
		$this->assertContains('Successfully configured alert "health-check", but has been disabled pending approval. Please contact SilverStripe Operations Team to have it approved', $this->log->content());
	}

}

class TestAlertService extends AlertService {

	public function setGateway($gateway) {
		$this->gateway = $gateway;
	}

}

class SpyAlertServiceMissingEnvironmentConfig extends TestAlertService {

	public function getAlertsConfigContent($project) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-broken-missing-environment.yml');
	}

}

class SpyAlertServiceInvalidAlertContactConfig extends TestAlertService {

	public function getAlertsConfigContent($project) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-broken-invalid-contacts.yml');
	}

}

class SpyAlertServiceGoodConfig extends TestAlertService {

	public function getAlertsConfigContent($project) {
		return file_get_contents(BASE_PATH . '/deploynaut-alerts/tests/alerts-good.yml');
	}

}
