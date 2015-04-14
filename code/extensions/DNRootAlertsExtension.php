<?php
class DNRootAlertsExtension extends Extension {

	private static $allowed_actions = array(
		'alerts',
		'approvealert',
		'AlertApprovalForm'
	);

	private static $dependencies = array(
		'alertService' => '%$AlertService'
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

	public function approvealert(SS_HTTPRequest $request) {
		$project = $this->getCurrentProject();
		if(!$project) {
			return new SS_HTTPResponse("Project '" . Convert::raw2xml($request->latestParam('Project')) . "' not found.", 404);
		}

		return $this->owner->customise(array(
			'Title' => 'Alert approval',
			'Project' => $project,
			'CurrentProject' => $project,
		))->renderWith(array('DNRoot_approvealert', 'DNRoot'));
	}

	public function AlertApprovalForm() {
		$project = $this->getCurrentProject() ?: null;

		return new Form($this->owner, 'AlertApprovalForm', new FieldList(
			new ReadonlyField('ProjectName', 'Project name', $project ? $project->Name : ''),
			new TextField('AlertName', 'Alert name'),
			new TextareaField('Comments'),
			new HiddenField('ProjectID', '', $project ? $project->ID : '')
		), new FieldList(
			new FormAction('doAlertApprovalForm', 'Submit')
		), new RequiredFields(array(
			'ProjectID',
			'AlertName'
		)));
	}

	public function doAlertApprovalForm($data, $form, $request) {
		$project = $this->owner->DNProjectList()->filter('ID', $data['ProjectID'])->first();

		if(!($project && $project->exists())) {
			$form->sessionMessage('Invalid project. Please re-submit.', 'bad');
			return $this->owner->redirectBack();
		}
		if(!defined('DEPLOYNAUT_ALERTS_APPROVE_EMAIL_TO') || !defined('DEPLOYNAUT_ALERTS_APPROVE_EMAIL_FROM')) {
			$form->sessionMessage('This form has not been configured yet. Please try again later.', 'bad');
			return $this->owner->redirectBack();
		}

		$email = new Email();
		$email->setFrom(DEPLOYNAUT_OPS_EMAIL_FROM);
		$email->setTo(DEPLOYNAUT_OPS_EMAIL);
		$email->setSubject('Deploynaut approve alert request');
		$email->setTemplate('ApproveAlertEmail');
		$email->populateTemplate($data);
		$email->populateTemplate(array('Submitter' => Member::currentUser(), 'Project' => $project));
		$email->populateTemplate(array('ProjectAlertsLink' => sprintf('%s/naut/project/%s/alerts', BASE_URL, $project->Name)));
		$email->send();

		$form->sessionMessage('Thank you, your request has been successfully submitted.', 'good');

		return $this->owner->redirectBack();
	}

	public function AlertsConfigContent() {
		return $this->alertService->getAlertsConfigContent($this->getCurrentProject());
	}

}
