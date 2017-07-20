<?php

class SyncPingdomAlertsTask extends BuildTask
{
    private static $dependencies = [
        'gateway' => '%$PingdomGateway',
    ];

    private $contacts = [];

    /**
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $projVar = $request->getVar('project');
        if (!$projVar) {
            $this->log('Please pass either a project ID or "all" as the request var "project"');

            return false;
        }

        if ($projVar == 'all') {
            $projects = DNProject::get();
        } elseif (is_numeric($projVar)) {
            $projects = DNProject::get()->filter([
                'ID' => $projVar,
            ]);
            if (!$projects || empty($projects)) {
                $this->log(sprintf('Couldn\'t find project with ID %d', $projVar));

                return false;
            }
        } else {
            $this->log('Invalid "project" request var. Must be int or "all"');

            return false;
        }

        foreach ($projects as $project) {
            $this->ensureTeamRolesAreContacts($project);
            if ($project->isPublic()) {
                $this->ensureUnpausedAlert($project);
            } else {
                $this->ensurePausedAlert($project);
            }
        }

        $this->log(sprintf(' %d production projects checked.', $projects->count()));
    }

    /**
     * @param DNProject $project
     */
    public function ensureUnpausedAlert($project)
    {
        $alert = $project->Alert;
        if (!$alert) {
            $alert = $this->createAndSyncAlert($project);
        }

        $checkID = $alert->PingdomID;
        if ($checkID) {
            $pingdomAlert = $this->gateway->getCheck($checkID);
            if ($pingdomAlert->paused) {
                try {
                    $this->gateway->modifyCheck($checkID, ['paused' => 'false']);
                } catch (\Exception $e) {
                    $this->log(sprintf('Unpausing alert %s failed.', $checkID));
                    $this->log($e->getMessage());
                }
            }
        }
    }

    /**
     * @param DNProject $project
     * @TODO remove old contacts who have been demoted/removed from the team
     */
    public function ensureTeamRolesAreContacts($project)
    {
        $whoToAlert = array_filter($project->listMembers(), function ($teamMember) {
            return $teamMember['RoleTitle'] == 'Stack Manager' || $teamMember['RoleTitle'] == 'Release Manager';
        });
        foreach ($whoToAlert as $member) {
            if (!$project->AlertContacts()->filter('Email', $member['Email'])->first()) {
                $contact = AlertContact::create();
                $contact->attachMember(Member::get()->byID($member['MemberID']));
                $project->AlertContacts()->add($contact);
            }
        }

        foreach ($project->AlertContacts() as $contact) {
            if (in_array($contact->Email, $this->contacts)) {
                continue;
            }
            try {
                $this->gateway->addOrModifyContact([
                    'email' => $contact->Email,
                    'name' => $contact->Name,
                ]);
            } catch (\Exception $e) {
                $this->log(sprintf(
                    'Unable to sync contact %s (%s): ', $contact->Name, $contact->Email
                ));
                $this->log($e->getMessage());
                continue;
            }
            $this->contacts[] = $contact->Email;
            $this->log('Updated contact info for '.$contact->Name);
        }
    }

    /**
     * @param DNEnvironment $environment
     * @param mixed         $paused
     */
    public function createAndSyncAlert($environment, $paused = false)
    {
        $alert = Alert::create($environment);
        try {
            $response = $this->gateway->addOrModifyAlert($alert->URL, $alert->AlertContacts()->toArray(), 5, $paused);
        } catch (\Exception $e) {
            $this->log(sprintf('Updating alert %s failed.', $alert->Name));
            $this->log($e->getMessage());
        }
    }

    /**
     * @param string $string
     */
    protected function log($string)
    {
        if (Director::is_cli()) {
            echo $string."\n";
        } else {
            echo $string.'<br />';
        }
    }
}
