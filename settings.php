<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Auto-cohort local plugin for Moodle 3.5+
 * @package    local_cohortauto
 * @copyright  2019 Catalyst IT
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $USER;

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/local/cohortauto/lib.php');

if ($hassiteconfig) { // Needs this condition or there is error on login page.

    // Add view and convert to "Site administration - Users - Accounts" section.
    $ADMIN->add('accounts', new admin_externalpage('cohortautotool',
        get_string('label_cohortautotool', 'local_cohortauto'),
        new moodle_url('/local/cohortauto/convert.php')));

    $ADMIN->add('accounts', new admin_externalpage('cohortautoview',
        get_string('label_cohortautoview', 'local_cohortauto'),
        new moodle_url('/local/cohortauto/view.php')));

    if ($ADMIN->fulltree) {

        $settings = new admin_settingpage('local_cohortauto',
            get_string('pluginname', 'local_cohortauto'));

        // Profile field helper.
        $fldlist = array();
        $usrhelper = get_admin();

        profile_load_data($usrhelper);
        profile_load_custom_fields($usrhelper);
        $fldlist = cohortauto_prepare_profile_data($usrhelper);

        // Additional values for email.
        if (!empty($fldlist['email'])) {
            $fldlist['email'] = array(
                'full' => 'exampleuser@mail.example.com',
                'username' => 'exampleuser',
                'domain' => 'mail.example.com',
                'rootdomain' => 'example.com'
            );
        }

        $helparray = array();
        cohortauto_print_profile_data($fldlist, '', $helparray);

        $helptext = implode(', ', $helparray);
        $settings->add(new admin_setting_heading(
            'local_cohortauto_profile_help',
            get_string('profile_help', 'local_cohortauto'),
            $helptext)
        );
        $settings->add(new admin_setting_configtextarea(
            'local_cohortauto/mainrule_fld',
            get_string('mainrule_fld', 'local_cohortauto'),
            '', '')
        );
        $settings->add(new admin_setting_configselect(
            'local_cohortauto/delim',
            get_string('delim', 'local_cohortauto'),
            get_string('delim_help', 'local_cohortauto'),
            'CR+LF',
            array('CR+LF' => 'CR+LF', 'CR' => 'CR', 'LF' => 'LF'))
        );
        $settings->add(new admin_setting_configtext(
            'local_cohortauto/secondrule_fld',
            get_string('secondrule_fld', 'local_cohortauto'),
            '', 'n/a')
        );
        $settings->add(new admin_setting_configtextarea(
            'local_cohortauto/replace_arr',
            get_string('replace_arr', 'local_cohortauto'),
            '', '')
        );
        $settings->add(new admin_setting_configtextarea(
            'local_cohortauto/donttouchusers',
            get_string('donttouchusers', 'local_cohortauto'),
            '', '')
        );
        $settings->add(new admin_setting_configcheckbox(
            'local_cohortauto/enableunenrol',
            get_string('enableunenrol', 'local_cohortauto'),
            '', 0)
        );
        $ADMIN->add('localplugins', $settings);
    }
}
