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
     * Product title for dashboard / home-card (same for all roles).
     */
    public static function title_for_view(string $view): string {
        return get_string('dash_product_title', 'local_tm_certification');
    }

    /**
     * Cover summary for manage / instruct / examinee views.
     *
     * @return array{
     *   title:string,
     *   subtitle:string,
     *   badges:array<int,array{label:string,mod:string}>,
     *   meta:string,
     *   meta_urgent:bool
     * }
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
                'title' => self::title_for_view('instruct'),
                'subtitle' => get_string('dash_hello', 'local_tm_certification', $fullname),
                'badges' => $badges,
                'meta' => $meta,
                'meta_urgent' => $urgent,
            ];
        }

        if ($view === 'examinee') {
            $levelopts = session_manager::level_options();
            // Same pattern as instructor: one pill/tab per held exam level.
            $examlevels = certificate_helper::get_user_exam_levels((int)$user->id);
            $badges = [];
            foreach ($examlevels as $lv) {
                $badges[] = [
                    'label' => $levelopts[$lv] ?? $lv,
                    'mod' => '',
                ];
            }
            if (empty($badges)) {
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
                'title' => self::title_for_view('examinee'),
                'subtitle' => get_string('dash_hello', 'local_tm_certification', $fullname),
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
            'title' => self::title_for_view('manage'),
            'subtitle' => '',
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
        global $USER;

        require_once(__DIR__ . '/session_manager.php');
        require_once(__DIR__ . '/application_manager.php');
        require_once(__DIR__ . '/enrolment_manager.php');
        require_once(__DIR__ . '/assessment_manager.php');

        $cover = self::cover_for_view($view);
        $fullurl = (new \moodle_url('/local/tm_certification/index.php'))->out(false);

        $out = \html_writer::start_div('tm-cert-home-dashboard-wrapper');
        $out .= \html_writer::start_div('tm-cert-home-card');

        $out .= \html_writer::start_div('tm-cert-home-card-top');
        $out .= \html_writer::start_div();
        $out .= \html_writer::tag('h3', self::title_for_view($view), ['class' => 'tm-cert-home-title']);
        if ($view !== 'manage' && !empty($cover['subtitle'])) {
            $out .= \html_writer::tag('p', s($cover['subtitle']), ['class' => 'tm-cert-home-subtitle']);
        }
        $out .= \html_writer::end_div();
        $out .= \html_writer::link($fullurl, get_string('dashboard_open_full_link', 'local_tm_certification'),
            ['class' => 'tm-cert-home-full-link']);
        $out .= \html_writer::end_div();

        if ($view === 'manage') {
            $out .= self::render_home_manage_body();
        } else if ($view === 'instruct') {
            $out .= self::render_home_instruct_body($cover, (int)$USER->id);
        } else {
            $out .= self::render_home_examinee_body($cover, (int)$USER->id);
        }

        $out .= \html_writer::end_div();
        $out .= \html_writer::end_div();

        return $out;
    }

    /**
     * Manage home: KPIs + pending slice + ops (no certificates).
     */
    private static function render_home_manage_body(): string {
        $pending = application_manager::get_applications(['status' => application_manager::STATUS_PENDING]);
        $openonly = session_manager::get_sessions(['status' => session_manager::STATUS_OPEN]);
        $weekcount = self::count_enrols_since(time() - (7 * DAYSECS));
        $levels = session_manager::level_options();

        $out = \html_writer::start_div('tm-cert-home-kpis');
        $out .= self::home_kpi((string)count($pending), get_string('dash_kpi_pending', 'local_tm_certification'),
            count($pending) > 0);
        $out .= self::home_kpi((string)count($openonly), get_string('dash_kpi_open', 'local_tm_certification'));
        $out .= self::home_kpi((string)$weekcount, get_string('dash_kpi_week', 'local_tm_certification'));
        $out .= \html_writer::end_div();

        $out .= \html_writer::div(get_string('dash_home_section_pending', 'local_tm_certification'),
            'tm-cert-home-section-label');
        if (empty($pending)) {
            $out .= \html_writer::div(get_string('app_empty_list', 'local_tm_certification'), 'tm-cert-home-empty');
        } else {
            $out .= \html_writer::start_div('tm-cert-home-list');
            foreach (array_slice($pending, 0, 2) as $app) {
                $level = $levels[$app->certlevel] ?? $app->certlevel;
                $name = fullname($app->applicant) . ' · [' . $level . ']';
                $when = userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig'));
                $venue = self::format_venue($app);
                $sub = $when . ($venue !== '' ? ' · ' . $venue : '');
                $ops = \html_writer::link(
                    new \moodle_url('/local/tm_certification/admin/review.php', [
                        'action' => 'approve',
                        'id' => $app->id,
                    ]),
                    get_string('app_action_approve', 'local_tm_certification'),
                    ['class' => 'btn btn-sm tm-cert-btn-accent']
                );
                $out .= self::home_list_item(s($name), s($sub), $ops);
            }
            $out .= \html_writer::end_div();
        }

        $out .= \html_writer::start_div('tm-cert-home-actions');
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/admin/review.php'),
            get_string('dash_action_review_apps', 'local_tm_certification'),
            ['class' => 'btn btn-primary btn-sm']
        );
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/admin/edit_session.php'),
            get_string('dash_action_create_session', 'local_tm_certification'),
            ['class' => 'btn btn-secondary btn-sm']
        );
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/admin/sessions.php'),
            get_string('dash_action_manage_sessions', 'local_tm_certification'),
            ['class' => 'btn btn-secondary btn-sm']
        );
        $out .= \html_writer::end_div();

        return $out;
    }

    /**
     * Instructor home: levels, next session, latest application.
     *
     * @param array $cover
     */
    private static function render_home_instruct_body(array $cover, int $userid): string {
        require_once(__DIR__ . '/course_access_helper.php');
        // Ensure instructor can open mapped level courses + instructor zone.
        course_access_helper::sync_instructor_access($userid);

        $levels = session_manager::level_options();
        $statusopts = application_manager::status_options();

        $out = '';
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

        $mysessions = session_manager::get_sessions(['instructorid' => $userid]);
        $next = self::pick_primary_session($mysessions);

        $out .= \html_writer::div(get_string('dash_home_section_next_teach', 'local_tm_certification'),
            'tm-cert-home-section-label');
        if (!$next) {
            $out .= \html_writer::div(get_string('dash_empty_sessions', 'local_tm_certification'), 'tm-cert-home-empty');
        } else {
            $ended = session_manager::is_ended($next);
            $when = userdate((int)$next->starttime, get_string('strftimedatetimeshort', 'langconfig'));
            $venue = self::format_venue($next);
            $people = get_string('dash_session_people', 'local_tm_certification',
                session_manager::count_enrols((int)$next->id));
            $subparts = array_filter([$when, $venue !== '' ? s($venue) : null, $people]);
            $sub = implode(' · ', $subparts) . ' · ' . self::session_status_pill($next);

            $ops = '';
            if (!$ended) {
                $ops .= \html_writer::link(
                    new \moodle_url('/local/tm_certification/admin/practical_scores.php', ['sessionid' => $next->id]),
                    get_string('dash_action_practical', 'local_tm_certification'),
                    ['class' => 'btn btn-sm btn-primary']
                );
            }
            $ops .= \html_writer::link(
                new \moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $next->id]),
                get_string('dash_action_roster', 'local_tm_certification'),
                ['class' => 'btn btn-sm btn-secondary']
            );

            $out .= \html_writer::start_div('tm-cert-home-list');
            $out .= self::home_list_item(format_string($next->name), $sub, $ops);
            $out .= \html_writer::end_div();
        }

        $myapps = application_manager::get_applications(['applicantid' => $userid]);
        $out .= \html_writer::div(get_string('dash_home_section_my_apps', 'local_tm_certification'),
            'tm-cert-home-section-label');
        if (empty($myapps)) {
            $out .= \html_writer::div(get_string('dash_empty_applications', 'local_tm_certification'),
                'tm-cert-home-empty');
        } else {
            $app = reset($myapps);
            // Prefer a pending application if any.
            foreach ($myapps as $candidate) {
                if ((int)$candidate->status === application_manager::STATUS_PENDING) {
                    $app = $candidate;
                    break;
                }
            }
            $level = $levels[$app->certlevel] ?? $app->certlevel;
            $name = '[' . $level . ']' . get_string('session_name_suffix', 'local_tm_certification');
            $when = userdate((int)$app->starttime, get_string('strftimedatetimeshort', 'langconfig'));
            $venue = self::format_venue($app);
            $statuslabel = $statusopts[(int)$app->status] ?? '';
            $statuspill = self::render_pill([
                'label' => $statuslabel,
                'mod' => ((int)$app->status === application_manager::STATUS_PENDING) ? '' : 'dim',
            ]);
            $subparts = array_filter([$when, $venue !== '' ? s($venue) : null]);
            $sub = implode(' · ', $subparts) . ' · ' . $statuspill;

            $out .= \html_writer::start_div('tm-cert-home-list');
            $out .= self::home_list_item(s($name), $sub, '');
            $out .= \html_writer::end_div();
        }

        $out .= \html_writer::start_div('tm-cert-home-actions');
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/apply.php'),
            get_string('dash_action_apply', 'local_tm_certification'),
            ['class' => 'btn btn-primary btn-sm']
        );
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/my_certificates.php'),
            get_string('nav_my_certificates', 'local_tm_certification'),
            ['class' => 'btn btn-secondary btn-sm']
        );
        $out .= \html_writer::end_div();

        return $out;
    }

    /**
     * Examinee home: level/id, next exam + written button.
     *
     * @param array $cover
     */
    private static function render_home_examinee_body(array $cover, int $userid): string {
        $out = '';
        if (!empty($cover['badges'])) {
            $out .= \html_writer::start_div('tm-cert-facts');
            foreach ($cover['badges'] as $badge) {
                $out .= self::render_pill($badge);
            }
            $out .= \html_writer::end_div();
        }

        $mysessions = enrolment_manager::get_examinee_sessions($userid);
        $next = self::pick_primary_session($mysessions);

        $out .= \html_writer::div(get_string('dash_home_section_next_exam', 'local_tm_certification'),
            'tm-cert-home-section-label');
        if (!$next) {
            $out .= \html_writer::div(get_string('dash_empty_exams', 'local_tm_certification'), 'tm-cert-home-empty');
        } else {
            $when = userdate((int)$next->starttime, get_string('strftimedatetimeshort', 'langconfig'));
            $venue = self::format_venue($next);
            $instructorlabel = '';
            if (!empty($next->instructor)) {
                $instructorlabel = get_string('examinee_session_instructor', 'local_tm_certification',
                    fullname($next->instructor));
            }
            $subparts = array_filter([
                $when,
                $venue !== '' ? s($venue) : null,
                $instructorlabel !== '' ? s($instructorlabel) : null,
            ]);
            $sub = implode(' · ', $subparts) . ' · ' . self::session_status_pill($next);

            $btn = assessment_manager::examinee_written_button($next);
            $ops = '';
            $hint = '';
            if (!empty($btn['show'])) {
                if (!empty($btn['enabled']) && !empty($btn['url'])) {
                    $ops .= \html_writer::link(
                        $btn['url'],
                        $btn['label'],
                        ['class' => 'btn btn-sm btn-primary']
                    );
                } else {
                    $ops .= \html_writer::span(
                        $btn['label'],
                        'btn btn-sm btn-secondary disabled',
                        [
                            'aria-disabled' => 'true',
                            'title' => (string)($btn['hint'] ?? ''),
                        ]
                    );
                    $hint = (string)($btn['hint'] ?? '');
                }
            }

            $out .= \html_writer::start_div('tm-cert-home-list');
            $out .= self::home_list_item(format_string($next->name), $sub, $ops, $hint);
            $out .= \html_writer::end_div();
        }

        $out .= \html_writer::start_div('tm-cert-home-actions');
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/my_certificates.php'),
            get_string('nav_my_certificates', 'local_tm_certification'),
            ['class' => 'btn btn-secondary btn-sm']
        );
        $out .= \html_writer::link(
            new \moodle_url('/local/tm_certification/index.php'),
            get_string('dash_action_all_exams', 'local_tm_certification'),
            ['class' => 'btn btn-secondary btn-sm']
        );
        $out .= \html_writer::end_div();

        return $out;
    }

    /**
     * Prefer soonest non-ended session; else most recent ended.
     *
     * @param \stdClass[] $sessions
     */
    private static function pick_primary_session(array $sessions): ?\stdClass {
        if (empty($sessions)) {
            return null;
        }
        require_once(__DIR__ . '/session_manager.php');
        $open = [];
        foreach ($sessions as $s) {
            if (!session_manager::is_ended($s)) {
                $open[] = $s;
            }
        }
        if (!empty($open)) {
            usort($open, static function ($a, $b): int {
                $cmp = ((int)$a->starttime <=> (int)$b->starttime);
                return $cmp !== 0 ? $cmp : ((int)$a->id <=> (int)$b->id);
            });
            return $open[0];
        }
        usort($sessions, static function ($a, $b): int {
            $cmp = ((int)$b->starttime <=> (int)$a->starttime);
            return $cmp !== 0 ? $cmp : ((int)$b->id <=> (int)$a->id);
        });
        return $sessions[0];
    }

    /**
     * @param \stdClass $row session or application with venue fields
     */
    private static function format_venue(\stdClass $row): string {
        if (!empty($row->venuename) || !empty($row->venuecode)) {
            return trim((string)($row->venuecode ?? '') . ' ' . (string)($row->venuename ?? ''));
        }
        if (!empty($row->venue)) {
            return (string)$row->venue;
        }
        return '';
    }

    private static function home_kpi(string $n, string $label, bool $warn = false): string {
        $nclass = 'n' . ($warn ? ' warn' : '');
        return \html_writer::div(
            \html_writer::div($n, $nclass) . \html_writer::div($label, 'l'),
            'tm-cert-home-kpi'
        );
    }

    /**
     * @param string $namehtml Already escaped / format_string()'d name
     * @param string $subhtml May contain safe HTML (pills)
     */
    private static function home_list_item(string $namehtml, string $subhtml, string $opshtml, string $hint = ''): string {
        $html = \html_writer::start_div('tm-cert-home-item');
        $html .= \html_writer::start_div();
        $html .= \html_writer::div($namehtml, 'tm-cert-home-item-name');
        $html .= \html_writer::div($subhtml, 'tm-cert-home-item-sub');
        if ($hint !== '') {
            $html .= \html_writer::div(s($hint), 'tm-cert-home-item-hint');
        }
        $html .= \html_writer::end_div();
        if ($opshtml !== '') {
            $html .= \html_writer::div($opshtml, 'tm-cert-home-item-ops');
        }
        $html .= \html_writer::end_div();
        return $html;
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
