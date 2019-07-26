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

define('NO_OUTPUT_BUFFERING', true); // Needed for progress_bar.

require_once('../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
$returnurl = new moodle_url('/local/cohortauto/convert.php');

admin_externalpage_setup('cohortautotool');

require_capability('moodle/site:config', $context, $USER->id);

// Fetch parameters.
$action = optional_param('action', 'list', PARAM_ALPHA);
$clist = (isset($_POST['clist'])) ? $_POST['clist'] : false;

switch ($action) {
    case 'list':
        $cohorts = $DB->get_records('cohort', array('contextid' => $context->id), 'name ASC');
        $cohortlist = array();

        foreach ($cohorts as $cohort) {
            $cid = $cohort->id;
            $cname = format_string($cohort->name);
            $cohortlist[$cid]['name'] = $cname;
            $cohortlist[$cid]['component'] = $cohort->component;
            $cohortlist[$cid]['count'] = $DB->count_records('cohort_members', array('cohortid' => $cid));
        }

        $row = array();
        $cell = array();
        $rownum = 0;

        foreach ($cohortlist as $key => $val) {
            $viewurl = new moodle_url('/local/cohortauto/view.php', array('cid' => $key));
            $row[$rownum] = new html_table_row();

            switch ($val['component']) {
                case 'local_cohortauto':
                    $row[$rownum]->attributes['class'] = 'cohortauto cohort-managed';
                    break;
                case 'auth_mcae':
                    $row[$rownum]->attributes['class'] = 'cohortauto cohort-deprecated';
                    break;
                default:
                    $row[$rownum]->attributes['class'] = 'cohortauto cohort-unmanaged';
            }

            $cell[1] = new html_table_cell();
            $cell[2] = new html_table_cell();
            $cell[3] = new html_table_cell();
            $cell[4] = new html_table_cell();

            $cell[1]->text = '<input type="checkbox" name="clist[]" value="'.$key.'"> '.$val['name'];
            $cell[2]->text = ($val['component'] === '') ? '&mdash;' : $val['component'];
            $cell[3]->text = $val['count'];
            $cell[4]->text = '<a href="'.$viewurl.'">'.get_string('userlink', 'local_cohortauto').'</a>';

            $row[$rownum]->cells = $cell;
            $rownum++;
        }

        $table = new html_table();
        $table->head = array(
            get_string('heading_cohortname', 'local_cohortauto'),
            get_string('heading_component', 'local_cohortauto'),
            get_string('heading_count', 'local_cohortauto'),
            get_string('heading_link', 'local_cohortauto')
        );
        $table->width = '60%';
        $table->data = $row;

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('label_cohortautotool', 'local_cohortauto'));

        echo get_string('cohortoper_help', 'local_cohortauto');
        echo "<form action=\"{$returnurl}\" method=\"POST\">";

        echo html_writer::table($table);

        echo '<select name="action"><option value="do">' .
             get_string('convert_do', 'local_cohortauto') .
             '</option><option value="restore">' .
             get_string('convert_restore', 'local_cohortauto') .
             '</option><option value="delete">' .
             get_string('convert_delete', 'local_cohortauto') .
             '</option></select>';
        echo '<input type="submit" name="submit" value="Submit">';
        echo '</form>';
        echo $OUTPUT->footer();
    break;
    case 'do':
        if ($clist) {
            list($usql, $params) = $DB->get_in_or_equal($clist);
            $DB->set_field_select('cohort', 'component', 'local_cohortauto', 'id ' . $usql, $params);
        };
        redirect($returnurl);
    break;
    case 'restore':
        if ($clist) {
            list($usql, $params) = $DB->get_in_or_equal($clist);
            $DB->set_field_select('cohort', 'component', '', 'id ' . $usql, $params);
        };
        redirect($returnurl);
    break;
    case 'delete':
        if ($clist) {
            set_time_limit(0);

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('plugindescription', 'local_cohortauto'));

            $progress = new progress_bar('delcohort');
            $progress->create();
            $delcount = count($clist);
            $delcurrent = 1;

            foreach ($clist as $cid) {
                $cohort = $DB->get_record('cohort', array('contextid' => $context->id, 'id' => $cid));
                cohort_delete_cohort($cohort);
                $progress->update($delcurrent, $delcount, "{$delcurrent} / {$delcount}");
                $delcurrent++;
            };
        };
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->footer();
        die();
    break;
    default:
        redirect($returnurl,
            get_string('error_unknown_action', 'local_cohortauto', $action),
            \core\output\notification::NOTIFY_ERROR
        );
}
