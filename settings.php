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
 * wsscol_course enrolment plugin settings and presets.
 *
 * @package local_wsscol_courses
 * @author Serge FELIX<serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $DB;

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_wsscol_courses',
        get_string('pluginname', 'local_wsscol_courses'));
        $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext('local_wsscol_courses/idnumber_seperator',
            get_string('idnumber_seperator', 'local_wsscol_courses'), get_string('idnumber_seperator_desc', 'local_wsscol_courses'), '/', PARAM_RAW,
            1));
        // Webservice params.
        $settings->add(new admin_setting_heading('local_wsscol_courses_webservices',
            get_string('webservices_title', 'local_wsscol_courses'), get_string('webservices_desc', 'local_wsscol_courses')));

        $link = '<a href="' . $CFG->wwwroot . '/local/wsscol_courses/wsmanage.php">' . get_string('wsmanage_link', 'local_wsscol_courses') . '</a>';
        $settings->add(new admin_setting_heading('local_wsscol_courses_addheading', '', $link));
    }
}
