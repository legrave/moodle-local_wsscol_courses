local_wsscol_courses
=======================

* Maintained by: Serge FELIX
* License: [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)

WSSCOL for "WebServices de Scolarité" - Schooling Webservices

Description
===========
This plugin provides a page listing courses that could be created by an user from a Webservice call?

Each record return by webservice for the user loggin propose a course to open. An wsscol enrol method can be added with the code return by Attributes (if setted).


Installation
============

* Copy the module code directly to the local/wsscol_courses directory.
* add the capacity local/wsscol_courses:activate_courses to the role targeting your teachers
* set the plugin
* No need moodle/course:create, this plugin is independant of that capacity

Tasks
============
If you utilyze wsscol enrolment methods, you must activate this task : \local_wsscol_courses\task\sync_methodenrol to synchronise them. This task utilyze adhoc tasks, so you need to set
the cron.

Admin Settings
============

### General

* rootcat_id : Root catégory where the course will be created
* path : A category name where the course will be created (under rootcat_id)
* roles : Roles that will be assigned to te course creator
* longname : Pattern for course longname
* shortname : Pattern for course shortname
* idnumber_seperator : the séparator use to the pattern of idnumber (slash bu default - to change if slash is use in your group code)

### Webservices (save in db local_wsscol_courses_ws_config)

* wsname : the name of the webservice
* wsuser : the user for webservice authentification
* wspassword : the password for webservice authentification
* wshost : webservice base url
* uri : webservice uri (you can utilized \[search\] which will be replaced by username)

### Mapping 
each of this attributes are the map field return by the web service
* template : (Optional) autocreated courses can copy their settings from a template course
* libelle : attribut to determine the name of the course

### Enrols
You can specified enrol methods from enrol/wsscol to add to courses (none if you don't use enrol/wsscol). You can add multiple enrol/wsscol methods. For each of them, you can specify :
* id : select te enrol/wsscol
* code : a code or an array of codes to apply to enrol/wsscol
* name : a name


Requirements
------------
This plugin needs enrol/wsscol if you want to use this enrolment method
