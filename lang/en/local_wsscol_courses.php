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

$string['pluginname'] = 'Wsscol activate courses';

$string['wsmanage_title'] = 'Manage Web Services';
$string['wsedit_title'] = 'Add Web Services';

$string['nocourses'] = 'No course available to activate';
$string['open_courses_title'] = 'Activating a course';
$string['open_courses_title2'] = 'Courses overview you can activate';
$string['open_courses_title3'] = 'Courses you has activated';
$string['type_instance_label'] = 'Type';
$string['creator_instance_label'] = 'Creator';

$string['webservice_header'] = 'WebServices Settings';
$string['config_header'] = 'Plugin Settings';
$string['mapping_header'] = 'Mapping Settings';
$string['mapping_description'] = '<div></div><p> Except for the <strong>Path</strong> field above, 
    all of the fields below can be mapped to multiple attributes (of type <code>string</code>) returned by the web service. </p>
    <p> Web service attributes must be enclosed in square brackets. </p>
    <p> <strong>Example for the Longname field:</strong> <code>[course] - [username] - 2026_2027</code> </p></div>';
$string['enrols_header'] = 'Enrolments WebServices Settings';
$string['enrols_description'] = 'Wsscol enrol method to associate when activating course';

$string['add_more'] = 'Ass some Wsscol enrol method';
$string['wsedit_submitlabel'] = 'Save this configuration';




$string['syncmethodenroltask'] = 'Synchronise wsscol method enrolment task';

$string['defaultcat'] = 'Default category';
$string['defaultcat_desc'] = 'Select the destination category of the new courses';

$string['roles'] = 'Roles';
$string['roles_desc'] = 'Roles that will be assigned to te course creator';

$string['template'] = 'Template';
$string['template_desc'] = 'Optional: auto-created courses can copy their settings from a template course';

$string['course_creation_title'] = 'Course Creation';
$string['course_creation_desc'] = 'settings of course creation';
$string['longname'] = 'Course\'s longname template';
$string['cc_longname_desc'] = 'fields availables : [code1] - [code2] - [code3] - [libelle] - [firstname] - [lastname]';
$string['shortname'] = 'Course\'s shortname template';
$string['cc_shortname_desc'] = 'fields availables : [code1] - [code2] - [code3] - [libelle] - [firstname] - [lastname]';
$string['idnumber_seperator'] = 'Course idnumber Separator';
$string['idnumber_seperator_desc'] = 'Separator use for course idnumber. If not set, / (slash) will be use.';
$string['idnumber_suffix'] = 'Course Tag Suffix';
$string['idnumber_suffix_desc'] = 'You can define a suffix tag for idnumber. for example : year';

$string['webservices_title'] = 'Webservices';
$string['webservices_desc'] = 'settings of webservices. Each record will propose a course to open.
        An wsscol enrol method will be added with the code return by Attributes.
	If code3 return an array,it add multiple enrol wssscol methods of type setted by ws_coursesteacher_enrol_wsid.
        If code 3 return a string, it will add only one enrol wssscol methods.
        code1 and code2 are concatenated to done code';
$string['ws_user'] = 'Webservices User';
$string['ws_user_desc'] = 'Webservices User';
$string['ws_password'] = 'Webservices User Password';
$string['ws_password_desc'] = 'Webservices User Password';
$string['ws_local_uid'] = 'User id field ';
$string['ws_local_uid_desc'] = 'User field to ask to ws_agent';
$string['ws_url'] = 'ws_agent enrol url';
$string['ws_url_desc'] = 'url of ws_agent giving course enrolment';
$string['ws_coursesteacher_uri'] = 'courses list uri';
$string['ws_coursesteacher_uri_desc'] = 'uri that get all courses of a teacher';

$string['ws_coursesteacher_code1'] = 'first attribute to set the course code';
$string['ws_coursesteacher_code1_desc'] = 'example : the code itself';
$string['ws_coursesteacher_code2'] = 'second attribute to set the course code ';
$string['ws_coursesteacher_code2_desc'] = 'example : the type of schooling group';
$string['ws_coursesteacher_code3'] = 'third attribute to retrieve code of schooling groups';
$string['ws_coursesteacher_code3_desc'] = 'If set, this attribute must return an array, we loop on it to add wsscol enrol method with code found';
$string['ws_coursesteacher_category'] = 'attribute to determine the category in witch the course will be create';
$string['ws_coursesteacher_category_desc'] = 'if it use, it\'ll create a category in default category to place the course';
$string['ws_coursesteacher_name'] = 'attribut to determine the name of the course';
$string['ws_coursesteacher_name_desc'] = '';
$string['wsscol_courses:activate_courses'] = 'access activate course page';

$string['opencourses_page_desciption'] = '<div class="box py-3 generalbox alert alert-info">
    Warning : Only Course Unit Coordinator can activated course in Moodle.
</div>
If you want to create a specific course, know that you will have to manage students enrollment, either by manually enroll them, or by activating the auto-enrollment feature : <a href="https://moodle.entpe.fr/course/request.php">click here<br></a>';



$string['confirm_delete_ws'] = 'Sure to delete this Web Service ?';
$string['add_ws_button'] = 'Add new Web Service';

$string['wsmanage_link'] = 'add/manage web services schooling app';

$string['enrol_name'] = 'Enrolments Name';
$string['enrol_wsid'] = 'Enrolments WebServices Id';
$string['enrol_code'] = 'Attribute Mapping Code';
$string['enrols_addmore'] = 'Add an enrolment WebService';
$string['enrols_delete'] = 'Delete enrolment WebService';

