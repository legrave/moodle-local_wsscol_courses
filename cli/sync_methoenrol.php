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
 * CLI update for wsscol enrolments, use for debugging or immediate update
 * of all courses.
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package local_wsscol_courses
 * @author Serge FELIX<serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("../lib.php");

// Now get cli options.
// Now get cli options.
// NOTE: the default value declared here is the value used when the option is
// NOT passed on the command line. It must match the expected TYPE of the
// final value ('' for a string, 0 for an int, false for a flag) - it is not
// a marker meaning "this option requires a value".
list($options, $unrecognized) = cli_get_params(
    [
        'verbose' => false,
        'help'    => false,
        'id'      => 0,
        'search'  => '',
    ],
    [
        'v' => 'verbose',
        'h' => 'help',
        'w' => 'id',
        's' => 'search',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "Execute wsscol method enrolment updates.

Options:
-v, --verbose         Print verbose progress information
-h, --help            Print out this help
-w, --id=ID           Id of ws_agent (required)
-s, --search=STRING   Part of idnumber of courses to sync (optional)

Note: values MUST be attached with '=' (Moodle CLI parser does not support
a space-separated value), e.g. -w=1 not -w 1.

Example:
\$ sudo -u www-data /usr/bin/php enrol/wsscol/cli/sync_methodenrol.php -w=1 -s='toto'
";

    echo $help;
    die;
}

if (!enrol_is_enabled('wsscol')) {
    cli_error('enrol_wsscol plugin is disabled, synchronisation stopped', 2);
}

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}

// Validate id explicitly: 0 or non-numeric is not acceptable.
$id = (int) $options['id'];
if ($id <= 0) {
    cli_error('id is required and must be a positive integer (use -w=ID)', 2);
}

// Normalise search: always a trimmed string, never a boolean.
$search = trim((string) $options['search']);

if ($search !== '') {
    $result = local_wsscol_courses_sync_methodenrol($id, $trace, $search);
} else {
    $result = local_wsscol_courses_sync_methodenrol($id, $trace);
}
//$plugin->send_expiry_notifications($trace);

exit($result);
