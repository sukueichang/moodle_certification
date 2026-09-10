<?php
/**
 * Phased migration: legacy lastname=candidate ID → candidates table, then split names.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/legacy_name_migrator.php');

use local_tm_certification\legacy_name_migrator;
use local_tm_certification\permissions_manager;

require_login();
if (!permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/legacy_name_migrate.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('nav_legacy_migrate', 'local_tm_certification'));
$PAGE->set_heading(get_string('nav_legacy_migrate', 'local_tm_certification'));

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$resultnotice = null;
$resulttype = \core\output\notification::NOTIFY_SUCCESS;

if ($action !== '' && data_submitted() && confirm_sesskey()) {
    if ($action === 'phase1') {
        $userids = optional_param_array('userids', [], PARAM_INT);
        $userids = array_values(array_filter(array_map('intval', $userids), static function($id) {
            return $id > 1;
        }));
        if (empty($userids)) {
            $resultnotice = get_string('legacy_migrate_none_selected', 'local_tm_certification');
            $resulttype = \core\output\notification::NOTIFY_ERROR;
        } else {
            $result = legacy_name_migrator::run_phase1($userids);
            $resultnotice = get_string('legacy_migrate_phase1_done', 'local_tm_certification', (object)[
                'bound' => $result['bound'],
                'ensured' => $result['ensured'],
                'skipped' => $result['skipped'],
                'errors' => count($result['errors']),
            ]);
            if (!empty($result['errors'])) {
                $resulttype = \core\output\notification::NOTIFY_WARNING;
            }
        }
    } else if ($action === 'phase2') {
        $verify = legacy_name_migrator::verify_phase1();
        if (!$verify['ok']) {
            $resultnotice = get_string('legacy_migrate_phase2_blocked', 'local_tm_certification', $verify['ready']);
            $resulttype = \core\output\notification::NOTIFY_ERROR;
        } else {
            $userids = optional_param_array('userids', [], PARAM_INT);
            $userids = array_values(array_filter(array_map('intval', $userids), static function($id) {
                return $id > 1;
            }));
            if (empty($userids)) {
                $resultnotice = get_string('legacy_migrate_none_selected', 'local_tm_certification');
                $resulttype = \core\output\notification::NOTIFY_ERROR;
            } else {
                $result = legacy_name_migrator::run_phase2($userids);
                $resultnotice = get_string('legacy_migrate_phase2_done', 'local_tm_certification', (object)[
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'errors' => count($result['errors']),
                ]);
                if (!empty($result['errors'])) {
                    $resulttype = \core\output\notification::NOTIFY_WARNING;
                }
            }
        }
    } else if ($action === 'resolve') {
        $userid = required_param('userid', PARAM_INT);
        $decision = optional_param('decision', '', PARAM_ALPHANUMEXT);
        $customid = trim(optional_param('customid', '', PARAM_TEXT));
        if ($decision === '') {
            redirect($PAGE->url, get_string('legacy_migrate_resolve_pick_required', 'local_tm_certification'), null,
                \core\output\notification::NOTIFY_ERROR);
        }
        try {
            $resolved = legacy_name_migrator::resolve_conflict($userid, $decision, $customid);
            if ($decision === legacy_name_migrator::DECISION_RECLAIM) {
                $resultnotice = get_string('legacy_migrate_resolve_done_reclaim', 'local_tm_certification', (object)[
                    'legacyid' => $resolved['candidateid'],
                    'holdernew' => $resolved['holder_new_id'],
                ]);
            } else if ($decision === legacy_name_migrator::DECISION_KEEP_EXISTING) {
                $resultnotice = get_string('legacy_migrate_resolve_done_keep', 'local_tm_certification', $resolved['candidateid']);
            } else {
                $resultnotice = get_string('legacy_migrate_resolve_done', 'local_tm_certification', $resolved['candidateid']);
            }
        } catch (moodle_exception $e) {
            $resultnotice = $e->getMessage();
            $resulttype = \core\output\notification::NOTIFY_ERROR;
        } catch (Throwable $e) {
            $resultnotice = get_string('legacy_migrate_resolve_failed', 'local_tm_certification');
            $resulttype = \core\output\notification::NOTIFY_ERROR;
        }
    }
    redirect($PAGE->url, $resultnotice, null, $resulttype);
}

$phase1 = legacy_name_migrator::scan_phase1();
$verify = legacy_name_migrator::verify_phase1();
$phase2 = $verify['ok'] ? legacy_name_migrator::scan_phase2() : ['rows' => [], 'counts' => []];

/**
 * Render a simple HTML table from row arrays.
 *
 * @param string[] $headers
 * @param string[][] $body
 * @return string
 */
function local_tm_certification_legacy_table(array $headers, array $body): string {
    if (empty($body)) {
        return html_writer::div(get_string('legacy_migrate_empty_rows', 'local_tm_certification'), 'text-muted mb-3');
    }
    $html = html_writer::start_tag('table', ['class' => 'generaltable table-sm mb-3']);
    $html .= html_writer::start_tag('thead');
    $html .= html_writer::start_tag('tr');
    foreach ($headers as $h) {
        $html .= html_writer::tag('th', $h);
    }
    $html .= html_writer::end_tag('tr');
    $html .= html_writer::end_tag('thead');
    $html .= html_writer::start_tag('tbody');
    foreach ($body as $cells) {
        $html .= html_writer::start_tag('tr');
        foreach ($cells as $c) {
            $html .= html_writer::tag('td', $c);
        }
        $html .= html_writer::end_tag('tr');
    }
    $html .= html_writer::end_tag('tbody');
    $html .= html_writer::end_tag('table');
    return $html;
}

/**
 * Conflict decision form for one row.
 *
 * @param array $row
 * @return string
 */
function local_tm_certification_legacy_resolve_form(array $row): string {
    $userid = (int)$row['userid'];
    $status = (string)$row['status'];
    $html = html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'tm-legacy-resolve mb-0',
        'onsubmit' => 'return confirm(' . json_encode(get_string('legacy_migrate_resolve_confirm', 'local_tm_certification')) . ');',
    ]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'resolve']);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);

    $radios = '';
    if ($status === legacy_name_migrator::STATUS_CONFLICT_TAKEN) {
        $radios .= local_tm_certification_legacy_radio(
            $userid,
            legacy_name_migrator::DECISION_ASSIGN_CUSTOM,
            get_string('legacy_migrate_decision_assign_custom', 'local_tm_certification')
        );
        $radios .= local_tm_certification_legacy_radio(
            $userid,
            legacy_name_migrator::DECISION_RECLAIM,
            get_string('legacy_migrate_decision_reclaim', 'local_tm_certification', $row['candidateid'])
        );
    } else if ($status === legacy_name_migrator::STATUS_CONFLICT_DIFFERENT) {
        $radios .= local_tm_certification_legacy_radio(
            $userid,
            legacy_name_migrator::DECISION_KEEP_EXISTING,
            get_string('legacy_migrate_decision_keep_existing', 'local_tm_certification', $row['existing_id'])
        );
        $radios .= local_tm_certification_legacy_radio(
            $userid,
            legacy_name_migrator::DECISION_SWITCH_LASTNAME,
            get_string('legacy_migrate_decision_switch_lastname', 'local_tm_certification', $row['candidateid'])
        );
        $radios .= local_tm_certification_legacy_radio(
            $userid,
            legacy_name_migrator::DECISION_ASSIGN_CUSTOM,
            get_string('legacy_migrate_decision_assign_custom_mismatch', 'local_tm_certification')
        );
    }

    $html .= html_writer::div($radios, 'mb-1');
    $html .= html_writer::start_div('form-inline');
    $html .= html_writer::tag('label', get_string('legacy_migrate_custom_id', 'local_tm_certification'), [
        'for' => 'customid_' . $userid,
        'class' => 'mr-1 mb-0 small',
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'customid',
        'id' => 'customid_' . $userid,
        'value' => '',
        'maxlength' => 6,
        'size' => 8,
        'class' => 'form-control form-control-sm mr-2',
        'pattern' => '[0-9]{6}',
        'placeholder' => '115000',
    ]);
    $html .= html_writer::tag('button', get_string('legacy_migrate_resolve_apply', 'local_tm_certification'), [
        'type' => 'submit',
        'class' => 'btn btn-sm btn-secondary',
    ]);
    $html .= html_writer::end_div();
    $html .= html_writer::tag('p', get_string('legacy_migrate_resolve_pick_hint', 'local_tm_certification'),
        ['class' => 'text-muted small mb-0 mt-1']);
    $html .= html_writer::end_tag('form');
    return $html;
}

/**
 * @param int $userid
 * @param string $value
 * @param string $label
 * @param bool $checked
 * @return string
 */
function local_tm_certification_legacy_radio(int $userid, string $value, string $label, bool $checked = false): string {
    $id = 'dec_' . $userid . '_' . $value;
    $attrs = [
        'type' => 'radio',
        'name' => 'decision',
        'id' => $id,
        'value' => $value,
        'class' => 'mr-1',
    ];
    if ($checked) {
        $attrs['checked'] = 'checked';
    }
    $input = html_writer::empty_tag('input', $attrs);
    return html_writer::div(
        html_writer::tag('label', $input . ' ' . $label, ['for' => $id, 'class' => 'mb-0 small']),
        'mb-1'
    );
}

/**
 * Checkbox cell for selective phase runs.
 *
 * @param int $userid
 * @param string $group CSS group class suffix (phase1|phase2)
 * @return string
 */
function local_tm_certification_legacy_row_checkbox(int $userid, string $group): string {
    $id = 'tm_sel_' . $group . '_' . $userid;
    $input = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'userids[]',
        'id' => $id,
        'value' => $userid,
        'class' => 'tm-legacy-row-cb',
        'checked' => 'checked',
        'data-tm-group' => $group,
    ]);
    return html_writer::tag('label', $input, ['for' => $id, 'class' => 'mb-0']);
}

/**
 * Select-all / select-none toolbar for a checkbox group.
 *
 * @param string $group
 * @return string
 */
function local_tm_certification_legacy_select_toolbar(string $group): string {
    $all = html_writer::tag('button', get_string('legacy_migrate_select_all', 'local_tm_certification'), [
        'type' => 'button',
        'class' => 'btn btn-sm btn-link p-0 mr-2 tm-legacy-select-all',
        'data-tm-group' => $group,
        'data-tm-state' => '1',
    ]);
    $none = html_writer::tag('button', get_string('legacy_migrate_select_none', 'local_tm_certification'), [
        'type' => 'button',
        'class' => 'btn btn-sm btn-link p-0 tm-legacy-select-all',
        'data-tm-group' => $group,
        'data-tm-state' => '0',
    ]);
    $hint = trim(get_string('legacy_migrate_select_hint', 'local_tm_certification'));
    $label = $hint !== '' ? ($hint . ' ') : '';
    return html_writer::div($label . $all . $none, 'text-muted small mb-2');
}

/**
 * Header checkbox for a group (toggles all row boxes).
 *
 * @param string $group
 * @return string
 */
function local_tm_certification_legacy_header_checkbox(string $group): string {
    $id = 'tm_sel_' . $group . '_all';
    $input = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => $id,
        'class' => 'tm-legacy-header-cb',
        'data-tm-group' => $group,
        'checked' => 'checked',
        'title' => get_string('legacy_migrate_select_all', 'local_tm_certification'),
    ]);
    return html_writer::tag('label', $input, ['for' => $id, 'class' => 'mb-0']);
}

$statuslabel = static function(string $status): string {
    $key = 'legacy_migrate_status_' . $status;
    if (get_string_manager()->string_exists($key, 'local_tm_certification')) {
        return get_string($key, 'local_tm_certification');
    }
    return $status;
};

echo $OUTPUT->header();

echo html_writer::tag('p', get_string('legacy_migrate_intro', 'local_tm_certification'), ['class' => 'mb-3']);

// ---- Summary ----
echo html_writer::tag('h3', get_string('legacy_migrate_summary', 'local_tm_certification'));
echo html_writer::start_div('mb-4');
echo html_writer::tag('span',
    get_string('legacy_migrate_stat_legacy', 'local_tm_certification', $verify['legacy_total']),
    ['class' => 'badge badge-secondary mr-1']);
echo html_writer::tag('span',
    get_string('legacy_migrate_stat_ready', 'local_tm_certification', $verify['ready']),
    ['class' => 'badge badge-info mr-1']);
echo html_writer::tag('span',
    get_string('legacy_migrate_stat_bound', 'local_tm_certification', $verify['already_bound']),
    ['class' => 'badge badge-success mr-1']);
echo html_writer::tag('span',
    get_string('legacy_migrate_stat_conflicts', 'local_tm_certification', $verify['conflicts']),
    ['class' => 'badge badge-warning mr-1']);
echo html_writer::end_div();

if ($verify['ok']) {
    echo $OUTPUT->notification(get_string('legacy_migrate_verify_ok', 'local_tm_certification', $verify['phase2_eligible']),
        \core\output\notification::NOTIFY_SUCCESS);
} else {
    echo $OUTPUT->notification(get_string('legacy_migrate_verify_pending', 'local_tm_certification', $verify['ready']),
        \core\output\notification::NOTIFY_INFO);
}

echo $OUTPUT->notification(get_string('legacy_migrate_bound_explain', 'local_tm_certification'),
    \core\output\notification::NOTIFY_INFO);

// Prefix breakdown (e.g. 114 / 115).
$byprefix = legacy_name_migrator::summarize_by_prefix($phase1['rows']);
if (!empty($byprefix)) {
    echo html_writer::tag('h4', get_string('legacy_migrate_prefix_title', 'local_tm_certification'));
    $prefixbody = [];
    foreach ($byprefix as $prefix => $st) {
        $prefixbody[] = [
            s($prefix),
            (string)$st['total'],
            (string)$st['ready'],
            (string)$st['already_bound'],
            (string)($st['conflict_taken'] + $st['conflict_different']),
            get_string('legacy_migrate_bound_via_detail', 'local_tm_certification', (object)[
                'table' => $st['bound_via_table'],
                'username' => $st['bound_via_username'],
            ]),
        ];
    }
    echo local_tm_certification_legacy_table([
        get_string('legacy_migrate_col_prefix', 'local_tm_certification'),
        get_string('legacy_migrate_col_total', 'local_tm_certification'),
        get_string('legacy_migrate_stat_ready_short', 'local_tm_certification'),
        get_string('legacy_migrate_stat_bound_short', 'local_tm_certification'),
        get_string('legacy_migrate_stat_conflicts_short', 'local_tm_certification'),
        get_string('legacy_migrate_col_bound_via', 'local_tm_certification'),
    ], $prefixbody);
}

// ---- Phase 1 ----
echo html_writer::tag('h3', get_string('legacy_migrate_phase1_title', 'local_tm_certification'));
echo html_writer::tag('p', get_string('legacy_migrate_phase1_desc', 'local_tm_certification'), ['class' => 'text-muted']);

$readyrows = [];
$boundrows = [];
$conflictrows = [];
foreach ($phase1['rows'] as $row) {
    if ($row['status'] === legacy_name_migrator::STATUS_READY) {
        $readyrows[] = [
            local_tm_certification_legacy_row_checkbox((int)$row['userid'], 'phase1'),
            s($row['candidateid']),
            s($row['lastname'] . ' ' . $row['firstname']),
            s($row['email']),
            s($row['username']),
            s($statuslabel($row['status'])),
        ];
    } else if ($row['status'] === legacy_name_migrator::STATUS_ALREADY_BOUND) {
        $via = ($row['bound_via'] ?? '') === 'username'
            ? get_string('legacy_migrate_via_username', 'local_tm_certification')
            : get_string('legacy_migrate_via_table', 'local_tm_certification');
        $boundrows[] = [
            s($row['candidateid']),
            s($row['lastname'] . ' ' . $row['firstname']),
            s($row['email']),
            s($row['username']),
            $via,
        ];
    } else {
        $note = '';
        if (!empty($row['holder_email'])) {
            $note = s($row['holder_fullname'] . ' / ' . $row['holder_email']);
        } else if (!empty($row['existing_id'])) {
            $note = s(get_string('legacy_migrate_existing_id', 'local_tm_certification', $row['existing_id']));
        }
        $conflictrows[] = [
            s($row['candidateid']),
            s($row['lastname'] . ' ' . $row['firstname']),
            s($row['email']),
            s($row['username']),
            s($statuslabel($row['status'])),
            $note,
            local_tm_certification_legacy_resolve_form($row),
        ];
    }
}

echo html_writer::tag('h4', get_string('legacy_migrate_ready_list', 'local_tm_certification', count($readyrows)));
echo html_writer::tag('p', get_string('legacy_migrate_ready_hint', 'local_tm_certification'), ['class' => 'text-muted']);

if (!empty($readyrows)) {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'mb-4 tm-legacy-phase-form',
        'data-tm-group' => 'phase1',
        'data-tm-confirm' => get_string('legacy_migrate_phase1_confirm', 'local_tm_certification'),
        'data-tm-none' => get_string('legacy_migrate_none_selected', 'local_tm_certification'),
        'data-tm-run-tpl' => get_string('legacy_migrate_phase1_run', 'local_tm_certification', '__COUNT__'),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'phase1']);
    echo local_tm_certification_legacy_select_toolbar('phase1');
    echo local_tm_certification_legacy_table([
        local_tm_certification_legacy_header_checkbox('phase1'),
        get_string('candidate_id', 'local_tm_certification'),
        get_string('fullname'),
        get_string('email'),
        get_string('username'),
        get_string('legacy_migrate_col_status', 'local_tm_certification'),
    ], $readyrows);
    echo html_writer::tag('button', get_string('legacy_migrate_phase1_run', 'local_tm_certification', count($readyrows)), [
        'type' => 'submit',
        'class' => 'btn btn-primary tm-legacy-run-btn',
    ]);
    echo html_writer::end_tag('form');
} else {
    echo html_writer::tag('p', get_string('legacy_migrate_phase1_nothing', 'local_tm_certification'), ['class' => 'text-muted mb-4']);
}

echo html_writer::start_tag('details', ['class' => 'mb-3']);
echo html_writer::tag('summary', get_string('legacy_migrate_bound_list', 'local_tm_certification', count($boundrows)));
echo html_writer::tag('p', get_string('legacy_migrate_bound_list_hint', 'local_tm_certification'), ['class' => 'text-muted']);
echo local_tm_certification_legacy_table([
    get_string('candidate_id', 'local_tm_certification'),
    get_string('fullname'),
    get_string('email'),
    get_string('username'),
    get_string('legacy_migrate_col_bound_via', 'local_tm_certification'),
], $boundrows);
echo html_writer::end_tag('details');

echo html_writer::tag('h4', get_string('legacy_migrate_conflict_list', 'local_tm_certification', count($conflictrows)));
if (!empty($conflictrows)) {
    echo html_writer::tag('p', get_string('legacy_migrate_conflict_hint', 'local_tm_certification'),
        ['class' => 'text-muted']);
}
echo local_tm_certification_legacy_table([
    get_string('candidate_id', 'local_tm_certification'),
    get_string('fullname'),
    get_string('email'),
    get_string('username'),
    get_string('legacy_migrate_col_status', 'local_tm_certification'),
    get_string('legacy_migrate_col_holder', 'local_tm_certification'),
    get_string('legacy_migrate_col_decision', 'local_tm_certification'),
], $conflictrows);

// ---- Phase 2 ----
echo html_writer::tag('h3', get_string('legacy_migrate_phase2_title', 'local_tm_certification'));
echo html_writer::tag('p', get_string('legacy_migrate_phase2_desc', 'local_tm_certification'), ['class' => 'text-muted']);

if (!$verify['ok']) {
    echo $OUTPUT->notification(get_string('legacy_migrate_phase2_blocked', 'local_tm_certification', $verify['ready']),
        \core\output\notification::NOTIFY_WARNING);
} else {
    $p2ready = [];
    $p2short = [];
    foreach ($phase2['rows'] as $row) {
        $preview = ($row['new_lastname'] !== null)
            ? s($row['new_lastname'] . ' / ' . $row['new_firstname'])
            : '—';
        if ($row['status'] === legacy_name_migrator::STATUS_PHASE2_READY) {
            $p2ready[] = [
                local_tm_certification_legacy_row_checkbox((int)$row['userid'], 'phase2'),
                s($row['candidateid']),
                s($row['firstname']),
                $preview,
                s($row['email']),
                s($row['username']),
                s($statuslabel($row['status'])),
            ];
        } else {
            $p2short[] = [
                s($row['candidateid']),
                s($row['firstname']),
                $preview,
                s($row['email']),
                s($row['username']),
                s($statuslabel($row['status'])),
            ];
        }
    }

    echo html_writer::tag('h4', get_string('legacy_migrate_phase2_ready_list', 'local_tm_certification', count($p2ready)));
    if (!empty($p2ready)) {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'class' => 'mb-4 tm-legacy-phase-form',
            'data-tm-group' => 'phase2',
            'data-tm-confirm' => get_string('legacy_migrate_phase2_confirm', 'local_tm_certification'),
            'data-tm-none' => get_string('legacy_migrate_none_selected', 'local_tm_certification'),
            'data-tm-run-tpl' => get_string('legacy_migrate_phase2_run', 'local_tm_certification', '__COUNT__'),
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'phase2']);
        echo local_tm_certification_legacy_select_toolbar('phase2');
        echo local_tm_certification_legacy_table([
            local_tm_certification_legacy_header_checkbox('phase2'),
            get_string('candidate_id', 'local_tm_certification'),
            get_string('legacy_migrate_col_old_firstname', 'local_tm_certification'),
            get_string('legacy_migrate_col_new_name', 'local_tm_certification'),
            get_string('email'),
            get_string('username'),
            get_string('legacy_migrate_col_status', 'local_tm_certification'),
        ], $p2ready);
        echo html_writer::tag('button', get_string('legacy_migrate_phase2_run', 'local_tm_certification', count($p2ready)), [
            'type' => 'submit',
            'class' => 'btn btn-warning tm-legacy-run-btn',
        ]);
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::tag('p', get_string('legacy_migrate_phase2_nothing', 'local_tm_certification'), ['class' => 'text-muted mb-4']);
    }

    if (!empty($p2short)) {
        echo html_writer::tag('h4', get_string('legacy_migrate_phase2_skip_list', 'local_tm_certification', count($p2short)));
        echo local_tm_certification_legacy_table([
            get_string('candidate_id', 'local_tm_certification'),
            get_string('legacy_migrate_col_old_firstname', 'local_tm_certification'),
            get_string('legacy_migrate_col_new_name', 'local_tm_certification'),
            get_string('email'),
            get_string('username'),
            get_string('legacy_migrate_col_status', 'local_tm_certification'),
        ], $p2short);
    }
}

$js = <<<'JS'
(function() {
    function rowBoxes(group) {
        return Array.prototype.slice.call(document.querySelectorAll(
            'input.tm-legacy-row-cb[data-tm-group="' + group + '"]'
        ));
    }
    function refresh(group) {
        var boxes = rowBoxes(group);
        var n = boxes.filter(function(cb) { return cb.checked; }).length;
        var form = document.querySelector('form.tm-legacy-phase-form[data-tm-group="' + group + '"]');
        if (!form) { return; }
        var btn = form.querySelector('.tm-legacy-run-btn');
        var tpl = form.getAttribute('data-tm-run-tpl') || '';
        if (btn && tpl) {
            btn.textContent = tpl.replace('__COUNT__', String(n));
            btn.disabled = n === 0;
        }
        var header = document.querySelector('input.tm-legacy-header-cb[data-tm-group="' + group + '"]');
        if (header) {
            header.checked = boxes.length > 0 && n === boxes.length;
            header.indeterminate = n > 0 && n < boxes.length;
        }
    }
    document.querySelectorAll('input.tm-legacy-row-cb').forEach(function(cb) {
        cb.addEventListener('change', function() {
            refresh(cb.getAttribute('data-tm-group'));
        });
    });
    document.querySelectorAll('input.tm-legacy-header-cb').forEach(function(header) {
        header.addEventListener('change', function() {
            var group = header.getAttribute('data-tm-group');
            rowBoxes(group).forEach(function(cb) { cb.checked = header.checked; });
            refresh(group);
        });
    });
    document.querySelectorAll('button.tm-legacy-select-all').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group = btn.getAttribute('data-tm-group');
            var on = btn.getAttribute('data-tm-state') === '1';
            rowBoxes(group).forEach(function(cb) { cb.checked = on; });
            refresh(group);
        });
    });
    document.querySelectorAll('form.tm-legacy-phase-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var group = form.getAttribute('data-tm-group');
            var n = rowBoxes(group).filter(function(cb) { return cb.checked; }).length;
            if (n === 0) {
                e.preventDefault();
                window.alert(form.getAttribute('data-tm-none') || '');
                return;
            }
            if (!window.confirm(form.getAttribute('data-tm-confirm') || '')) {
                e.preventDefault();
            }
        });
        refresh(form.getAttribute('data-tm-group'));
    });
})();
JS;
echo html_writer::tag('script', $js);

echo $OUTPUT->footer();
