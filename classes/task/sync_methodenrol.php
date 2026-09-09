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
 * Sync WSScol Method Enrolment task.
 *
 * @package local_wsscol_courses
 * @author Serge FELIX <serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsscol_courses\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use text_progress_trace;

require_once(__DIR__ . '/../../lib.php');

class sync_methodenrol extends scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncmethodenroltask', 'local_wsscol_courses');
    }

    /**
     * Run task for syncing enrolments.
     */
    public function execute() {
        global $DB;
        $trace = new text_progress_trace();
        if (!enrol_is_enabled('wsscol')) {
            $trace->output('Plugin not enabled');
            return;
        }
        $trace->output('Processing sync wsscol enrolment method');
        $wsapps = $DB->get_records('local_wsscol_courses_ws_config',array('status'=>TRUE ));
        foreach ($wsapps as $wsapp) {
            local_wsscol_courses_sync_methodenrol($wsapp->id, $trace);
        }
        $trace->finished();
    }

}
