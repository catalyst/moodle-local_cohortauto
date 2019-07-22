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
 * This function prepares complete $USER object for manipulation.
 * Strip long strings and reject some keys.
 *
 * @param array $data Complete $USER object with custom profile fields loaded
 * @return array Cleaned array created from $data
 */
function cohortauto_prepare_profile_data($data, $replaceempty = 'EMPTY') {
    $reject = array('ajax_updatable_user_prefs', 'sesskey', 'preference', 'editing', 'access', 'message_lastpopup', 'enrol');
    if (is_array($data) or is_object($data)) {
        $newdata = array();
        foreach ($data as $key => $val) {
            if (!in_array($key, $reject)) {
                if (is_array($val) or is_object($val)) {
                    $newdata[$key] = mcae_prepare_profile_data($val, $replaceempty);
                } else {
                    if ($val === '' or $val === ' ' or $val === null) {
                        $str = ($val === false) ? 'false' : $replaceempty;
                    } else {
                        $str = ($val === true) ? 'true' : format_string("$val");
                    }
                    $newdata[$key] = substr($str, 0, 100);
                }
            }
        }
    } else {
        if ($data === '' or $data === ' ' or $data === null) {
            $str = ($data === false) ? 'false' : $replaceempty;
        } else {
            $str = ($data === true) ? 'true' : format_string("$data");
        }
        $newdata = substr($str, 0, 100);
    }
    if (empty($newdata)) {
        return $replaceempty;
    } else {
        return $newdata;
    }
}

/**
 * This function prepares help section for settings page.
 *
 * @param array $data Result of mcae_prepare_profile_data function
 * @param string $prefix String prefix
 * @param array $result Variable to store result
 */

function cohortauto_print_profile_data($data, $prefix = '', &$result) {
    if (is_array($data)) {
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $field = ($prefix == '') ? "$key" : "$prefix.$key";
                mcae_print_profile_data($val, $field, $result);
            } else {
                $field = ($prefix == '') ? "$key" : "$prefix.$key";
                $title = format_string($val);
                $result[] = "<span title=\"$title\">{{ $field }}</span>";
            }
        }
    } else {
        $title = format_string($data);
        $result[] = "<span title=\"$title\">{{ $prefix }}</span>";
    }
}
