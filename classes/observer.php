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

/**
 * User profile event observer class.
 *
 * Delegates profile and cohort processing to lib/local_cohortauto_handler().
 *
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cohortauto_observer {
    /**
     * Observer function to handle the user created event
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/cohortauto/lib.php');
        $eventdata = $event->get_data();
        $handler = new local_cohortauto_handler();
        if ($user = $DB->get_record('user', array('id' => $eventdata['relateduserid']))) {
            $handler->user_profile_hook($user);
        }
    }

    /**
     * Observer function to handle the user updated event
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/cohortauto/lib.php');
        $eventdata = $event->get_data();
        $handler = new local_cohortauto_handler();
        if ($user = $DB->get_record('user', array('id' => $eventdata['relateduserid']))) {
            $handler->user_profile_hook($user);
        }
    }
}
