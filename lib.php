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

use local_wsscol_courses\idnumber_info;
/**
 * local_wsscol_courses lib
 *
 * @package local_wsscol_courses
 * @author Serge FELIX<serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns an array of courses matching the given search string, created by this plugin,
 * indexed by username and then by course id.
 *
 * @param string $search Search string matched against the course idnumber (LIKE '%search%')
 * @return array<string, array<int, course_info>> Array of course_info objects, indexed as [$username][$courseid]
 */
function local_wsscol_courses_get_all_courses($search) {
    global $DB;
    $db_courses = $DB->get_records_select('course', $DB->sql_like('idnumber', ':idnum'), ['idnum' => '%' . $search . '%']);
    $courses = array();
    foreach ($db_courses as $db_course) {
        try {
            $course_info = \local_wsscol_courses\course_info::get_from_course_record($db_course);
        } catch (Exception $e) {
            continue;
        }
        $courses[$course_info->username][$db_course->idnumber] = $course_info;
    }
    return $courses;
}

/**
 * sync_groups : sync wsscol instances courses open from wsscol activate_course page.
 *
 * @param int $wsid Application's webservices to sync.
 * @param progress_trace $trace
 * Return
 */
function local_wsscol_courses_sync_methodenrol(int $wsid, progress_trace $trace, $search = NULL) {
    global $DB;

    $ws_record = $DB->get_record('local_wsscol_courses_ws', array('id' => $wsid), '*', MUST_EXIST);
    $trace->output('Gogogo');
    $ws_config = \local_wsscol_courses\ws_config_persistent::from_record($ws_record);
    $enrol_wsscol = enrol_get_plugin('wsscol');
    if (!$ws_config) {
        $trace->output('wsapp with id = '.$wsid.' don\'t exist', 1);
        exit();
    }

    // If sync is utilized with Cli or other things than task. We can restrict the scope.
    $separator = get_config('local_wsscol_courses', 'idnumber_seperator');
    $search = $search ? 'wsscol_'.$wsid.$separator.$search : 'wsscol_'.$wsid;
    // We 've get all courses from db
    $allcoursesdb = local_wsscol_courses_get_all_courses($search);


    // We loop on teacher's usernames.
    foreach ($allcoursesdb as $username => $db_courses) {
        $trace->output('Processing ' . $username . ' courses');

        //

        $user = \core_user::get_user_by_username($username);
        $courses_info = \local_wsscol_courses\ws_service::getinstance($wsid)->get_courses_teacher($user->id);

        // We loop on courses created by the teacher.
        foreach ($db_courses as $db_course_idnumber => $db_course) {
            $trace->output('Processing courseid : ' . $db_course_idnumber . ' codescol : ' . $db_course->shortname, 1);

            // We build an array of wsscol enrolment method (wems) expected from sebService

            $wems = array();
            if (!array_key_exists($db_course_idnumber,$courses_info)) {
                $trace->output('not found',2);
                continue;
            } else {
                $course_info = $courses_info[$db_course_idnumber];
            }
            foreach ($course_info->enrols as $enrol) {
                $wems[$enrol->enrol_wsid] = array();
                foreach ($enrol->enrol_code as $code) {
                    $wems[$enrol->enrol_wsid][$code] = $code;
                }
            }

            $course_record = $DB->get_record('course', ['idnumber' => $db_course_idnumber], '*', MUST_EXIST);

            $instances = enrol_get_instances($course_record->id, false);
            foreach ($instances as $instance) {

                if ($instance->enrol == 'wsscol' && $instance->customint3 == enrol_wsscol_plugin::INSTANCE_ETAT_AUTO) {

                    // If instances of type wsid exist in $wems (so from our ws_agent).
                    if (key_exists($instance->customint2,$wems)) {
                        // If group exist in $wems (so from our ws_agent).
                        if (key_exists($instance->customchar1, $wems[$instance->customint2])) {
                            // But instance is desactivated in Moodle => we re-activate.
                            if ($instance->status == ENROL_INSTANCE_DISABLED) {
                                $dataupdate = new stdClass();
                                $dataupdate->status = ENROL_INSTANCE_ENABLED;
                                $enrol_wsscol->update_instance($instance, $dataupdate);
                                $trace->output('wsid: '.$instance->customchar1.' '.$instance->customchar1 . ' reactivated', 2);
                            }
                            // We unindex $wems record.
                            // At last, we must have only wem to create for $wems.
                            unset ($wems[$instance->customint2][$instance->customchar1]);
                            // If group doestn't exist in $wems.
                        } else {
                            // And it always active in Moodle => we desactivate it.
                            if ($instance->status == ENROL_INSTANCE_ENABLED) {
                                $dataupdate = new stdClass();
                                $dataupdate->status = ENROL_INSTANCE_DISABLED;
                                $enrol_wsscol->update_instance($instance, $dataupdate);
                                $trace->output('wsid: '.$instance->customchar1.' '.$instance->customchar1 . ' desactivated', 2);
                            }
                        }
                    }
                }
            }
            // We create wem that stay in $wems.
            foreach ($wems as $wsid=>$wem) {
                foreach ($wem as $code) {
                    $course_objet = get_course($course_record->id);

                    $datas_wsscol = $enrol_wsscol->get_instance_fields(
                        $code,
                        $username,
                        $wsid,
                        enrol_wsscol_plugin::INSTANCE_ETAT_AUTO
                    );

                    $instanceid = $enrol_wsscol->add_instance($course_objet, $datas_wsscol);
                    $trace->output('wsid: '.$instance->customchar1.' '.$instance->customchar1 . ' created', 2);
                }
            }
        }
    }
    $trace->finished();
}

/** manual enrol a user
 *
 * @param int $courseid
 * @param string $username
 * @param int $roleid
 * @return boolean true if user's enrolled
 */
function local_wsscol_course_manual_enrol_user(int $courseid, string $username, int $roleid) {
    global $DB;
    $user = $DB->get_record('user', array('username' => $username, 'deleted' => 0), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $enrol = enrol_get_plugin('manual');
    $instances = enrol_get_instances($courseid, true);
    $manualinstance = null;
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
            break;
        }
    }
    if ($manualinstance === null) {
        $instanceid = $enrol->add_default_instance($course);
        $manualinstance = $DB->get_record('enrol', array('id' => $instanceid));
    }
    $enrol->enrol_user($manualinstance, $user->id, $roleid);
    return true;
}

/**
 * Groups parallel arrays from repeat_elements into stdClass objects.
 *
 * The first element in $elements is used as the array key.
 *
 * @param stdClass $flat Object containing the parallel arrays, for example the result of get_data().
 * @param array $elements Property names to group.
 * @return stdClass[] Associative array of stdClass objects keyed by the value of the first property.
 */
function local_wsscol_courses_get_repeated_elements(stdClass $flat, array $elements): array {
    $data = [];
    $keyfield = $elements[0];
    $keys = $flat->$keyfield ?? [];
    foreach ($keys as $i => $keyvalue) {
        $item = new stdClass();
        foreach ($elements as $element) {
            $item->$element = $flat->$element[$i] ?? null;
        }
        $data[$i] = $item;
    }
    return $data;
}