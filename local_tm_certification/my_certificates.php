<?php
/**
 * My certificates — instructor recognition + exam customcert list.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/permissions_manager.php');
require_once(__DIR__ . '/classes/catalog_manager.php');
require_once(__DIR__ . '/classes/session_manager.php');
require_once(__DIR__ . '/classes/certificate_helper.php');
require_once(__DIR__ . '/classes/candidate_manager.php');

use local_tm_certification\candidate_manager;
use local_tm_certification\catalog_manager;
use local_tm_certification\certificate_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
permissions_manager::require_view_access();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/my_certificates.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cert_page_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('cert_page_title', 'local_tm_certification'));

$levels = session_manager::level_options();
$isinstructor = permissions_manager::user_can_instruct();
$instlevels = $isinstructor ? catalog_manager::get_user_instructor_levels((int)$USER->id) : [];
$profiles = $isinstructor
    ? catalog_manager::get_instructor_cert_profiles([(int)$USER->id])
    : [];
$profile = $profiles[(int)$USER->id] ?? null;

$examcerts = certificate_helper::get_user_exam_certificates((int)$USER->id);

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');
echo html_writer::tag('h2', get_string('cert_page_title', 'local_tm_certification'));
echo html_writer::tag('p', get_string('cert_page_intro', 'local_tm_certification'), ['class' => 'text-muted']);

if ($isinstructor) {
    echo html_writer::start_div('tm-cert-card mb-3');
    echo html_writer::tag('h3', get_string('cert_section_instructor', 'local_tm_certification'));
    if (empty($instlevels)) {
        echo html_writer::tag('p', get_string('cert_instructor_none', 'local_tm_certification'), ['class' => 'text-muted']);
    } else {
        $levelstatuses = catalog_manager::get_instructor_levels_status((int)$USER->id);
        echo html_writer::start_tag('ul');
        foreach ($instlevels as $lv) {
            $st = $levelstatuses[$lv] ?? null;
            $statuslabel = get_string(
                'cert_instr_status_' . ($st['status'] ?? 'pending'),
                'local_tm_certification'
            );
            $line = get_string('cert_instructor_level_item', 'local_tm_certification', $levels[$lv] ?? $lv)
                . ' — ' . $statuslabel;
            if ($st && !empty($st['months'])) {
                $line .= ' · ' . get_string('cert_instructor_validity_months', 'local_tm_certification',
                    (int)$st['months']);
            }
            echo html_writer::tag('li', $line);
            $deadline = $st
                ? catalog_manager::format_renewal_deadline_label($st)
                : get_string('cert_renewal_deadline_pending', 'local_tm_certification');
            echo html_writer::tag('li',
                get_string('cert_renewal_deadline_label', 'local_tm_certification') . $deadline,
                ['class' => 'text-muted', 'style' => 'list-style: none; margin-left: 1.25rem;']
            );
        }
        echo html_writer::end_tag('ul');
        if (catalog_manager::instructor_extend_by_teaching()) {
            echo html_writer::tag('p', get_string('cert_extend_teaching_on', 'local_tm_certification'),
                ['class' => 'text-muted small']);
        }
        if ($profile) {
            $bits = [];
            if (!empty($profile->certifiedat)) {
                $bits[] = get_string('instructor_certifiedat', 'local_tm_certification') . '：'
                    . userdate((int)$profile->certifiedat, get_string('strftimedate', 'langconfig'));
            }
            if (!empty($profile->renewedat)) {
                $bits[] = get_string('instructor_renewedat', 'local_tm_certification') . '：'
                    . userdate((int)$profile->renewedat, get_string('strftimedate', 'langconfig'));
            }
            if (!empty($bits)) {
                echo html_writer::tag('p', implode(' · ', $bits), ['class' => 'text-muted']);
            }
        }
    }
    echo html_writer::end_div();
}

echo html_writer::start_div('tm-cert-card mb-3');
echo html_writer::tag('h3', get_string('cert_section_exam', 'local_tm_certification'));

if (!certificate_helper::is_customcert_installed()) {
    echo html_writer::tag('p', get_string('cert_exam_no_plugin', 'local_tm_certification'), ['class' => 'text-muted']);
} else if (empty($examcerts)) {
    echo html_writer::tag('p', get_string('cert_exam_empty', 'local_tm_certification'), ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable tm-cert-table';
    $table->head = [
        get_string('cert_col_level', 'local_tm_certification'),
        get_string('cert_col_course', 'local_tm_certification'),
        get_string('cert_col_status', 'local_tm_certification'),
        get_string('cert_col_issued', 'local_tm_certification'),
        get_string('cert_col_action', 'local_tm_certification'),
    ];
    foreach ($examcerts as $row) {
        $lv = (string)($row->certlevel ?? '');
        $levellabel = $levels[$lv] ?? ($lv !== '' ? $lv : '—');
        $statuslabel = ((int)$row->issueid > 0)
            ? get_string('cert_status_issued', 'local_tm_certification')
            : get_string('cert_status_receivable', 'local_tm_certification');
        $issued = !empty($row->timecreated)
            ? userdate((int)$row->timecreated, get_string('strftimedatetimeshort', 'langconfig'))
            : '—';
        $action = html_writer::link(
            certificate_helper::get_own_download_url((int)$row->cmid),
            get_string('cert_action_view', 'local_tm_certification'),
            ['class' => 'btn btn-sm btn-primary']
        );
        $table->data[] = [
            s($levellabel),
            format_string($row->coursename),
            $statuslabel,
            $issued,
            $action,
        ];
    }
    echo html_writer::table($table);
}

$candidateid = candidate_manager::get_candidate_id((int)$USER->id);
if ($candidateid) {
    echo html_writer::tag('p',
        get_string('cert_candidate_id_line', 'local_tm_certification', $candidateid),
        ['class' => 'text-muted mt-2']
    );
}
echo html_writer::end_div();

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/tm_certification/index.php'),
        get_string('nav_dashboard', 'local_tm_certification'),
        ['class' => 'btn btn-secondary btn-sm']
    ),
    'mt-3'
);
echo html_writer::end_div();
echo $OUTPUT->footer();
