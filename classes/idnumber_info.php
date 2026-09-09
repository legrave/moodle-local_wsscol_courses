<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.


namespace local_wsscol_courses;

/**
 * Contains information about an idnumber.
 *
 * @package    local_wsscol_courses
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class idnumber_info {
    /** @var string */
    public $username;

    /** @var string */
    public $code;

    /** @var int */
    public $wsid;

    /** @var string */
    public $suffix;

    /** @var string */
    public $separator;

    /**
     * Constructor.
     *
     * @param int $wsid
     * @param string $code
     * @param string $username
     * @param string $suffix
     * @param string $separator
     */
    public function __construct(int $wsid, string $code, string $username, string $suffix, string $separator) {
        $this->username = $username;
        $this->code = $code;
        $this->wsid = $wsid;
        $this->suffix = $suffix;
        $this->separator = $separator;
    }
    public function to_idnumber () {
        $idnumber = 'wsscol_'.$this->wsid . $this->separator . $this->code . $this->separator . $this->username . $this->separator . $this->suffix;
        return $idnumber;
    }

    /**
     * Builds an instance from an idnumber formatted as wsscol_{wsid}{sep}{code}{sep}{username}[{sep}{suffix}]
     *
     * The "wsscol_" prefix is fixed and is stripped before splitting on the configured
     * separator, to avoid any conflict when that separator is itself an underscore.
     * The suffix is optional: if absent, an empty string is assigned to it.
     *
     * @param string $idnumber The idnumber to parse, e.g. "wsscol_2/user/code/suffix"
     *
     * @return self Instance built from the information extracted from the idnumber
     *
     * @throws Exception If the separator is not configured (idnumber_seperator)
     * @throws Exception If the idnumber does not start with the "wsscol_" prefix
     * @throws Exception If the idnumber does not contain the expected number of segments (3 or 4)
     */
    public static function get_from_idnumber(string $idnumber) {
        $separator = get_config('local_wsscol_courses', 'idnumber_seperator');
        if (!$separator) {
            throw new Exception('idnumber_seperator not set');
        }

        $prefix = 'wsscol_';
        if (strpos($idnumber, $prefix) !== 0) {
            throw new Exception('wrong idnumber');
        }

        $rest = substr($idnumber, strlen($prefix));
        $matches = explode($separator, $rest);

        // $matches[0] = wsid, [1] = code, [2] = username, [3] = suffix (optionnel)
        if (count($matches) === 4) {
            [$wsid, $code, $username, $suffix] = $matches;
        } elseif (count($matches) === 3) {
            [$wsid, $code, $username] = $matches;
            $suffix = '';
        } else {
            throw new Exception('wrong idnumber');
        }
        return new self((int) $wsid, $code, $username, $suffix, $separator);
    }
}