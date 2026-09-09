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
    /** @var int */
    private $id;
    /** @var string */
    private $wsname = '';
    /** @var array */
    private $enrols = [];
    /** @var string */
    private $uri = '';
    /** @var string */
    private $local_username = '';
    /** @var array */
    private $path = null;
    /** @var string */
    private $template = null;
    /** @var string */
    private $longname = '';
    /** @var string */
    private $shortname = '';

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
    public function get_id(): int
    {
        return $this->id;
    }

    /**
     * Constructor.
     *
     * @param int $wsid
     * @throws dml_exception
     */
    protected function __construct(int $wsid) {
        global $DB;

        $this->id = $wsid;

        $record = $DB->get_record('local_wsscol_courses_ws', ['id' => $wsid]);
        $ws_config = \local_wsscol_courses\ws_config_persistent::from_record($record);
        $ws_record_flatten = $ws_config->to_form_data();

        if (!$ws_record_flatten) {
            throw new \Exception('No ws_service with id ' . $wsid);
        }

        if (empty($ws_record_flatten->wsname)) {
            throw new \Exception('No name for ws_service with id ' . $wsid);
        }
        $this->wsname = $ws_record_flatten->wsname;

        if (empty($ws_record_flatten->wshost)) {
            throw new \Exception('No wshost for ws_service with id ' . $wsid);
        }
        $this->wshost = $ws_record_flatten->wshost;

        $this->wsuser = $ws_record_flatten->wsuser ?? null;
        $this->wspassword = $ws_record_flatten->wspassword ?? null;

        if (empty($ws_record_flatten->local_username)) {
            throw new \Exception('No local_username field ' . $wsid);
        }
        $this->local_username = $ws_record_flatten->local_username;

        $enrols = local_wsscol_courses_get_repeated_elements(
            $ws_record_flatten,
            enrol_info::get_attribute_names()
        );

        foreach ($enrols as $enrol) {
            if (empty($enrol->enrol_wsid)) {
                throw new \Exception(
                    'No enrol_wsid for ws_service with id ' . $wsid
                );
            }
            $enrol_array = array();
            $enrol_array['enrol_wsid'] = $enrol->enrol_wsid;
            $enrol_array['enrol_code'] = $enrol->enrol_code;
            $this->enrols[] = $enrol_array;
        }

        if (empty($ws_record_flatten->uri)) {
            throw new \Exception(
                'No uri for ws_service with id ' . $wsid
            );
        }
        $this->uri = $ws_record_flatten->uri;

        $this->path = $ws_record_flatten->path ?? null;

        $this->template = $ws_record_flatten->template ?? null;
        if (empty($ws_record_flatten->longname)) {
            throw new \Exception(
                'No longname for ws_service with id ' . $wsid
            );
        }
        $this->longname = $ws_record_flatten->longname;

        if (empty($ws_record_flatten->shortname)) {
            throw new \Exception(
                'No shortname for ws_service with id ' . $wsid
            );
        }
        $this->shortname = $ws_record_flatten->shortname;
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
     * @param string $search Teacher login.
     * @return course_info[]
     */
    public function get_courses_teacher($userid) {
        global $DB;
        $usernamefield = $this->local_username;
        $user = \core_user::get_user($userid);
        $search = $user->$usernamefield;
        $username = $user->username;

        if (!$this->uri) {
            return false;
        }

        $pattern = ['%\[search\]%'];
        $replacement = [rawurlencode($search)];

        // Build the URI using the teacher login.
        // TODO: Allow querying the web service via POST if [search]
        // is not specified in the configured URL.
        $uri = preg_replace($pattern, $replacement, $this->uri);

        // web get some settings from ws_agent database settings
        $record = $DB->get_record('local_wsscol_courses_ws', ['id' => $this->id]);
        //$ws_record = wsscol_flatten_record($record);

        $ws_config = ws_config_persistent::from_record($record);
        $ws_record_flatten = $ws_config->to_form_data();

        // we get the course data from ws_agent
        $courses_teacher = $this->getfromws($uri);
        $courses = [];

        // web construct array of course_info from $courses_teacher and $ws_record
        foreach ($courses_teacher as $course_teacher) {
            $course = new course_info();
            $course->enrols = [];
            foreach ($this->enrols as $key => $enrol) {
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
            if ($course_teacher[$this->path]) {
                if(is_array($course_teacher[$this->path])) {
                    $course->path = $course_teacher[$this->path];
                } else {
                    $course->path = array();
                    $course->path[] = $course_teacher[$this->path];
                }
            } else {
                $course->path = null;
            }

            $course->rootcat_id = $ws_record_flatten->rootcat_id;
            $course->roles = $ws_record_flatten->roles;

            $separator = get_config('local_wsscol_courses', 'idnumber_seperator');
            $suffix = $this->idnumber_suffix ?? '';

            $course->username = $username;

            $searchfields = ['[firstname]', '[lastname]', '[username]'];
            $replace = [$username, $username, $username];
            foreach ($course_teacher as $attr => $value) {
                $searchfields[] = '[' . $attr . ']';
                if (is_array($value)) {
                    $value = implode("-",$value);
                }
                $replace[] = $value;
            }

            $course->template = str_replace(
                $searchfields,
                $replace,
                $this->template
            );

            $course->fullname = str_replace(
                $searchfields,
                $replace,
                $this->longname
            );

            $course->shortname = str_replace(
                $searchfields,
                $replace,
                $this->shortname
            );
            $idnumber_info = new idnumber_info($this->id,$course->shortname,$username,$suffix,$separator);

            $course->idnumber_info = $idnumber_info;

            $course->wsid = $this->id;
            $courses[$idnumber_info->to_idnumber()] = $course;
        }
        return $courses;
    }
    public function get_idnumber() {
        $separator = get_config('local_wsscol_courses', 'idnumber_seperator');
        $suffix = $this->idnumber_suffix ?? '';
        $this->idnumber = 'wsscol_'.$this->wsid . $separator . $this->shortname . $separator . $this->username . $separator . $suffix;
    }
}
