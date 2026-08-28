<?php
/**
 * User edit field: lifelong candidate ID stored in local_tm_cert_candidates.
 *
 * Placed next to core "institution" on editadvanced; admin-only via PROFILE_VISIBLE_NONE.
 *
 * @package    profilefield_tmcandidate
 */

defined('MOODLE_INTERNAL') || die();

class profile_field_tmcandidate extends profile_field_base {

    /**
     * Admins with moodle/user:update always see this on editadvanced.
     *
     * @return bool
     */
    public function is_visible() {
        if (has_capability('moodle/user:update', context_system::instance())) {
            return true;
        }
        return parent::is_visible();
    }

    /**
     * @return bool
     */
    public function is_editable() {
        if (has_capability('moodle/user:update', context_system::instance())) {
            if (!empty($this->field->locked) && !has_capability('moodle/user:update', context_system::instance())) {
                return false;
            }
            return true;
        }
        return parent::is_editable();
    }

    /**
     * Load display/edit value from the certification plugin table (not user_info_data).
     */
    public function load_data() {
        $this->data = '';
        $this->dataformat = FORMAT_HTML;
        if (empty($this->userid) || $this->userid < 2) {
            return;
        }
        if (!$this->require_candidate_manager()) {
            return;
        }
        $cid = \local_tm_certification\candidate_manager::get_candidate_id((int)$this->userid);
        $this->data = $cid !== null ? $cid : '';
    }

    /**
     * Put field on the user edit form, immediately after institution (before department).
     *
     * @param MoodleQuickForm $mform
     */
    public function edit_field_add($mform) {
        $label = format_string($this->field->name);
        $attributes = 'maxlength="6" size="20"';

        if ($mform->elementExists('department')) {
            $mform->insertElementBefore(
                $mform->createElement('text', $this->inputname, $label, $attributes),
                'department'
            );
        } else if ($mform->elementExists('institution')) {
            $mform->insertElementBefore(
                $mform->createElement('text', $this->inputname, $label, $attributes),
                'institution'
            );
        } else {
            $mform->addElement('text', $this->inputname, $label, $attributes);
        }
        $mform->setType($this->inputname, PARAM_TEXT);

        // Field was moved out of its profile category — drop the empty category header.
        $header = 'category_' . (int)$this->field->categoryid;
        if ($mform->elementExists($header)) {
            $mform->removeElement($header);
        }
    }

    /**
     * @param stdClass $user
     */
    public function edit_load_user_data($user) {
        if (empty($user->id) || (int)$user->id < 2) {
            $user->{$this->inputname} = '';
            return;
        }
        if (!$this->require_candidate_manager()) {
            $user->{$this->inputname} = '';
            return;
        }
        $cid = \local_tm_certification\candidate_manager::get_candidate_id((int)$user->id);
        $user->{$this->inputname} = $cid !== null ? $cid : '';
    }

    /**
     * @param stdClass $usernew
     * @return array
     */
    public function edit_validate_field($usernew) {
        $errors = [];
        if (!isset($usernew->{$this->inputname})) {
            return $errors;
        }
        if (!$this->require_candidate_manager()) {
            $errors[$this->inputname] = get_string('error_candidate_allocate_failed', 'local_tm_certification');
            return $errors;
        }

        $userid = !empty($usernew->id) ? (int)$usernew->id : (int)$this->userid;
        $value = trim((string)$usernew->{$this->inputname});
        $msg = \local_tm_certification\candidate_manager::validate_candidate_id_change($userid, $value);
        if ($msg !== null) {
            $errors[$this->inputname] = $msg;
        }
        return $errors;
    }

    /**
     * Persist to local_tm_cert_candidates only (skip user_info_data).
     *
     * @param stdClass $usernew
     */
    public function edit_save_data($usernew) {
        if (!isset($usernew->{$this->inputname})) {
            return;
        }
        if (empty($usernew->id) || (int)$usernew->id < 2) {
            return;
        }
        if (!$this->require_candidate_manager()) {
            return;
        }

        $value = trim((string)$usernew->{$this->inputname});
        \local_tm_certification\candidate_manager::change_candidate_id((int)$usernew->id, $value);
    }

    /**
     * Never show on signup.
     *
     * @return bool
     */
    public function is_signup_field() {
        return false;
    }

    /**
     * @return string
     */
    public function display_data() {
        return s((string)$this->data);
    }

    /**
     * @return bool
     */
    private function require_candidate_manager(): bool {
        global $CFG;
        $path = $CFG->dirroot . '/local/tm_certification/classes/candidate_manager.php';
        if (!file_exists($path)) {
            return false;
        }
        require_once($path);
        return class_exists('\local_tm_certification\candidate_manager');
    }
}
