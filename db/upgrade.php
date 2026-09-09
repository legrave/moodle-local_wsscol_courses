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
 * This file keeps track of upgrades to the wsscol enrolment plugin
 *
 * @package local_wsscol_courses
 * @author Serge FELIX <serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_wsscol_courses_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2026090200) {
        $records = $DB->get_records('local_wsscol_courses_ws');

        foreach ($records as $record) {
            $course = new \local_wsscol_courses\ws_config_persistent();

            $course->id = $record->id;
            $course->wsname = $record->wsname;
            $course->wshost = $record->wshost;
            $course->wsuser = $record->wsuser;
            $course->wspassword = $record->wspassword;
            $course->uri = $record->uri;
            $course->status = $record->status;

            // Migrate the old course configuration.
            $course->shortname = $record->ws_coursesteacher_code1;
            $course->path = $record->ws_coursesteacher_parent;
            $course->libelle = $record->ws_coursesteacher_libelle;
            $course->local_id = $record->ws_local_id;
            $course->rootcat_id = $record->catid;
            $course->roles = json_decode($record->roles, true);
            $course->template = $record->template;
            $course->longname = $record->cc_longname;
            $course->shortname = $record->cc_shortname;
            $course->idnumber_suffix = $record->idnumber_suffix;

            // Migrate the old enrolment configuration.
            if ($record->ws_coursesteacher_enrol_wsid !== null) {
                $course->enrols[] = [
                    'enrol_wsid' => $record->ws_coursesteacher_enrol_wsid,
                    'enrol_code' => $record->ws_coursesteacher_code3,
                ];
            }

            // Generate the new record, including the JSON configuration.
            $newrecord = $course->to_record();

            $DB->update_record('local_wsscol_courses_ws', $newrecord);
        }

        upgrade_block_savepoint(true, 2026090200, 'wsscol');
    }
    return true;
}
