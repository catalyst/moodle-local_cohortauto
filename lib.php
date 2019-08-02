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

require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * This function prepares complete $USER object for manipulation.
 * Strip long strings and reject some keys.
 *
 * @author       2011 Andrew "Kama" (kamasutra12@yandex.ru) in auth_mcae
 * @param array  $data Complete $USER object with custom profile fields loaded
 * @param string $replaceempty Placeholder value to use for empty entries.
 * @return array Cleaned array created from $data
 */
function cohortauto_prepare_profile_data($data, $replaceempty = 'EMPTY') {
    $reject = array('ajax_updatable_user_prefs', 'sesskey', 'preference', 'editing', 'access', 'message_lastpopup', 'enrol');
    if (is_array($data) or is_object($data)) {
        $newdata = array();
        foreach ($data as $key => $val) {
            if (!in_array($key, $reject)) {
                if (is_array($val) or is_object($val)) {
                    $newdata[$key] = cohortauto_prepare_profile_data($val, $replaceempty);
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
 * @author       2011 Andrew "Kama" (kamasutra12@yandex.ru) in auth_mcae
 * @param array  $data Result of cohortauto_prepare_profile_data function
 * @param string $prefix String prefix
 * @param array  $result Variable to store result
 */
function cohortauto_print_profile_data($data, $prefix = '', &$result) {
    if (is_array($data)) {
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $field = ($prefix == '') ? "$key" : "$prefix.$key";
                cohortauto_print_profile_data($val, $field, $result);
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

/**
 * Event handler class for user profile information changes.
 *
 * This is usually triggered by user profile update events, but can also be
 * triggered by CLI scripts.
 *
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cohortauto_handler {
    /**
     * The component name of the plugin.
     *
     * Used in setting config, and in the cohort table to specify the
     * managing component.
     */
    const COMPONENT_NAME = 'local_cohortauto';

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/mustache/src/Mustache/Autoloader.php');

        $this->config = get_config(self::COMPONENT_NAME);
        Mustache_Autoloader::register();

        $this->mustache = new Mustache_Engine;
    }

    /**
     * Processes and stores configuration data for this plugin.
     * $this->config->somefield
     *
     * @author 2011 Andrew "Kama" (kamasutra12@yandex.ru) in auth_mcae
     * @param object $config The configuration object.
     */
    public function process_config($config) {
        // Set to defaults if undefined.

        if (!isset($config->mainrule_fld)) {
            $config->mainrule_fld = '';
        }
        if (!isset($config->secondrule_fld)) {
            $config->secondrule_fld = 'n/a';
        }
        if (!isset($config->replace_arr)) {
            $config->replace_arr = '';
        }
        if (!isset($config->delim)) {
            $config->delim = 'CR+LF';
        }
        if (!isset($config->donttouchusers)) {
            $config->donttouchusers = '';
        }
        if (!isset($config->enableunenrol)) {
            $config->enableunenrol = 0;
        }
        // Save settings.
        set_config('mainrule_fld',   $config->mainrule_fld,   self::COMPONENT_NAME);
        set_config('secondrule_fld', $config->secondrule_fld, self::COMPONENT_NAME);
        set_config('replace_arr',    $config->replace_arr,    self::COMPONENT_NAME);
        set_config('delim',          $config->delim,          self::COMPONENT_NAME);
        set_config('donttouchusers', $config->donttouchusers, self::COMPONENT_NAME);
        set_config('enableunenrol',  $config->enableunenrol,  self::COMPONENT_NAME);

        return true;
    }

    /**
     * Hook for processing user profile changes.
     *
     * This method is called on user_created and user_updated events, where
     * changes to user profiles may effect changes in cohort membership.
     *
     * Derived from auth.php in auth_mcae.
     *
     * @author              David Thompson <david.thompson@catalyst.net.nz>
     * @param object $user  The user whose profile needs to be inspected
     */
    public function user_profile_hook(&$user) {
        global $DB;

        $context = context_system::instance();
        $uid = $user->id;
        // Ignore users from don't_touch list.
        $ignore = explode(",", $this->config->donttouchusers);

        // Skip explicitly ignored users.
        if (!empty($ignore) AND array_search($user->username, $ignore) !== false) {
            return;
        };

        // Ignore guests.
        if (isguestuser($user)) {
            return;
        };

        // Get cohorts.
        $params = array(
            'contextid' => $context->id,
        );
        if ($this->config->enableunenrol == 1) {
            $params['component'] = self::COMPONENT_NAME;
        };

        $cohorts = $DB->get_records('cohort', $params);

        $cohortslist = array();
        foreach ($cohorts as $cohort) {
            $cohortslist[$cohort->id] = format_string($cohort->name);;
        }

        // Get advanced user data.
        profile_load_data($user);
        profile_load_custom_fields($user);
        $userprofiledata = cohortauto_prepare_profile_data($user, $this->config->secondrule_fld);

        // Additional values for email.
        list($emailusername, $emaildomain) = explode("@", $userprofiledata['email']);

        // Email root domain.
        $emaildomainarray = explode('.', $emaildomain);
        if (count($emaildomainarray) > 2) {
            $emailrootdomain = $emaildomainarray[count($emaildomainarray) - 2].'.'.
                               $emaildomainarray[count($emaildomainarray) - 1];
        } else {
            $emailrootdomain = $emaildomain;
        }
        $userprofiledata['email'] = array(
            'full' => $userprofiledata['email'],
            'username' => $emailusername,
            'domain' => $emaildomain,
            'rootdomain' => $emailrootdomain
        );

        // Set delimiter in use.
        $delimiter = $this->config->delim;
        $delim = strtr($delimiter, array('CR+LF' => chr(13).chr(10), 'CR' => chr(13), 'LF' => chr(10)));

        // Calculate cohort names for user.
        $replacementstemplate = $this->config->replace_arr;

        $replacements = array();
        if (!empty($replacementstemplate)) {
            $replacementsarray = explode($delim, $replacementstemplate);
            foreach ($replacementsarray as $replacement) {
                list($key, $val) = explode("|", $replacement);
                $replacements[$key] = $val;
            };
        };

        // Generate cohort array.
        $mainrule = $this->config->mainrule_fld;
        $mainrulearray = array();
        $templates = array();
        if (!empty($mainrule)) {
            $mainrulearray = explode($delim, $mainrule);
        } else {
            return; // Empty mainrule; no further processing to do.
        };

        // Find %split function.
        foreach ($mainrulearray as $item) {
            if (preg_match('/(?<full>%split\((?<fld>\w*)\|(?<delim>.{1,5})\))/', $item, $splitparams)) {
                // Split!
                $parts = explode($splitparams['delim'], $userprofiledata[$splitparams['fld']]);
                foreach ($parts as $key => $val) {
                    $userprofiledata[$splitparams['fld']."_$key"] = $val;
                    $templates[] = strtr($item, array("{$splitparams['full']}" => "{{ ".$splitparams['fld']."_$key }}"));
                }
            } else {
                $templates[] = $item;
            }
        }

        $processed = array();

        // Apply templates and process the user's cohort memberships.
        foreach ($templates as $cohort) {
            // Transform templates into cohort names with Mustache.
            $cohortname = $this->mustache->render($cohort, $userprofiledata);
            // Apply symbol replacements as necessary.
            $cohortname = (!empty($replacements)) ? strtr($cohortname, $replacements) : $cohortname;

            // Skip empty cohort names. Users with no cohort name should not be assigned.
            if ($cohortname == '') {
                continue;
            };

            $cid = array_search($cohortname, $cohortslist);
            if ($cid !== false) {
                if (!$DB->record_exists('cohort_members', array('cohortid' => $cid, 'userid' => $user->id))) {
                    cohort_add_member($cid, $user->id);
                };
            } else {
                // Cohort with this name does not exist, so create a new one.
                $newcohort = new stdClass();
                $newcohort->name = $cohortname;
                $newcohort->description = "created ".date("d-m-Y");
                $newcohort->contextid = $context->id;
                $newcohort->idnumber = '';
                if ($this->config->enableunenrol == 1) {
                    $newcohort->component = self::COMPONENT_NAME;
                };
                $cid = cohort_add_cohort($newcohort);
                // Add new cohort into the list to avoid creating new ones with same name.
                $cohortslist[$cid] = $cohortname;
                // Add user to the new cohort.
                cohort_add_member($cid, $user->id);

            };
            $processed[] = $cid;
        };

        // Remove users from cohorts if necessary.
        if ($this->config->enableunenrol == 1) {
            // List of cohorts, managed by this plugin, where the user is a member.
            $sql = "SELECT DISTINCT c.id AS cid
                      FROM {cohort} c
                      JOIN {cohort_members} cm ON cm.cohortid = c.id
                    WHERE c.component = :component AND cm.userid = :userid";
            $params = array(
                'component' => self::COMPONENT_NAME,
                'userid' => $uid,
            );
            $incohorts = $DB->get_records_sql($sql, $params);

            foreach ($incohorts as $target) {
                // Remove membership if it no longer matches a processed cohort.
                if (array_search($target->cid, $processed) === false) {
                    cohort_remove_member($target->cid, $uid);
                };
            };
        };
    }

}
