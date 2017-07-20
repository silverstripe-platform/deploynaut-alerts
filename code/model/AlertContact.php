<?php

class AlertContact extends DataObject
{
    private static $db = [
        'Name' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'SMSCountryCode' => 'Varchar(2)',
        'SMSCellphone' => 'Varchar(100)',
        'SMSCountryISO' => 'Varchar(2)',
    ];

    private static $has_one = [
        'Project' => 'DNProject',
        'Member' => 'Member',
    ];

    private static $summary_fields = [
        'Name' => 'Name',
        'Email' => 'Email',
        'MemberID' => 'Member ID',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->dataFieldByName('SMSCountryCode')->setDescription('Country code, e.g. 64 for New Zealand');
        $fields->dataFieldByName('SMSCellphone')->setDescription('Phone number without country code and leading zero, e.g. 21123456');
        $fields->dataFieldByName('SMSCountryISO')->setDescription('Country ISO code, e.g. NZ for New Zealand');

        return $fields;
    }

    /**
     * @param Member $member
     */
    public function attachMember($member)
    {
        $this->Member = $member;
        $this->Name = $member->Name;
        $this->Email = $member->Email;
        $this->SMSCountryCode = $member->SMSCountryCode;
        $this->SMSCellphone = $member->SMSCellphone;
        $this->SMSCountryISO = $member->SMSCountryISO;
    }
}
