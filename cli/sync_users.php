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
 * CLI script: synchronise cohorts for all users.
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

$handler = new local_cohortauto_handler();
$users = $DB->get_recordset('user', array('deleted' => 0));
// DB caching means the repeat query for counting is very low cost.
$usercount = $DB->count_records('user', array('deleted' => 0));

cli_writeln(get_string('cli_sync_users_begin', 'local_cohortauto'));

$transaction = $DB->start_delegated_transaction();

foreach ($users as $user) {
    $username = $user->username;
    cli_write(get_string('cli_sync_users_userstart', 'local_cohortauto', $username));
    $handler->user_profile_hook($user);
    cli_writeln(get_string('cli_sync_users_userdone', 'local_cohortauto'));
}
$users->close();

$transaction->allow_commit();
cli_writeln(get_string('cli_sync_users_finished', 'local_cohortauto', $usercount));
