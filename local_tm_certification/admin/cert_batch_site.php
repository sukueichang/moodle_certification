<?php
/**
 * Site-wide batch certificate download (search by level → cart → summary → zip).
 * Dashboard entry for manage / approve / instruct. Not tied to a session.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/certificate_helper.php');
require_once(__DIR__ . '/../classes/candidate_manager.php');
require_once(__DIR__ . '/../classes/dashboard_helper.php');

use local_tm_certification\candidate_manager;
use local_tm_certification\certificate_helper;
use local_tm_certification\dashboard_helper;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();

if (!permissions_manager::user_can_batch_download_certs()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/cert_batch_site.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cert_batch_site_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('cert_batch_site_title', 'local_tm_certification'));

$levels = session_manager::level_options();
$validlevels = array_flip(array_keys($levels));
$action = optional_param('action', '', PARAM_ALPHA);
$q = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$showsummary = optional_param('summary', 0, PARAM_INT);

$dashurl = new moodle_url('/local/tm_certification/index.php');
$baseurl = new moodle_url('/local/tm_certification/admin/cert_batch_site.php');

$cart_key = static function(int $userid, string $level): string {
    return $userid . ':' . $level;
};

$normalize_cart = static function(array $raw) use ($validlevels, $cart_key): array {
    $out = [];
    $seen = [];
    foreach ($raw as $item) {
        $userid = 0;
        $level = '';
        if (is_array($item)) {
            $userid = (int)($item['userid'] ?? 0);
            $level = trim((string)($item['level'] ?? ''));
        } else if (is_object($item)) {
            $userid = (int)($item->userid ?? 0);
            $level = trim((string)($item->level ?? ''));
        } else if (is_string($item) && strpos($item, ':') !== false) {
            [$uidpart, $level] = explode(':', $item, 2);
            $userid = (int)$uidpart;
            $level = trim($level);
        }
        // Legacy cart stored bare userids — drop (cannot infer which level was meant).
        if ($userid <= 0 || $level === '' || !isset($validlevels[$level])) {
            continue;
        }
        $k = $cart_key($userid, $level);
        if (isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $out[] = ['userid' => $userid, 'level' => $level];
    }
    return $out;
};

$get_cart = static function() use ($normalize_cart): array {
    global $SESSION;
    $key = 'tm_cert_batch_site_cart';
    if (empty($SESSION->$key) || !is_array($SESSION->$key)) {
        return [];
    }
    return $normalize_cart($SESSION->$key);
};
$set_cart = static function(array $items) use ($normalize_cart): void {
    global $SESSION;
    $SESSION->tm_cert_batch_site_cart = $normalize_cart($items);
};

$in_cart = static function(array $cart, int $userid, string $level) use ($cart_key): bool {
    $want = $cart_key($userid, $level);
    foreach ($cart as $item) {
        if ($cart_key((int)$item['userid'], (string)$item['level']) === $want) {
            return true;
        }
    }
    return false;
};

if ($action !== '' && confirm_sesskey()) {
    if ($action === 'add') {
        $userid = required_param('userid', PARAM_INT);
        $level = required_param('level', PARAM_ALPHANUMEXT);
        $cart = $get_cart();
        if (isset($validlevels[$level]) && !$in_cart($cart, $userid, $level)) {
            $cart[] = ['userid' => $userid, 'level' => $level];
            $set_cart($cart);
        }
        redirect(new moodle_url($baseurl, ['q' => $q]));
    }
    if ($action === 'remove') {
        $userid = required_param('userid', PARAM_INT);
        $level = required_param('level', PARAM_ALPHANUMEXT);
        $want = $cart_key($userid, $level);
        $cart = array_values(array_filter($get_cart(), static function(array $item) use ($want, $cart_key): bool {
            return $cart_key((int)$item['userid'], (string)$item['level']) !== $want;
        }));
        $set_cart($cart);
        redirect(new moodle_url($baseurl, ['q' => $q]));
    }
    if ($action === 'clear') {
        $set_cart([]);
        redirect($baseurl);
    }
    if ($action === 'download') {
        $raw = optional_param_array('sel', [], PARAM_RAW);
        $selections = $normalize_cart($raw);
        if (empty($selections)) {
            $selections = $get_cart();
        }
        $result = certificate_helper::build_zip_for_selections($selections, 'cert_site_selected');
        if (empty($result['ok']) || empty($result['path']) || !is_readable($result['path'])) {
            redirect(
                new moodle_url($baseurl, ['q' => $q, 'summary' => 1]),
                get_string('cert_batch_zip_empty', 'local_tm_certification'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $bytes = file_get_contents($result['path']);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $bytes;
        @unlink($result['path']);
        die;
    }
}

$load_users = static function(array $userids): array {
    global $DB;
    $userids = array_values(array_filter(array_map('intval', $userids)));
    if (empty($userids)) {
        return [];
    }
    [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
    $rows = $DB->get_records_select('user', "id $insql AND deleted = 0", $params,
        'lastname ASC, firstname ASC',
        'id,email,username,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic,institution');
    $cids = candidate_manager::get_candidate_ids_for_users($userids);
    foreach ($rows as $row) {
        $row->candidateid = $cids[(int)$row->id]
            ?? (candidate_manager::is_candidate_id_format((string)$row->username) ? (string)$row->username : '');
    }
    return $rows;
};

/**
 * @param array<int,array{userid:int,level:string}> $selections
 * @return array{rows:array,pdfcount:int,ok_n:int,miss_n:int}
 */
$build_summary = static function(array $selections, array $levelopts) use ($load_users): array {
    $userids = [];
    foreach ($selections as $sel) {
        $userids[] = (int)$sel['userid'];
    }
    $users = $load_users($userids);
    $rows = [];
    $pdfcount = 0;
    $okn = 0;
    $missn = 0;
    foreach ($selections as $sel) {
        $userid = (int)$sel['userid'];
        $level = (string)$sel['level'];
        if (!isset($users[$userid])) {
            continue;
        }
        $u = $users[$userid];
        $info = certificate_helper::get_level_certificate_availability($userid, $level);
        $levellabel = $levelopts[$level] ?? $level;
        if (!empty($info['available'])) {
            $pdfcount++;
            $okn++;
            $statuslabel = ((string)$info['status'] === certificate_helper::STATUS_ISSUED)
                ? get_string('cert_status_issued', 'local_tm_certification')
                : get_string('cert_status_receivable', 'local_tm_certification');
            $result = get_string('cert_batch_result_include', 'local_tm_certification');
            $mod = 'ok';
        } else {
            $missn++;
            $statuslabel = get_string('cert_status_none', 'local_tm_certification');
            $result = get_string('cert_batch_result_skip', 'local_tm_certification');
            $mod = 'bad';
        }
        $rows[] = (object)[
            'user' => $u,
            'level' => $level,
            'levellabel' => $levellabel,
            'statuslabel' => $statuslabel,
            'mod' => $mod,
            'available' => !empty($info['available']),
            'result' => $result,
        ];
    }
    return [
        'rows' => $rows,
        'pdfcount' => $pdfcount,
        'ok_n' => $okn,
        'miss_n' => $missn,
    ];
};

$cart = $get_cart();
$searchhits = ($q !== '') ? candidate_manager::search_examinees($q, 40) : [];
$summary = null;
if ($showsummary && !empty($cart)) {
    $summary = $build_summary($cart, $levels);
}

echo $OUTPUT->header();
echo html_writer::start_div('tm-cert-dash');

echo html_writer::start_div('tm-cert-head');
echo html_writer::start_div();
echo html_writer::tag('h2', get_string('cert_batch_site_title', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-facts');
echo dashboard_helper::render_pill([
    'label' => get_string('cert_batch_mode_search', 'local_tm_certification'),
    'mod' => '',
]);
echo dashboard_helper::render_pill([
    'label' => get_string('cert_batch_by_level', 'local_tm_certification'),
    'mod' => '',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('tm-cert-head-actions');
echo html_writer::link($dashurl, get_string('roster_back', 'local_tm_certification'),
    ['class' => 'btn btn-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();

if (!certificate_helper::is_customcert_installed()) {
    echo $OUTPUT->notification(get_string('cert_download_customcert_missing', 'local_tm_certification'), 'error');
}

echo html_writer::div(
    get_string('cert_batch_level_pick_hint', 'local_tm_certification'),
    'tm-cert-alert-slim'
);

// Search.
echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('cert_batch_search_heading', 'local_tm_certification'));
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $baseurl->out(false),
    'class' => 'tm-cert-search-bar',
]);
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'name' => 'q',
    'value' => $q,
    'placeholder' => get_string('cert_batch_search_placeholder', 'local_tm_certification'),
]);
echo html_writer::tag('button', get_string('search', 'core'), [
    'type' => 'submit',
    'class' => 'btn btn-primary btn-sm',
]);
echo html_writer::tag('p', get_string('cert_batch_search_hint', 'local_tm_certification'),
    ['class' => 'tm-cert-search-hint']);
echo html_writer::end_tag('form');

if ($q !== '') {
    if (empty($searchhits)) {
        echo html_writer::div(get_string('cert_batch_search_empty', 'local_tm_certification'), 'tm-cert-empty');
    } else {
        echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
        echo html_writer::start_tag('thead');
        echo html_writer::tag('tr',
            html_writer::tag('th', get_string('fullname'))
            . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
            . html_writer::tag('th', get_string('email'))
            . html_writer::tag('th', get_string('cert_batch_col_level', 'local_tm_certification'))
            . html_writer::tag('th', get_string('cert_batch_col_action', 'local_tm_certification'))
        );
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
        $anyrow = false;
        foreach ($searchhits as $hit) {
            $uid = (int)$hit->id;
            $userlevels = certificate_helper::get_user_exam_levels($uid);
            if (empty($userlevels)) {
                $anyrow = true;
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', fullname($hit));
                echo html_writer::tag('td', s((string)($hit->candidateid ?? '')), ['class' => 'tm-cert-id']);
                echo html_writer::tag('td', s($hit->email), ['class' => 'tm-cert-muted']);
                echo html_writer::tag('td', get_string('cert_status_none', 'local_tm_certification'),
                    ['class' => 'tm-cert-muted']);
                echo html_writer::tag('td', '—', ['class' => 'tm-cert-muted']);
                echo html_writer::end_tag('tr');
                continue;
            }
            foreach ($userlevels as $lv) {
                $anyrow = true;
                $lvlabel = $levels[$lv] ?? $lv;
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', fullname($hit));
                echo html_writer::tag('td', s((string)($hit->candidateid ?? '')), ['class' => 'tm-cert-id']);
                echo html_writer::tag('td', s($hit->email), ['class' => 'tm-cert-muted']);
                echo html_writer::tag('td', s($lvlabel));
                if ($in_cart($cart, $uid, $lv)) {
                    echo html_writer::tag('td',
                        get_string('cert_batch_already_in_cart', 'local_tm_certification'),
                        ['class' => 'tm-cert-muted']);
                } else {
                    $addurl = new moodle_url('/local/tm_certification/admin/cert_batch_site.php', [
                        'action' => 'add',
                        'userid' => $uid,
                        'level' => $lv,
                        'q' => $q,
                        'sesskey' => sesskey(),
                    ]);
                    echo html_writer::tag('td', html_writer::link($addurl,
                        get_string('cert_batch_add', 'local_tm_certification'),
                        ['class' => 'btn btn-sm btn-secondary']));
                }
                echo html_writer::end_tag('tr');
            }
        }
        if (!$anyrow) {
            echo html_writer::tag('tr', html_writer::tag('td',
                get_string('cert_batch_search_empty', 'local_tm_certification'),
                ['colspan' => 5, 'class' => 'tm-cert-muted']));
        }
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }
}
echo html_writer::end_div();

// Cart.
echo html_writer::start_div('tm-cert-panel');
echo html_writer::start_div('tm-cert-ph');
echo html_writer::tag('h3', get_string('cert_batch_cart_heading', 'local_tm_certification'));
echo html_writer::start_div('tm-cert-ph-ops');
echo dashboard_helper::render_pill([
    'label' => get_string('cert_batch_count', 'local_tm_certification', count($cart)),
    'mod' => '',
]);
if (!empty($cart)) {
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/cert_batch_site.php', [
            'action' => 'clear',
            'sesskey' => sesskey(),
        ]),
        get_string('cert_batch_clear_cart', 'local_tm_certification'),
        ['class' => 'btn btn-sm btn-secondary']
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

if (empty($cart)) {
    echo html_writer::div(get_string('cert_batch_cart_empty', 'local_tm_certification'), 'tm-cert-empty');
} else {
    $cartuids = [];
    foreach ($cart as $item) {
        $cartuids[] = (int)$item['userid'];
    }
    $cartusers = $load_users($cartuids);
    echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . html_writer::tag('th', get_string('email'))
        . html_writer::tag('th', get_string('cert_batch_col_level', 'local_tm_certification'))
        . html_writer::tag('th', '')
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($cart as $item) {
        $uid = (int)$item['userid'];
        $lv = (string)$item['level'];
        if (!isset($cartusers[$uid])) {
            continue;
        }
        $u = $cartusers[$uid];
        $rm = new moodle_url('/local/tm_certification/admin/cert_batch_site.php', [
            'action' => 'remove',
            'userid' => $uid,
            'level' => $lv,
            'q' => $q,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', fullname($u));
        echo html_writer::tag('td', s((string)($u->candidateid ?? '')), ['class' => 'tm-cert-id']);
        echo html_writer::tag('td', s($u->email), ['class' => 'tm-cert-muted']);
        echo html_writer::tag('td', s($levels[$lv] ?? $lv));
        echo html_writer::tag('td', html_writer::link($rm,
            get_string('cert_batch_remove', 'local_tm_certification'),
            ['class' => 'btn btn-sm tm-cert-btn-danger']));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('tm-cert-ph tm-cert-ph-foot');
    echo html_writer::link(
        new moodle_url('/local/tm_certification/admin/cert_batch_site.php', [
            'summary' => 1,
            'q' => $q,
        ]),
        get_string('cert_batch_make_summary', 'local_tm_certification'),
        ['class' => 'btn btn-primary btn-sm']
    );
    echo html_writer::end_div();
}
echo html_writer::end_div();

if ($summary !== null) {
    echo html_writer::start_div('tm-cert-panel');
    echo html_writer::start_div('tm-cert-ph');
    echo html_writer::tag('h3', get_string('cert_batch_summary_heading', 'local_tm_certification'));
    echo html_writer::start_div('tm-cert-ph-ops');
    echo dashboard_helper::render_pill([
        'label' => get_string('cert_batch_summary_pdfs', 'local_tm_certification', $summary['pdfcount']),
        'mod' => 'ok',
    ]);
    echo dashboard_helper::render_pill([
        'label' => get_string('cert_batch_summary_ok', 'local_tm_certification', $summary['ok_n']),
        'mod' => 'ok',
    ]);
    echo dashboard_helper::render_pill([
        'label' => get_string('cert_batch_summary_miss', 'local_tm_certification', $summary['miss_n']),
        'mod' => 'bad',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::div(
        get_string('cert_batch_summary_hint', 'local_tm_certification'),
        'tm-cert-alert-slim'
    );

    echo html_writer::start_tag('table', ['class' => 'tm-cert-data']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('fullname'))
        . html_writer::tag('th', get_string('candidate_id', 'local_tm_certification'))
        . html_writer::tag('th', get_string('cert_batch_col_level', 'local_tm_certification'))
        . html_writer::tag('th', get_string('cert_batch_col_moodle_short', 'local_tm_certification'))
        . html_writer::tag('th', get_string('cert_batch_col_result', 'local_tm_certification'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($summary['rows'] as $srow) {
        $u = $srow->user;
        $trclass = $srow->available ? 'tm-cert-row-ok' : 'tm-cert-row-miss';
        echo html_writer::start_tag('tr', ['class' => $trclass]);
        echo html_writer::tag('td', fullname($u));
        echo html_writer::tag('td', s((string)($u->candidateid ?? '')), ['class' => 'tm-cert-id']);
        echo html_writer::tag('td', s($srow->levellabel));
        echo html_writer::tag('td', dashboard_helper::render_pill([
            'label' => $srow->statuslabel,
            'mod' => $srow->mod,
        ]));
        echo html_writer::tag('td', s($srow->result));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('tm-cert-ph tm-cert-ph-foot');
    if ($summary['pdfcount'] > 0) {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $baseurl->out(false),
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'download']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'q', 'value' => $q]);
        foreach ($cart as $item) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'sel[]',
                'value' => $cart_key((int)$item['userid'], (string)$item['level']),
            ]);
        }
        echo html_writer::tag('button',
            get_string('cert_batch_confirm_zip', 'local_tm_certification', $summary['pdfcount']),
            ['type' => 'submit', 'class' => 'btn btn-success']
        );
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::span(get_string('cert_batch_zip_empty', 'local_tm_certification'), 'tm-cert-muted');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo $OUTPUT->footer();
