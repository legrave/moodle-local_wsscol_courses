<?php

namespace local_wsscol_courses;

/**
 * Template representing the course creation.
 */
class course_info {

    /** @var int Identifier of the associated ws_agent. */
    public $wsid = 0;
    /** @var enrol_info[] List of associated enrolment information. */
    public $enrols = [];
    /** @var int Category identifier. */
    public $rootcat_id = 0;
    /** @var string|array Path of category to create if specified. */
    public $path = '';
    /** @var int[] Role identifiers to assign. */
    public $roles = array();
    /** @var string Long name pattern of the course to create. */
    public $fullname = '';
    /** @var string Short name pattern of the course to create. */
    public $shortname = '';
    /** @var string username. */
    public $username = '';
    /** @var idnumber_info Idnumber. */
    public $idnumber_info = '';
    /** @var string Course to clone. */
    public $template = '';

    /**
     * Build a course_info instance from a standard mdl_course database record.
     *
     * Only fields that exist natively on mdl_course are mapped here.
     * Fields specific to the template logic (wsid, enrols, roles, path,
     * username, template) are NOT present on mdl_course and must
     * be populated separately (custom table, config, or setters) before use.
     *
     * @param stdClass $course A record from the {course} table (e.g. from get_record()).
     * @return course_info
     */
    public static function get_from_course_record(\stdClass $course): course_info {
        $instance = new self();
        global $DB;

        if (isset($course->idnumber)) {
            $instance->idnumber_info = idnumber_info::get_from_idnumber($course->idnumber);
        }

        $instance->template = '';


        $ws_config_record = $DB->get_record('local_wsscol_courses_ws_config', ['id'=>$instance->idnumber_info->wsid]);
        $ws_config = ws_config_persistent::from_record($ws_config_record);

        if (isset($ws_config->rootcat_id)) {
            $instance->rootcat_id = $ws_config->rootcat_id;
        }
        // Champs directement disponibles sur mdl_course.
        if (isset($ws_config->longname)) {
            $instance->fullname = $course->fullname;
        }
        if (isset($ws_config->shortname)) {
            $instance->shortname = $course->shortname;
        }
        if (isset($ws_config->roles)) {
            $instance->roles = $ws_config->roles;
        }

        $enrol_instances = enrol_get_instances($course->id, false);
        //TODO SFX
        foreach ($enrol_instances as $enrol_instance) {
            if ($enrol_instance->enrol == 'wsscol' && $enrol_instance->customint3 == \enrol_wsscol_plugin::INSTANCE_ETAT_AUTO) {
                $enrol_info = new enrol_info($enrol_instance->customint2);
                $enrol_info->add_enrol_code($enrol_instance->customchar1);
                $instance->enrols[]=$enrol_info;
            }
        }
        $instance->path = self::get_path_category($course->category);
        $instance->wsid = $instance->idnumber_info->wsid;
        $instance->username = $instance->idnumber_info->username;
        return $instance;
    }

    public static function get_path_category(int $categoryid): array {
        global $DB;

        $category = \core_course_category::get($categoryid, MUST_EXIST, true);

        // Extrait les ids numériques du path (/1/5/12 -> [1, 5, 12]).
        $categoryids = array_filter(explode('/', $category->path));
        $categoryids = array_map('intval', $categoryids);

        if (empty($categoryids)) {
            return [];
        }

        // Une seule requête pour récupérer tous les idnumber en une fois.
        list($insql, $inparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
        $records = $DB->get_records_select(
            'course_categories',
            "id $insql",
            $inparams,
            '',
            'id, idnumber'
        );

        // Reconstruction du path dans l'ordre root -> feuille.
        $path = [];
        foreach ($categoryids as $catid) {
            if (!isset($records[$catid])) {
                continue;
            }
            $idnumber = $records[$catid]->idnumber;
            if ($idnumber === '' || $idnumber === null) {
                return [];
            }
            $path[] = $idnumber;
        }
        return $path;
    }
}

