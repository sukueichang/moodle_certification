<?php
/**
 * Inject / save candidate ID on core user/editadvanced.php (Moodle 3.10).
 *
 * Core has no form hook here; we inject a field next to institution and
 * persist on user_updated from the POST value.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class user_edit_candidate_field {

    /** @var string HTML name / POST key */
    public const FIELDNAME = 'tm_cert_candidateid';

    /**
     * True on advanced user edit when the viewer may update users.
     */
    public static function should_inject(): bool {
        global $PAGE, $USER;

        if (!isloggedin() || isguestuser() || empty($PAGE)) {
            return false;
        }
        if (!has_capability('moodle/user:update', \context_system::instance())) {
            return false;
        }

        $path = $PAGE->url ? $PAGE->url->get_path() : '';
        if (strpos($path, '/user/editadvanced.php') === false
                && (string)($PAGE->pagetype ?? '') !== 'user-editadvanced') {
            return false;
        }

        $id = optional_param('id', 0, PARAM_INT);
        // Creating a user (-1) or missing id: still allow blank field for later bind.
        if ($id === 0) {
            $id = (int)$USER->id;
        }
        return $id !== 0;
    }

    /**
     * Target Moodle user id for the form being edited.
     */
    public static function target_userid(): int {
        global $USER;
        $id = optional_param('id', 0, PARAM_INT);
        if ($id === 0) {
            $id = (int)$USER->id;
        }
        return $id;
    }

    /**
     * Current candidate ID string for the form (may be empty).
     */
    public static function current_value(int $userid): string {
        if ($userid < 2) {
            return '';
        }
        require_once(__DIR__ . '/candidate_manager.php');
        $cid = candidate_manager::get_candidate_id($userid);
        return $cid !== null ? $cid : '';
    }

    /**
     * HTML + JS to insert the field before 科系 / department (after 機構).
     */
    public static function render_inject_markup(): string {
        if (!self::should_inject()) {
            return '';
        }

        // Profile-field path already present — do not duplicate.
        $out = \html_writer::script("
            if (document.querySelector('input[name=\"profile_field_tm_candidate_id\"]')) {
                window.tmCertCandidateFieldSkip = true;
            }
        ");

        $userid = self::target_userid();
        $value = self::current_value($userid);
        $label = get_string('candidate_id', 'local_tm_certification');
        $fieldname = self::FIELDNAME;
        $checkurl = (new \moodle_url('/local/tm_certification/check_candidateid.php'))->out(false);
        $sesskey = sesskey();

        $labeljson = json_encode($label, JSON_UNESCAPED_UNICODE);
        $valuejson = json_encode($value, JSON_UNESCAPED_UNICODE);
        $namejson = json_encode($fieldname);
        $checkurljson = json_encode($checkurl);
        $sesskeyjson = json_encode($sesskey);
        $useridjson = json_encode((int)$userid);
        $checkingjson = json_encode(get_string('candidate_id_checking', 'local_tm_certification'), JSON_UNESCAPED_UNICODE);

        $out .= \html_writer::script("
(function() {
    if (window.tmCertCandidateFieldSkip) { return; }
    var labelText = {$labeljson};
    var fieldName = {$namejson};
    var fieldValue = {$valuejson};
    var checkUrl = {$checkurljson};
    var sessKey = {$sesskeyjson};
    var userId = {$useridjson};
    var checkingMsg = {$checkingjson};
    function buildRow(ref) {
        var row = ref.cloneNode(true);
        row.classList.add('tm-cert-candidateid-row');
        var label = row.querySelector('label');
        if (label) {
            label.setAttribute('for', 'id_' + fieldName);
            label.textContent = labelText;
        }
        var title = row.querySelector('.col-form-label, .fitemtitle');
        if (title && !label) {
            title.textContent = labelText;
        }
        var input = row.querySelector('input');
        if (!input) { return null; }
        input.setAttribute('name', fieldName);
        input.setAttribute('id', 'id_' + fieldName);
        input.setAttribute('maxlength', '6');
        input.value = fieldValue;
        var helps = row.querySelectorAll('.btn-link, .helptooltip, a.helpbutton, .form-control-feedback, [data-toggle=\"popover\"]');
        for (var i = 0; i < helps.length; i++) {
            if (helps[i].parentNode) { helps[i].parentNode.removeChild(helps[i]); }
        }
        return row;
    }
    function bindSubmitGuard() {
        var form = document.querySelector('#page-user-editadvanced form.mform, form.mform');
        if (!form || form.getAttribute('data-tm-cert-cid-guard') === '1') { return; }
        form.setAttribute('data-tm-cert-cid-guard', '1');
        form.addEventListener('submit', function(ev) {
            var input = document.querySelector('input[name=\"' + fieldName + '\"]')
                || document.querySelector('input[name=\"profile_field_tm_candidate_id\"]');
            if (!input) { return; }
            ev.preventDefault();
            ev.stopPropagation();
            var candidateid = (input.value || '').replace(/^\\s+|\\s+$/g, '');
            var body = 'userid=' + encodeURIComponent(userId)
                + '&candidateid=' + encodeURIComponent(candidateid)
                + '&sesskey=' + encodeURIComponent(sessKey);
            var submitters = form.querySelectorAll('button[type=submit], input[type=submit]');
            for (var s = 0; s < submitters.length; s++) { submitters[s].disabled = true; }
            fetch(checkUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data && data.ok) {
                    form.removeAttribute('data-tm-cert-cid-guard');
                    form.submit();
                    return;
                }
                for (var s = 0; s < submitters.length; s++) { submitters[s].disabled = false; }
                window.alert((data && data.error) ? data.error : checkingMsg);
            }).catch(function() {
                for (var s = 0; s < submitters.length; s++) { submitters[s].disabled = false; }
                window.alert(checkingMsg);
            });
        }, true);
    }
    function inject() {
        if (window.tmCertCandidateFieldSkip) { return true; }
        if (document.querySelector('input[name=\"' + fieldName + '\"]')) {
            bindSubmitGuard();
            return true;
        }
        if (document.querySelector('input[name=\"profile_field_tm_candidate_id\"]')) {
            bindSubmitGuard();
            return true;
        }
        var dept = document.querySelector('input[name=\"department\"]');
        var inst = document.querySelector('input[name=\"institution\"]');
        var refInput = dept || inst;
        if (!refInput) { return false; }
        var ref = refInput.closest('.form-group') || refInput.closest('.fitem') || refInput.parentNode;
        if (!ref || !ref.parentNode) { return false; }
        var row = buildRow(ref);
        if (!row) { return false; }
        if (dept) {
            var deptRow = dept.closest('.form-group') || dept.closest('.fitem') || dept.parentNode;
            deptRow.parentNode.insertBefore(row, deptRow);
        } else {
            ref.parentNode.insertBefore(row, ref.nextSibling);
        }
        bindSubmitGuard();
        return true;
    }
    var tries = 0;
    function run() {
        if (inject()) { return; }
        tries += 1;
        if (tries < 40) { setTimeout(run, 100); }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
        ");

        return $out;
    }

    /**
     * Persist POST value after core user save (admin advanced edit only).
     *
     * @param int $userid
     */
    public static function save_from_request(int $userid): void {
        if ($userid < 2) {
            return;
        }
        if (!has_capability('moodle/user:update', \context_system::instance())) {
            return;
        }
        // Only when our field was posted (injected form) or profile field path ran separately.
        $raw = optional_param(self::FIELDNAME, null, PARAM_RAW);
        if ($raw === null) {
            return;
        }

        require_once(__DIR__ . '/candidate_manager.php');
        $value = trim((string)$raw);
        $err = candidate_manager::validate_candidate_id_change($userid, $value);
        if ($err !== null) {
            \core\notification::error($err);
            return;
        }
        try {
            candidate_manager::change_candidate_id($userid, $value);
        } catch (\moodle_exception $e) {
            \core\notification::error($e->getMessage());
        }
    }
}
