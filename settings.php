<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('local_cicei_snatools_settings', get_string('pluginname', 'local_cicei_snatools'));
    $settings->add(new admin_setting_configcheckbox(
            'local_cicei_snatools/enabled',
            get_string('enable_cicei_snatools', 'local_cicei_snatools'),
            get_string('explainenable_cicei_snatools', 'local_cicei_snatools'),
            0)
    );
    $ADMIN->add('localplugins', $settings);
}
