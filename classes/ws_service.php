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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_wsscol_courses;

defined('MOODLE_INTERNAL') || die();



/**
 * REST wscourse ws_agent client
 *
 * @package local_wsscol_courses
 * @author Serge FELIX<serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class ws_service extends ws_agent {
    /** @var self[] */
    private static $instance = [];
    /** @var string */
    private $wsname = '';
    /** @var array */

    /**
     * @return string
     */
    public function get_wsname(): string
    {
        return $this->wsname;
    }

    /**
     * @return int
     */
    public function get_wsid(): int
    {
        return $this->wsid;
    }

    /**
     * Constructor.
     *
     * @param int $wsid
     * @throws dml_exception
     */
    protected function __construct(int $wsid)
    {
        global $DB;

        $this->wsid = $wsid;

        $record = $DB->get_record('local_wsscol_courses_ws_config', ['wsid' => $wsid]);
        $ws_config = \local_wsscol_courses\ws_config_persistent::from_record($record);
        $ws_record_flatten = $ws_config->to_form_data();

        if (!$ws_record_flatten) {
            throw new \Exception('No ws_service with wsid ' . $wsid);
        }

        if (empty($ws_record_flatten->wsname)) {
            throw new \Exception('No name for ws_service with wsid ' . $wsid);
        }
        $this->wsname = $ws_record_flatten->wsname;

        if (empty($ws_record_flatten->wshost)) {
            throw new \Exception('No wshost for ws_service with wsid ' . $wsid);
        }
        $this->wshost = $ws_record_flatten->wshost;

        $this->wsuser = $ws_record_flatten->wsuser ?? null;
        $this->wspassword = $ws_record_flatten->wspassword ?? null;

        if (empty($ws_record_flatten->wsuri)) {
            throw new \Exception(
                'No uri for ws_service with wsid ' . $wsid
            );
        }
        $this->wsuri = $ws_record_flatten->wsuri;
    }

    /**
     * Get an instance of the web service configuration.
     *
     * Instances are cached by web service ID.
     *
     * @param int $wsid
     * @return ws_agent
     * @throws dml_exception
     */
    public static function getinstance(int $wsid): ws_agent
    {
        if (!isset(self::$instance[$wsid])) {
            self::$instance[$wsid] = new self($wsid);
        }
        return self::$instance[$wsid];
    }


    /**
     * Get courses from the web service for a teacher.
     *
     * The web service must return one record for each Moodle course.
     *
     * @param int $userid Teacher userid.
     * @return course_info[]
     */
    public function get_courses_teacher($userid) {
        global $DB;

        // First, we get wsid settings from database
        $record = $DB->get_record('local_wsscol_courses_ws_config', ['wsid' => $this->wsid]);
        $ws_config = ws_config_persistent::from_record($record);

        $usernamefield = $ws_config->local_username;
        $user = \core_user::get_user($userid);
        $search = $user->$usernamefield;
        $username = $user->username;

        if (!$this->wsuri) {
            return false;
        }

        $pattern = ['%\[search\]%'];
        $replacement = [rawurlencode($search)];

        // Build the URI using the teacher login.
        // TODO: Allow querying the web service via POST if [search]
        // is not specified in the configured URL.
        $uri = preg_replace($pattern, $replacement, $this->wsuri);

        // we get the course data from ws_agent
        $courses_teacher = $this->getfromws($uri);
        $courses = [];

        // web construct array of course_info from $courses_teacher and $ws_record
        foreach ($courses_teacher as $course_teacher) {
            $course = new course_info();
            $course->enrols = [];

            foreach ($ws_config->enrols as $key => $enrol) {
                $course->enrols[$key] = new enrol_info(
                    $enrol['enrol_wsid']
                );
                // Web Service can return just one code or an array of code.
                // But enrol_info->enrol_code is an array.
                if (is_array($course_teacher[$enrol['enrol_code']])) {
                    foreach ($course_teacher[$enrol['enrol_code']] as $code) {
                        $course->enrols[$key]->add_enrol_code($code);
                    }
                } else {
                    $course->enrols[$key]->add_enrol_code($course_teacher[$enrol['enrol_code']]);
                }
            }
            if ($course_teacher[$ws_config->path]) {
                if(is_array($course_teacher[$ws_config->path])) {
                    $course->path = $course_teacher[$ws_config->path];
                } else {
                    $course->path = array();
                    $course->path[] = $course_teacher[$ws_config->path];
                }
            } else {
                $course->path = null;
            }

            $course->rootcat_id = $ws_config->rootcat_id;
            $course->roles = $ws_config->roles;

            $separator = get_config('local_wsscol_courses', 'idnumber_seperator');
            $suffix = $ws_config->idnumber_suffix ?? '';

            $course->username = $username;

            $searchfields = ['[firstname]', '[lastname]', '[username]'];
            $replace = [$user->firstname, $user->lastname, $username];
            foreach ($course_teacher as $attr => $value) {
                $searchfields[] = '[' . $attr . ']';
                if (is_array($value)) {
                    $value = implode("-",$value);
                }
                $replace[] = $value;
            }
            $course->form_label = str_replace(
                $searchfields,
                $replace,
                $ws_config->form_label
            );

            $course->template = str_replace(
                $searchfields,
                $replace,
                $ws_config->template
            );

            $course->fullname = str_replace(
                $searchfields,
                $replace,
                $ws_config->longname
            );

            $course->shortname = str_replace(
                $searchfields,
                $replace,
                $ws_config->shortname
            );
            $idnumber_info = new idnumber_info($this->wsid,$course->shortname,$username,$suffix,$separator);

            $course->idnumber_info = $idnumber_info;

            $course->wsid = $this->wsid;
            $courses[$idnumber_info->to_idnumber()] = $course;
        }
        return $courses;
    }
    public function get_idnumber() {
        // web get some settings from ws_agent database settings
        $record = $DB->get_record('local_wsscol_courses_ws_config', ['wsid' => $this->wsid]);
        $ws_config = ws_config_persistent::from_record($record);

        $separator = get_config('local_wsscol_courses', 'idnumber_seperator');
        $suffix = $ws_config->idnumber_suffix ?? '';
        $this->idnumber = 'wsscol_'.$this->wsid . $separator . $ws_config->shortname . $separator . $ws_config->username . $separator . $suffix;
    }
}
