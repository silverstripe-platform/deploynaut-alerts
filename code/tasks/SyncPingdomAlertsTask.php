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
            $prodEnv = $project->Environments()->filter(
                'Usage', DNEnvironment::PRODUCTION
            )->first();
            if ($prodEnv && $prodEnv->isPublic) {
                $this->ensureUnpausedAlert($prodEnv);
                $this->log(sprintf('%s alert is now unpaused.', $prodEnv->URL));
            } else {
                $this->ensurePausedAlert($prodEnv);
                $this->log(sprintf('%s alert is paused.', $prodEnv->URL));
            }
        }

        $this->log(sprintf(' %d production projects checked.', $projects->count()));
    }

    /**
     * @param DNEnvironment $environment
     */
    public function ensureUnpausedAlert($environment)
    {
        $alertID = $environment->UptimeAlertID ?: $this->createAndSyncAlert($environment);

        if (!$alertID) {
            $this->log('Unable to get Alert ID. Skipping Unpause.');

            return false;
        }
        try {
            $this->gateway->unpauseCheck($alertID);
        } catch (\Exception $e) {
            $this->log(sprintf('Unpausing alert %s failed.', $alertID));
            $this->log($e->getMessage());
        }
    }

    /**
     * @param DNEnvironment $environment
     */
    public function ensurePausedAlert($environment)
    {
        $alertID = $environment->UptimeAlertID ?: $this->createAndSyncAlert($environment);

        if (!$alertID) {
            $this->log('Unable to get Alert ID. Skipping Pause.');

            return false;
        }
        try {
            $this->gateway->pauseCheck($alertID);
        } catch (\Exception $e) {
            $this->log(sprintf('Pausing alert %s failed.', $alertID));
            $this->log($e->getMessage());
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
        try {
            $checkID = $this->gateway->addOrModifyAlert($environment->URL, $this->mapContactsForPingdom($environment), 5, $paused, [Config::inst()->get('Alert', 'default_tag')]);
        } catch (\Exception $e) {
            $this->log($this->gateway->getLastError());
            $this->log(sprintf('Updating alert for %s failed.', $environment->Name));
            $this->log($e->getMessage());

            return null;
        }
        $environment->UptimeAlertID = $checkID;
        $environment->write();

        return $checkID;
    }

    protected function mapContactsForPingdom($environment)
    {
        $contactsArray = [];
        $contacts = $environment->Project()->AlertContacts();
        foreach ($contacts as $contact) {
            $contactsArray[$contact->ID] = [
                'name' => $contact->Name,
                'email' => $contact->Email,
            ];
        }

        return $contactsArray;
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
