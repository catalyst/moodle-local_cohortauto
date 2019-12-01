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
 * CLI script: synchronise cohorts for a single user.
 * @package    local_cohortauto
 * @copyright  2019 Catalyst IT
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/cohortauto/lib.php');

/* Emulate normal user session. This isn't a direct cron script, but assuming
 * the role of an admin user avoids problems with observers in other modules
 * checking permissions and complaining when a bare script doesn't have any.
 * So for the purposes of synchronising users, we're an admin user.
 */
cron_setup_user(get_admin());

$username = cli_input('Synchronise cohorts for username:');

if ($username) {
    if ($user = $DB->get_record('user', array('username' => $username))) {
        $handler = new local_cohortauto_handler();
        $handler->user_profile_hook($user);

        cli_writeln(get_string('cli_user_sync_complete', 'local_cohortauto', $username));
    } else {
        cli_error(get_string('cli_user_sync_notfound', 'local_cohortauto', $username));
    }
}
