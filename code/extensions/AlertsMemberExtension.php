<?php

class AlertsMemberExtension extends DataExtension
{
    private static $has_many = [
        'AlertContacts' => 'AlertContact',
    ];

    /**
     * @param FieldList $fields
     *
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->dataFieldByName('SMSCountryCode')->setDescription('Country code, e.g. 64 for New Zealand');
        $fields->dataFieldByName('SMSCellphone')->setDescription('Phone number without country code and leading zero, e.g. 21123456');
        $fields->dataFieldByName('SMSCountryISO')->setDescription('Country ISO code, e.g. NZ for New Zealand');

        return $fields;
    }
}
