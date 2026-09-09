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
 * Activate courses page
 *
 * @package local_wsscol_courses
 * @author Serge FELIX<serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once('lib.php');
require_once("$CFG->dirroot/course/externallib.php");

global $USER;
global $DB;
global $CFG;

require_login();
// Sfx : add a test if enrol_wsscol is needed.
$wsscol = enrol_get_plugin('wsscol');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/wsscol_courses/activate_courses.php'));
$PAGE->set_heading(get_string('open_courses_title', 'local_wsscol_courses'));
$PAGE->set_title(get_string('open_courses_title', 'local_wsscol_courses'));

if (!$USER || !has_capability('local/wsscol_courses:activate_courses', $context)) {
    throw new moodle_exception('nopermissiontoviewpage');
}

$pagedesciption = format_text(get_string('opencourses_page_desciption', 'local_wsscol_courses'));
echo $OUTPUT->header();

$mform = new \local_wsscol_courses\form\choice_form();

if ($datas = $mform->get_data()) {
    $content = '';
    $content .= html_writer::tag('h3', get_string('open_courses_title3', 'local_wsscol_courses'));
    $content .= html_writer::start_tag('ul');

    $allcourses = $datas->courses;
    foreach ($allcourses as $key => $courses) {
        $result = $DB->get_record('local_wsscol_courses_ws_config',array('wsid'=>$key),'wsname');
        $content .= ($result) ? html_writer::tag('h2',$result->wsname) : '';

        foreach ($courses as $key => $value) {
            if ($value) {
                $content .= html_writer::start_tag('li');
                $data = unserialize($value);

                // Override defaults with template course (Thanks Enrol LDAP).
                $template_name = $data->template ?? false;
                if ($template_name) {
                    if ($template = $DB->get_record('course', array('shortname' => $template_name))) {
                        $coursetemplate = fullclone(course_get_format($template)->get_course());
                        unset($coursetemplate->id); // So we are clear to reinsert the record.
                        unset($coursetemplate->fullname);
                        unset($coursetemplate->shortname);
                        unset($coursetemplate->idnumber);
                    }
                }
                if (!isset($coursetemplate)) {
                    $courseconfig = get_config('moodlecourse');
                    $coursetemplate = new stdClass();
                    $coursetemplate->summary = '';
                    $coursetemplate->summaryformat = FORMAT_HTML;
                    $coursetemplate->format = $courseconfig->format;
                    $coursetemplate->newsitems = $courseconfig->newsitems;
                    $coursetemplate->showgrades = $courseconfig->showgrades;
                    $coursetemplate->showreports = $courseconfig->showreports;
                    $coursetemplate->maxbytes = $courseconfig->maxbytes;
                    $coursetemplate->groupmode = $courseconfig->groupmode;
                    $coursetemplate->groupmodeforce = $courseconfig->groupmodeforce;
                    $coursetemplate->visible = $courseconfig->visible;
                    $coursetemplate->lang = $courseconfig->lang;
                    $coursetemplate->enablecompletion = $courseconfig->enablecompletion;
                }


                if ($data->path) {
                    $path = array();
                    if (is_string($data->path)) {
                        $path[] = $data->path;
                    } elseif (is_array($data->path)) {
                        $path = $data->path;
                    }
                    $cat_id = $data->rootcat_id;
                    foreach ($path as $cat) {
                        $parent_id = $cat_id;
                        $catstrucsql = $DB->get_record('course_categories', array('idnumber' => 'wscol_' . $cat), 'id');
                        if ($catstrucsql) {
                            $cat_id = $catstrucsql->id;
                        } else {
                            $categorydata = new stdClass();
                            $categorydata->name = $cat;
                            $categorydata->idnumber = 'wscol_' . $cat;
                            $categorydata->parent = $parent_id;
                            $cat_id = core_course_category::create($categorydata)->id;
                        }
                    }
                }

                $course = $coursetemplate;
                if ($data->idnumber_info instanceof \local_wsscol_courses\idnumber_info) {
                    $course->idnumber = $data->idnumber_info->to_idnumber();
                } else {
                    continue;
                }
                $course->category = $cat_id;
                $course->fullname = $data->fullname;
                $course->shortname = $data->shortname;
                $courseobj = create_course($course);
                if (!empty($data->roles)) {
                    foreach ($data->roles as $role) {
                        local_wsscol_course_manual_enrol_user($courseobj->id, $USER->username, intval($role));
                    }
                }
                // Todo with multiple enrolws

                if (!empty ($data->enrols)) {
                    foreach ($data->enrols as $enrol) {
                        foreach ($enrol->enrol_code as $code) {
                            $datawscol = $wsscol->get_instance_fields(
                                $code,
                                $USER->username,
                                intval($enrol->enrol_wsid),
                                enrol_wsscol_plugin::INSTANCE_ETAT_AUTO
                            );
                            $wsscol->add_instance($courseobj, $datawscol);
                        }
                    }
                }

                $coursecontext = context_course::instance($course->id);
                $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                $content .= html_writer::link(new moodle_url ('/course/view.php', ['id' => $course->id]),
                        format_string($course->fullname));
                $content .= html_writer::end_tag('li');
            }
        }
    }
    $content .= html_writer::end_tag('ul');
    print $content;
} else {
    $content = '';
    $content .= html_writer::tag('h3', get_string('open_courses_title2', 'local_wsscol_courses'));
    print $content;
    $mform->display();
    print  $pagedesciption;
}

echo $OUTPUT->footer();







