<?php

class DNEnvironmentAlertsExtension extends DataExtension
{
    private static $has_one = [
        'Alert' => 'Alert',
    ];
}
