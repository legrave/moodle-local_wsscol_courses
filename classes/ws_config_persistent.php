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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
namespace local_wsscol_courses;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent class for storing and managing the configuration of a wsscol ws_agent.
 *
 * Handles the conversion between the database record, form data
 * and the JSON configuration stored in the database.
 *
 * @package    local_wsscol_courses
 */
class ws_config_persistent {
    /** @var int Database record ID. */
    public $id = 0;
    /** @var string Web service name. */
    public $wsname = '';
    /** @var string Web service host. */
    public $wshost = '';
    /** @var string Web service username. */
    public $wsuser = '';
    /** @var string Web service password. */
    public $wspassword = '';
    /** @var string Web service URI. */
    public $wsuri = '';
    /** @var int Configuration status. */
    public $status = 0;
    /** @var string|array Course category mapping. */
    public $path = '';
    /** @var string Course template mapping. */
    public $template = '';
    /** @var string Built course long name. */
    public $longname = '';
    /** @var string Built course short name. */
    public $shortname = '';
    /** @var string which label to display. */
    public $form_label = '';
    /** @var string Local username setting. */
    public $local_username = '';
    /** @var int Moodle category ID. */
    public $rootcat_id = 0;
    /** @var array Course roles. */
    public $roles = [];
    /** @var string ID number suffix. */
    public $idnumber_suffix = '';
    /** @var array Enrolment mappings. */
    public $enrols = [];

    /**
     * Create a ws_config from a database record.
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(\stdClass $record): self
    {
        $ws_config = new self();

        // Database fields.
        $ws_config->id = (int)($record->id ?? 0);
        $ws_config->wsname = $record->wsname ?? '';
        $ws_config->wshost = $record->wshost ?? '';
        $ws_config->wsuser = $record->wsuser ?? '';
        $ws_config->wspassword = $record->wspassword ?? '';
        $ws_config->wsuri = $record->wsuri ?? '';
        $ws_config->status = (int)($record->status ?? 0);

        // JSON configuration.
        $config = self::decode_config($record->config ?? null);

        $mappedfields = $config['mapped_fields'] ?? [];
        $ws_config->path = $mappedfields['path'] ?? '';
        $ws_config->template = $mappedfields['template'] ?? '';
        $ws_config->longname = $mappedfields['longname'] ?? '';
        $ws_config->shortname = $mappedfields['shortname'] ?? '';
        $ws_config->form_label = $mappedfields['form_label'] ?? '';

        $settings = $config['settings'] ?? [];
        $ws_config->local_username = $settings['local_username'] ?? '';
        $ws_config->rootcat_id = (int)($settings['rootcat_id'] ?? 0);
        $ws_config->roles = $settings['roles'] ?? [];
        $ws_config->idnumber_suffix = $settings['idnumber_suffix'] ?? '';

        $ws_config->enrols = $config['enrols'] ?? [];

        return $ws_config;
    }

    /** Convert the model to form data.
     * The returned object can be passed to $mform->set_data().
     * @return stdClass
     */
    public function to_form_data(): \stdClass
    {
        $data = new \stdClass();
        // Database fields.
        $data->id = $this->id;
        $data->wsname = $this->wsname;
        $data->wshost = $this->wshost;
        $data->wsuser = $this->wsuser;
        $data->wspassword = $this->wspassword;
        $data->wsuri = $this->wsuri;
        $data->status =
            $this->status;
        // Mapped fields.
        $data->path = $this->path;
        $data->template = $this->template;
        $data->form_label = $this->form_label;
        // Built fields.
        $data->longname = $this->longname;
        $data->shortname = $this->shortname;
        // Settings.
        $data->local_username = $this->local_username;
        $data->rootcat_id = $this->rootcat_id;
        $data->roles = $this->roles;
        $data->idnumber_suffix = $this->idnumber_suffix;
        // Enrolments.
        $data->enrol_wsid = [];
        $data->enrol_code = [];

        foreach ($this->enrols as $enrol) {
            $data->enrol_wsid[] = $enrol['enrol_wsid'] ?? 'none';
            $data->enrol_code[] = $enrol['enrol_code'] ?? '';
        }
        return $data;
    }


    /**
     * Create a model from form data.
     *
     * Handles repeated enrolment elements.
     *
     * @param stdClass $data Form data.
     * @return self
     */
    public
    static function from_form_data(\stdClass $data): self
    {
        $model = new self();

        // Database fields.
        $model->id = (int)($data->id ?? 0);
        $model->wsname = $data->wsname ?? '';
        $model->wshost = $data->wshost ?? '';
        $model->wsuser = $data->wsuser ?? '';
        $model->wspassword = $data->wspassword ?? '';
        $model->wsuri = $data->wsuri ?? '';
        $model->status = (int)($data->status ?? 0);

        // Mapped fields.
        $model->path = $data->path ?? '';
        $model->template = $data->template ?? '';
        $model->longname = $data->longname ?? '';
        $model->shortname = $data->shortname ?? '';
        $model->form_label = $data->form_label ?? '';

        // Settings.
        $model->local_username = $data->local_username ?? '';
        $model->rootcat_id = (int)($data->rootcat_id ?? 0);
        $model->roles = $data->roles ?? [];
        $model->idnumber_suffix = $data->idnumber_suffix ?? '';

        // Enrolment mappings.
        $model->enrols = [];
        if (!empty($data->enrol_wsid)) {
            foreach ($data->enrol_wsid as $index=>$enrol_wsid) {
                $wsid = ($enrol_wsid === 'none') ? null : (int)$enrol_wsid;
                // Ignore enrolments without a web service ID.
                if ($wsid === null) {
                    continue;
                }
                $code = $data->enrol_code[$index] ?? '';
                $code = ($code === '') ? null : $code;

                // Ignore enrolments without a code.
                if ($code === null) {
                    continue;
                }

                $model->enrols[$wsid] = [
                    'enrol_wsid' => $wsid,
                    'enrol_code' => $code,
                ];
            }
        }

        return $model;
    }


    /**
     * Get the mapped fields configuration.
     *
     * @return array
     */
    public
    function get_mapped_fields(): array
    {
        return [
            'path' => $this->path,
            'template' => $this->template,
            'longname' => $this->longname,
            'shortname' => $this->shortname,
            'form_label' => $this->form_label,
        ];
    }

    /**
     * Get the settings configuration.
     *
     * @return array
     */
    public
    function get_settings(): array
    {
        return [
            'local_username' => $this->local_username,
            'rootcat_id' => $this->rootcat_id,
            'roles' => $this->roles,
            'idnumber_suffix' => $this->idnumber_suffix,
        ];
    }


    /**
     * Get the enrolment mappings.
     *
     * @return array
     */
    public
    function get_enrols(): array
    {
        return $this->enrols;
    }


    /**
     * Get the complete JSON configuration.
     *
     * @return array
     */
    public
    function get_json_config(): array
    {
        return [
            'mapped_fields' => $this->get_mapped_fields(),
            'settings' => $this->get_settings(),
            'enrols' => $this->get_enrols(),
        ];
    }


    /**
     * Create a database record from the model.
     *
     * The returned object can be passed directly to
     * $DB->insert_record() or $DB->update_record().
     *
     * @return \stdClass
     */
    public function to_record(): \stdClass
    {
        $record = new \stdClass();

        $record->id = $this->id;
        $record->wsname = $this->wsname;
        $record->wshost = $this->wshost;
        $record->wsuser = $this->wsuser;
        $record->wspassword = $this->wspassword;
        $record->wsuri = $this->wsuri;
        $record->status = $this->status;
        $record->config = json_encode($this->get_json_config());

        return $record;
    }


    /**
     * Decode the JSON configuration stored in the database.
     *
     * @param string|null $json JSON configuration.
     * @return array Decoded configuration.
     */
    private
    static function decode_config($json): array
    {
        if (empty($json)) {
            return [];
        }

        $config = json_decode($json, true);

        return is_array($config) ? $config : [];
    }
}