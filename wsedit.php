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
 * page to edit webservices settings
 *
 * @package local_wsscol_courses
 * @author Serge FELIX <serge.felix@entpe.fr>
 * @copyright Université Lumière Lyon 2  {@link http://www.entpe.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/course/externallib.php");

class wsedit_form extends moodleform {
    protected $isadding;

    public function __construct($actionurl, $isadding, $customdata = null) {
        $this->isadding = $isadding;
        parent::__construct($actionurl, $customdata);
    }

    public function definition() {
        global $CFG;
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'webservice_header', get_string('webservice_header', 'local_wsscol_courses'));
        $mform->setExpanded('webservice_header');

        $mform->addElement('text', 'wsname', 'wsname', array('size' => '100'));
        $mform->setType('wsname', PARAM_NOTAGS);
        $mform->addRule('wsname', null, 'required');
        $mform->addRule('wsname', null, 'maxlength', 100);

        $mform->addElement('text', 'wshost', 'wshost', array('size' => '253'));
        $mform->setType('wshost', PARAM_NOTAGS);
        $mform->addRule('wshost', null, 'required');
        $mform->addRule('wshost', null, 'maxlength', 253);

        $mform->addElement('text', 'wsuser', 'wsuser', array('size' => '128'));
        $mform->setType('wsuser', PARAM_NOTAGS);
        $mform->addRule('wsuser', null, 'maxlength', 128);

        $mform->addElement('passwordunmask', 'wspassword', 'wspassword', array('size' => '128'));
        $mform->setType('wspassword', PARAM_NOTAGS);
        $mform->addRule('wspassword', null, 'maxlength', 128);

        $mform->addElement('textarea', 'wsuri', 'wsuri', array('wrap' => 'virtual','rows'=>'5','cols'=>'100'));
        $mform->setType('wsuri', PARAM_RAW_TRIMMED);
        $mform->addRule('wsuri', null, 'required');

        $mform->addElement('header', 'config_header', get_string('config_header', 'local_wsscol_courses'));
        $mform->setExpanded('config_header');

        require_once($CFG->dirroot . '/user/profile/lib.php');
        $idfields = [
            'username' => new lang_string('username'),
            'firstname' => new lang_string('firstname'),
            'lastname' => new lang_string('lastname'),
            'idnumber' => new lang_string('idnumber'),
            'email' => new lang_string('email'),
            'phone1' => new lang_string('phone1'),
            'phone2' => new lang_string('phone2'),
            'department' => new lang_string('department'),
            'institution' => new lang_string('institution'),
            'city' => new lang_string('city'),
            'country' => new lang_string('country'),
        ];

        $profilefields = profile_get_custom_fields();
        foreach ($profilefields as $field) {
            if ($field->param2 > 255 || $field->datatype != 'text') {
                continue;
            }
            $idfields['profile_field_' . $field->shortname] = $field->name . ' *';
        }

        $mform->addElement('select', 'local_username', 'local_username', $idfields);

        $catlist = core_course_category::make_categories_list();
        $catlist[0] = get_string('top', 'core');
        $mform->addElement('select', 'rootcat_id', 'rootcat_id', $catlist);
        $mform->setType('rootcat_id', PARAM_INT);
        $mform->addRule('rootcat_id', null, 'required');

        $options = get_default_enrol_roles(context_system::instance());
        $editingteacher = get_archetype_roles('editingteacher');
        $manager = get_archetype_roles('manager');
        $roles = array_merge($editingteacher, $manager);
        $defaultrole = array();
        foreach ($roles as $role) {
            $defaultrole[] = intval($role->id);
        }
        $mform->addElement('select', 'roles', 'roles', $options);
        $mform->setType('roles', PARAM_INT);
        $mform->addRule('roles', null, 'required');
        $mform->getElement('roles')->setMultiple(true);
        $mform->getElement('roles')->setSelected($defaultrole);

        $mform->addElement('text', 'idnumber_suffix', 'idnumber_suffix', array('size' => '64'));
        $mform->setType('idnumber_suffix', PARAM_NOTAGS);
        $mform->addRule('idnumber_suffix', null, 'maxlength', 64);

        $mform->addElement('advcheckbox', 'status', get_string('active'));
        $mform->setDefault('status', 1);

        $mform->addElement('header', 'mapping_header', get_string('mapping_header', 'local_wsscol_courses'));
        $mform->setExpanded('mapping_header');

        $mform->addElement('text', 'path', 'path', array('size' => '64'));
        $mform->setType('path', PARAM_NOTAGS);
        $mform->addRule('path', null, 'maxlength', 64);

        $mform->addElement('html', get_string('mapping_description', 'local_wsscol_courses'));

        $mform->addElement('text', 'form_label', 'form_label', array('size' => '64'));
        $mform->setType('form_label', PARAM_NOTAGS);
        $mform->addRule('form_label', null, 'maxlength', 64);

        $mform->addElement('text', 'template', 'template', array('size' => '64'));
        $mform->setType('template', PARAM_NOTAGS);
        $mform->addRule('template', null, 'maxlength', 64);

        $mform->addElement('text', 'longname', 'longname', array('size' => '64'));
        $mform->setType('longname', PARAM_NOTAGS);
        $mform->addRule('longname', null, 'maxlength', 255);
        $mform->setDefault('longname', '[libelle] ([firstname] [lastname] 2023-24)');

        $mform->addElement('text', 'shortname', 'shortname', array('size' => '64'));
        $mform->setType('shortname', PARAM_NOTAGS);
        $mform->addRule('shortname', null, 'maxlength', 255);
        $mform->setDefault('shortname', '[code1]-[code2]-([firstname]-[lastname])-2023');

        $wsapps = $DB->get_records('enrol_wsscol_scolapps');
        $wsappselect = array();
        if ($wsapps) {
            foreach ($wsapps as $wsapp) {
                $wsappselect[$wsapp->id] = $wsapp->name;
            }
        }
        $wsappselect['none'] = 'none';

        // --- Bloc dynamique : mappings ---
        $mform->addElement('header', 'enrols_header', get_string('enrols_header', 'local_wsscol_courses'));
        $mform->setExpanded('enrols_header');

        $mform->addElement('html', get_string('enrols_description', 'local_wsscol_courses'));

        $repeatarray = array();
        $repeatarray[] = $mform->createElement(
            'select', 'enrol_wsid', get_string('enrol_wsid', 'local_wsscol_courses'), $wsappselect
        );
        $repeatarray[] = $mform->createElement(
            'text', 'enrol_code', get_string('enrol_code', 'local_wsscol_courses'), array('size' => '32')
        );

        $repeatoptions = array(
            'enrol_wsid' => array('type' => PARAM_ALPHANUM),
            'enrol_code' => array('type' => PARAM_NOTAGS),
        );

        $mappingcount = $this->_customdata['mappingcount'] ?? 0;
        $repeatno = $mappingcount + 1;

        $this->repeat_elements(
            $repeatarray,
            $repeatno,
            $repeatoptions,
            'enrol_repeats',
            'enrol_add_more',
            1,
            get_string('wenrol_addmore', 'local_wsscol_courses'),
            true
        );
        $submitlabel = null;
        if ($this->isadding) {
            $submitlabel = get_string('wsedit_submitlabel', 'local_wsscol_courses');
        }
        $this->add_action_buttons(true, $submitlabel);
    }
}


// PAGE Handling.

require_login();
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissiontoviewpage');
}
$managewsurl = new moodle_url('/local/wsscol_courses/wsmanage.php');
$pluginurl = new moodle_url('/admin/settings.php', array('section' => 'ettingswsscol'));
$wsid = optional_param('id', 0, PARAM_INT);
$urlparams = array('id' => $wsid);
$PAGE->set_context($context);
$PAGE->set_url('/local/wsscol_courses/wsedit.php', $urlparams);
$PAGE->set_pagelayout('admin');

$mappingcount = 0;
if ($wsid) {
    $isadding = false;
    $wsrecord_db = $DB->get_record('local_wsscol_courses_ws_config', array('id' => $wsid), '*', MUST_EXIST);
    //$wsrecord = wsscol_flatten_record($wsrecord);
    $wsrecord_db = \local_wsscol_courses\ws_config_persistent::from_record($wsrecord_db);
    $wsrecord = $wsrecord_db->to_form_data();
    $mappingcount = count($wsrecord->enrol_wsid ?? []);
} else {
    $isadding = true;
    $wsrecord = new stdClass;
}

$mform = new wsedit_form($PAGE->url, $isadding, array('mappingcount' => $mappingcount));
$mform->set_data($wsrecord);

if ($mform->is_cancelled()) {
    redirect($managewsurl);
} else if ($data = $mform->get_data()) {
    //$config = wsscol_build_config($data);
    $course_model = \local_wsscol_courses\ws_config_persistent::from_form_data($data);

/*    $record = new stdClass();
    $record->wsname = $data->wsname;
    $record->wshost = $data->wshost;
    $record->wsuser = $data->wsuser;
    $record->wspassword = $data->wspassword;
    $record->wsuri = $data->wsuri;
    $record->status = intval($data->status);
    $record->config = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);*/

    if ($isadding) {
        $DB->insert_record('local_wsscol_courses_ws_config', $course_model->to_record());
    } else {
        $course_model->id = $wsid;
        $DB->update_record('local_wsscol_courses_ws_config', $course_model->to_record());
    }
    redirect($managewsurl);
} else {
    $PAGE->set_title('add wsbservices');
    $PAGE->set_heading('add wsbservices');

    $PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/' . $CFG->admin));
    $PAGE->navbar->add(get_string('pluginname', 'local_wsscol_courses'), $pluginurl);
    $PAGE->navbar->add(get_string('wsmanage_title', 'local_wsscol_courses'), $managewsurl);
    $PAGE->navbar->add(get_string('wsedit_title', 'local_wsscol_courses'));

    echo $OUTPUT->header();
    echo $OUTPUT->heading('add wsbservices', 2);

    $mform->display();

    echo $OUTPUT->footer();
}
