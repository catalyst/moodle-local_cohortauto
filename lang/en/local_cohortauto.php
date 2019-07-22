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
 * Language strings for auto-cohort plugin.
 * @package    local_cohortauto
 * @copyright  2019 Catalyst IT
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Auto-cohort plugin';

// Admin strings.
$string['label_cohortautotool'] = 'CohortAuto operations';
$string['label_cohortautoview'] = 'CohortAuto viewer';

// View strings.
$string['selectcohort'] = 'Select cohort';
$string['username'] = 'User name';
$string['link'] = 'Link';
$string['emptycohort'] = 'Empty cohort';
$string['viewcohort'] = 'Cohort view';
$string['userprofile'] = 'User profile &gt;&gt;';
$string['total'] = 'Total';

// Settings strings.
$string['mainrule_fld'] = 'Main template (one value per line)';
$string['profile_help'] = 'Available template values';
$string['delim'] = 'New line delimiter';
$string['delim_help'] = 'Different operating use different end of line delimiters.' .
    '<br>This is usually CR+LF for Windows, and LF for Linux/MacOS systems.' .
    '<br>If the plugin does not work with the current setting, experiment with other values.';
$string['secondrule_fld'] = 'Empty field text';
$string['replace_arr'] = 'Replace array (one value per line, in the format: old_val|new_val)';
$string['donttouchusers'] = 'Ignore users';
$string['donttouchusers_help'] = 'Comma-separated usernames.';
$string['enableunenrol'] = 'Enable automatic unenrol';

// Privacy API strings.
$string['privacy:metadata'] = 'Auto-cohort plugin does not store any user data.';
