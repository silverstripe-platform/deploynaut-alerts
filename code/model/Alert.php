<?php

class Alert extends DataObject
{
    private static $default_tag = '';

    private static $default_prefix = '';

    private static $db = [
        'Name' => 'Varchar',
        'URL' => 'Varchar',
        'Paused' => 'Boolean',
        'PingdomID' => 'Int',
    ];

    private static $has_one = [
        'DNProject' => 'Project',
    ];

    private static $has_many = [
        'Contacts' => 'AlertContact',
    ];
}
