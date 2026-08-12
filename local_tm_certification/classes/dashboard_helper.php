<?php
/**
 * Dashboard view helpers.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class dashboard_helper {

    /**
     * Cover summary for manage / instruct / examinee views.
     *
     * @return array{title:string,badges:array<int,array{label:string,mod:string}>,meta:string,meta_urgent:bool}
     */
    public static function cover_for_view(string $view, ?\stdClass $user = null): array {
        global $USER;
        require_once(__DIR__ . '/catalog_manager.php');
        require_once(__DIR__ . '/session_manager.php');
        require_once(__DIR__ . '/certificate_helper.php');
        require_once(__DIR__ . '/candidate_manager.php');
        require_once(__DIR__ . '/application_manager.php');

        $user = $user ?? $USER;
        $fullname = fullname($user);

        if ($view === 'instruct') {
            $levelopts = session_manager::level_options();
            $active = catalog_manager::get_user_instructor_levels((int)$user->id);
            $badges = [];
            $levelstatuses = catalog_manager::get_instructor_levels_status((int)$user->id);
            foreach ($active as $lv) {
                $badges[] = [
                    'label' => $levelopts[$lv] ?? $lv,
                    'mod' => '',
                ];
            }
            if (empty($badges)) {
                $badges[] = [
                    'label' => get_string('dash_badge_instructor_level_none', 'local_tm_certification'),
                    'mod' => 'dim',
                ];
            }

            $anyexpired = false;
            $anyvalid = false;
            $earliest = null;
            foreach ($levelstatuses as $st) {
                $status = $st['status'] ?? '';
                if ($status === catalog_manager::INSTR_STATUS_EXPIRED) {
                    $anyexpired = true;
                }
                if ($status === catalog_manager::INSTR_STATUS_VALID) {
                    $anyvalid = true;
                    if (!empty($st['expiresat'])) {
                        if ($earliest === null || (int)$st['expiresat'] < $earliest) {
                            $earliest = (int)$st['expiresat'];
                        }
                    }
                }
            }
            if ($anyexpired) {
                $badges[] = [
                    'label' => get_string('dash_badge_status_expired', 'local_tm_certification'),
                    'mod' => 'bad',
                ];
            } else if ($anyvalid) {
                $badges[] = [
                    'label' => get_string('dash_badge_status_valid', 'local_tm_certification'),
                    'mod' => 'ok',
                ];
            } else if (!empty($active)) {
                $badges[] = [
                    'label' => get_string('dash_badge_status_pending', 'local_tm_certification'),
                    'mod' => 'dim',
                ];
            }

            $meta = '';
            $urgent = false;
            if ($anyexpired) {
                $meta = get_string('cert_renewal_deadline_label', 'local_tm_certification')
                    . get_string('cert_renewal_deadline_urgent', 'local_tm_certification');
                $urgent = true;
            } else if ($earliest) {
                $meta = get_string('cert_renewal_deadline_label', 'local_tm_certification')
                    . get_string('cert_renewal_deadline_until', 'local_tm_certification',
                        userdate($earliest, get_string('strftimedate', 'langconfig')));
            }

            return [
                'title' => get_string('dash_hello', 'local_tm_certification', $fullname),
                'badges' => $badges,
                'meta' => $meta,
                'meta_urgent' => $urgent,
            ];
        }

        if ($view === 'examinee') {
            $levelopts = session_manager::level_options();
            $examlevel = certificate_helper::get_user_highest_exam_level((int)$user->id);
            $badges = [];
            if ($examlevel) {
                $badges[] = [
                    'label' => $levelopts[$examlevel] ?? $examlevel,
                    'mod' => '',
                ];
            } else {
                $badges[] = [
                    'label' => get_string('dash_badge_exam_level_none', 'local_tm_certification'),
                    'mod' => 'dim',
                ];
            }
            $candidateid = candidate_manager::get_candidate_id((int)$user->id);
            if ($candidateid) {
                $badges[] = [
                    'label' => get_string('dash_badge_candidate_id', 'local_tm_certification', $candidateid),
                    'mod' => '',
                ];
            }
            return [
                'title' => get_string('dash_hello', 'local_tm_certification', $fullname),
                'badges' => $badges,
                'meta' => '',
                'meta_urgent' => false,
            ];
        }

        $pending = application_manager::get_applications(['status' => application_manager::STATUS_PENDING]);
        $opensessions = session_manager::get_sessions(['status' => session_manager::STATUS_OPEN]);
        $pendingn = count($pending);
        $openn = count($opensessions);

        return [
            'title' => get_string('dash_manage_title', 'local_tm_certification'),
            'badges' => [
                [
                    'label' => get_string('dash_badge_kpi_pending', 'local_tm_certification', $pendingn),
                    'mod' => '',
                ],
                [
                    'label' => get_string('dash_badge_kpi_open', 'local_tm_certification', $openn),
                    'mod' => '',
                ],
            ],
            'meta' => '',
            'meta_urgent' => false,
        ];
    }

    /**
     * Compact panel for Moodle site home / My home injection.
     */
    public static function render_home_panel(string $view): string {
        $cover = self::cover_for_view($view);
        $fullurl = (new \moodle_url('/local/tm_certification/index.php'))->out(false);
        $certurl = (new \moodle_url('/local/tm_certification/my_certificates.php'))->out(false);

        $out = \html_writer::start_div('tm-cert-home-dashboard-wrapper');
        $out .= \html_writer::start_div('tm-cert-home-card');
        $out .= \html_writer::tag('h3', get_string('dashboard_home_title', 'local_tm_certification'),
            ['class' => 'tm-cert-home-title']);

        if ($view !== 'manage') {
            $out .= \html_writer::tag('h4', s($cover['title']), ['class' => 'tm-cert-home-subtitle']);
        }

        if (!empty($cover['badges'])) {
            $out .= \html_writer::start_div('tm-cert-facts');
            foreach ($cover['badges'] as $badge) {
                $out .= self::render_pill($badge);
            }
            $out .= \html_writer::end_div();
        }

        if (!empty($cover['meta'])) {
            $metacls = 'tm-cert-deadline';
            if (!empty($cover['meta_urgent'])) {
                $metacls .= ' urgent';
            }
            $out .= \html_writer::tag('p', s($cover['meta']), ['class' => $metacls]);
        }

        $out .= \html_writer::start_div('tm-cert-home-actions');
        $out .= \html_writer::link($fullurl, get_string('dashboard_open_full', 'local_tm_certification'),
            ['class' => 'btn btn-primary btn-sm']);
        $out .= ' ';
        $out .= \html_writer::link($certurl, get_string('nav_my_certificates', 'local_tm_certification'),
            ['class' => 'btn btn-secondary btn-sm']);
        $out .= \html_writer::end_div();
        $out .= \html_writer::end_div();
        $out .= \html_writer::end_div();

        return $out;
    }

    /**
     * @param array{label:string,mod?:string}|string $badge
     */
    public static function render_pill($badge): string {
        if (is_string($badge)) {
            $label = $badge;
            $mod = '';
        } else {
            $label = (string)($badge['label'] ?? '');
            $mod = (string)($badge['mod'] ?? '');
        }
        $class = 'tm-cert-pill';
        if ($mod !== '') {
            $class .= ' ' . $mod;
        }
        return \html_writer::span(s($label), $class);
    }

    /**
     * Status pill for a session row.
     */
    public static function session_status_pill(\stdClass $session): string {
        require_once(__DIR__ . '/session_manager.php');
        $ended = session_manager::is_ended($session);
        return self::render_pill([
            'label' => session_manager::status_label($session),
            'mod' => $ended ? 'dim' : 'ok',
        ]);
    }

    /**
     * Active enrolments created since $since (unix time).
     * Cancelled / removed roster rows are excluded.
     */
    public static function count_enrols_since(int $since): int {
        global $DB;
        require_once(__DIR__ . '/enrolment_manager.php');
        return (int)$DB->count_records_select(
            'local_tm_cert_enrols',
            'timecreated >= :since AND status <> :cancelled',
            [
                'since' => $since,
                'cancelled' => enrolment_manager::ENROL_CANCELLED,
            ]
        );
    }
}
