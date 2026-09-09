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
 * page to manage webservices
 *
 * @package local_wsscol_courses
 * @author Serge FELIX <serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once('lib.php');
require("$CFG->libdir/tablelib.php");

require_login();

$wsid = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 0, PARAM_TEXT);
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissiontoviewpage');
}
$managewsurl = new moodle_url('/local/wsscol_courses/wsmanage.php');
$editwsurl = new moodle_url('/local/wsscol_courses/wsedit.php');

$pluginurl = new moodle_url('/admin/settings.php', array('section' => 'ettingwsscol'));
$download = optional_param('download', '', PARAM_ALPHA);
$records_course_ws = $DB->get_records('local_wsscol_courses_ws_config');

$PAGE->set_context(context_system::instance());
$PAGE->set_url($managewsurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('wsmanage_title', 'local_wsscol_courses'));
$PAGE->set_heading(get_string('wsmanage_title', 'local_wsscol_courses'));

$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/' . $CFG->admin));
$PAGE->navbar->add(get_string('pluginname', 'local_wsscol_courses'), $pluginurl);
$PAGE->navbar->add(get_string('wsmanage_title', 'local_wsscol_courses'));

echo $OUTPUT->header();

// Process any actions.
if ($action and confirm_sesskey() and $wsid) {
   $ws_service_object = new stdClass();
   $ws_service_object->id = intval($wsid);
    switch ($action) {
        case 'delete':
            $DB->delete_records('local_wsscol_courses_ws_config', array('id' => $wsid));
            redirect($PAGE->url, get_string('wsappdeleted', 'local_wsscol_courses'));
            break;
        case 'enable':
           $ws_service_object->status = 1;
            $DB->update_record('local_wsscol_courses_ws_config',$ws_service_object);
            redirect($PAGE->url, get_string('wsappenable', 'local_wsscol_courses'));
            break;
        case 'disable':
           $ws_service_object->status = 0;
            $DB->update_record('local_wsscol_courses_ws_config',$ws_service_object);
            redirect($PAGE->url, get_string('wsappdisable', 'local_wsscol_courses'));
            break;
    }
}

$table = new flexible_table('id');
$table->define_columns(array('id', 'wsname',
        get_string('actions', 'moodle')));
$table->define_headers(array('id', 'wsname',
        get_string('actions', 'moodle')));
$table->define_baseurl($managewsurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'wsapps');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('feed', 'ws_courses');
$table->column_class('actions', 'actions');

$table->setup();
foreach ($records_course_ws as $record_course_ws) {
    $classname = '';
    // Deal with edit action.
    $editinstanceurl = clone $editwsurl;
    $editinstanceurl->param('id', $record_course_ws->id);
    $editinstanceurl->param('sesskey', sesskey());
    $edit[] = $OUTPUT->action_icon($editinstanceurl, new pix_icon('t/edit', get_string('edit')));

    // Deal with activating action.
    $activeinstanceurl = clone $managewsurl;
    $activeinstanceurl->param('id', $record_course_ws->id);
    $activeinstanceurl->param('sesskey', sesskey());
    if ($record_course_ws->status) {
        $activeinstanceurl->param('action', 'disable');
        $edit[] =
                $OUTPUT->action_icon($activeinstanceurl, new pix_icon('t/hide', 'disable', 'core', array('class' => 'iconsmall')));
    } else {
        $activeinstanceurl->param('action', 'enable');
        $classname = 'dimmed_text';
        $edit[] = $OUTPUT->action_icon($activeinstanceurl, new pix_icon('t/show', 'enable', 'core', array('class' => 'iconsmall')));
    }
    // Deal with deleting action.
    $deleteinstanceurl = clone $managewsurl;
    $deleteinstanceurl->param('id', $record_course_ws->id);
    $deleteinstanceurl->param('sesskey', sesskey());
    $deleteinstanceurl->param('action', 'disable');
    $edit[] = $OUTPUT->action_icon($deleteinstanceurl, new pix_icon('t/delete', get_string('delete')),
            new confirm_action(get_string('confirm_delete_ws', 'enrol_wsscol')));
    $table->add_data(array($record_course_ws->id, $record_course_ws->wsname, implode('', $edit)),$classname);
    unset ($edit);
}

$table->finish_output();

echo '<div class="actionbuttons">' . $OUTPUT->single_button($editwsurl, get_string('add_ws_button', 'local_wsscol_courses'), 'get') .
        '</div>';
