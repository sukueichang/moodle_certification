<?php
/**
 * Ensure Moodle user-edit form exposes candidate ID via profilefield_tmcandidate.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class profile_candidate_field {

    /** @var string Shortname stored in user_info_field (form input profile_field_*). */
    public const SHORTNAME = 'tm_candidate_id';

    /** @var string profilefield plugin datatype. */
    public const DATATYPE = 'tmcandidate';

    /**
     * Create category + field instance when the profile field plugin is installed.
     * Safe to call repeatedly (upgrade / install).
     */
    public static function ensure_installed(): void {
        global $DB, $CFG;

        if (!class_exists('\core_component') ||
                !\core_component::get_plugin_directory('profilefield', self::DATATYPE)) {
            return;
        }

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $field = $DB->get_record('user_info_field', [
            'shortname' => self::SHORTNAME,
            'datatype' => self::DATATYPE,
        ]);
        if ($field) {
            // Keep admin-only visibility / not on signup.
            $needupdate = false;
            if ((int)$field->visible !== (int)PROFILE_VISIBLE_NONE) {
                $field->visible = PROFILE_VISIBLE_NONE;
                $needupdate = true;
            }
            if ((int)$field->signup !== 0) {
                $field->signup = 0;
                $needupdate = true;
            }
            if ($needupdate) {
                $DB->update_record('user_info_field', $field);
            }
            return;
        }

        $categoryid = self::ensure_category_id();

        $record = new \stdClass();
        $record->shortname = self::SHORTNAME;
        $record->name = get_string('candidate_id', 'local_tm_certification');
        $record->datatype = self::DATATYPE;
        $record->description = '';
        $record->descriptionformat = FORMAT_HTML;
        $record->categoryid = $categoryid;
        $record->sortorder = 1;
        $record->required = 0;
        $record->locked = 0;
        $record->visible = PROFILE_VISIBLE_NONE;
        $record->forceunique = 0;
        $record->signup = 0;
        $record->defaultdata = '';
        $record->defaultdataformat = FORMAT_HTML;
        $record->param1 = '';
        $record->param2 = '';
        $record->param3 = '';
        $record->param4 = '';
        $record->param5 = '';

        $DB->insert_record('user_info_field', $record);
    }

    /**
     * Dedicated category so we can drop its empty header after moving the field
     * next to institution on the advanced user edit form.
     */
    private static function ensure_category_id(): int {
        global $DB;

        $name = get_string('profile_category_candidate', 'local_tm_certification');
        $existing = $DB->get_record('user_info_category', ['name' => $name], 'id', IGNORE_MULTIPLE);
        if ($existing) {
            return (int)$existing->id;
        }

        $max = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {user_info_category}');
        $cat = new \stdClass();
        $cat->name = $name;
        $cat->sortorder = $max + 1;
        return (int)$DB->insert_record('user_info_category', $cat);
    }
}
