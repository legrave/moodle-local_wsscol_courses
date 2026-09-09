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

namespace local_wsscol_courses\form;

use Cassandra\Exception\TruncateException;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class choice_form extends \moodleform {
    public function definition() {
        global $DB;
        global $USER;
        $wsids = $DB->get_fieldset_select(
            'local_wsscol_courses_ws_config',
            'id',
            'config IS NOT NULL AND config <> :empty AND status = :status',
            [
                'empty' => '',
                'status' => 1,
            ]
        );
        $mform = $this->_form;
        $check = false;
        foreach ($wsids as $wsid) {
           $ws_service = \local_wsscol_courses\ws_service::getinstance($wsid);
            $mform->addElement('header',$ws_service->get_wsname(),$ws_service->get_wsname());
            //$content .= ($result) ? html_writer::tag('h2',$result->name) : '';
        }
        $courses =$ws_service->get_courses_teacher($USER->id);
        if ($courses) {
            foreach ($courses as $course) {
                $idnumber = $course->idnumber_info->to_idnumber();
                $search = $DB->get_record('course', array('idnumber' => $idnumber));
                if (empty($search)) {
                    $check = true;
                    $leftlabel = $course->fullname." (".$course->shortname .")";
                    $mform->addElement('advcheckbox',
                        'courses['.$ws_service->get_id().'][' . $course->shortname . ']', // Name.
                        $leftlabel,  // Left label.
                        '', // Right label.
                        [], // Attributes.
                        array(false, serialize($course)));
                }
            }
        }
        if (!$check) {
            $mform->addElement('html',
                    '
<div class="box py-3 generalbox alert alert-danger">' . get_string('nocourses', 'local_wsscol_courses') . '</div>');
        } else {
            $this->add_action_buttons();
        }
    }

    // Custom validation should be added here.
    public function validation($data, $files): array {
        return array();
    }
}
