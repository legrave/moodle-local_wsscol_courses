<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.


namespace local_wsscol_courses;

/**
 * Contains information about an enrolment.
 *
 * @package    local_wsscol_courses
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class enrol_info {

    /** @var array */
    public $enrol_code=[];

    /** @var int */
    public $enrol_wsid;

    /**
     * Constructor.
     *
     * @param int $enrol_wsid
     * @param array $enrol_code
     */
    public function __construct(int $enrol_wsid, array $enrol_code = []) {
        if ($enrol_code) {
            $this->enrol_code = $enrol_code;
        }
        $this->enrol_wsid = $enrol_wsid;
    }

    public function add_enrol_code($code) {
        $this->enrol_code[] = $code;
    }

    /**
     * Returns the names of the enrolment attributes.
     *
     * @return string[] List of attribute names.
     */
    public static function get_attribute_names(): array {
        return [
            'enrol_code',
            'enrol_wsid',
        ];
    }

    public static function from_stdclass(\stdClass $data): self {
        return new self(
            $data->enrol_wsid,
            $data->enrol_code
        );
    }
}