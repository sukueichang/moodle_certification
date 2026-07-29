<?php
/**
 * Dashboard view helpers (P1 shell).
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class dashboard_helper {

    /**
     * Placeholder cover data until certificate tables exist (P6).
     *
     * @return array{title:string,badges:string[],meta:string}
     */
    public static function cover_for_view(string $view, ?\stdClass $user = null): array {
        global $USER;
        $user = $user ?? $USER;
        $fullname = fullname($user);

        if ($view === 'instruct') {
            return [
                'title' => get_string('dash_hello', 'local_tm_certification', $fullname),
                'badges' => [
                    get_string('dash_badge_instructor_level_placeholder', 'local_tm_certification'),
                    get_string('dash_badge_valid_placeholder', 'local_tm_certification'),
                ],
                'meta' => get_string('dash_instructor_cover_hint', 'local_tm_certification'),
            ];
        }
        if ($view === 'examinee') {
            return [
                'title' => get_string('dash_hello', 'local_tm_certification', $fullname),
                'badges' => [
                    get_string('dash_badge_exam_level_placeholder', 'local_tm_certification'),
                ],
                'meta' => get_string('dash_examinee_cover_hint', 'local_tm_certification'),
            ];
        }
        return [
            'title' => get_string('dash_manage_title', 'local_tm_certification'),
            'badges' => [],
            'meta' => get_string('dash_manage_cover_hint', 'local_tm_certification'),
        ];
    }

    /**
     * Compact panel for Moodle site home / My home injection.
     */
    public static function render_home_panel(string $view): string {
        $cover = self::cover_for_view($view);
        $fullurl = (new \moodle_url('/local/tm_certification/index.php'))->out(false);
        $certurl = (new \moodle_url('/local/tm_certification/my_certificates.php'))->out(false);

        $viewlabel = get_string('dash_view_' . $view, 'local_tm_certification');

        $out = \html_writer::start_div('tm-cert-home-dashboard-wrapper');
        $out .= \html_writer::start_div('tm-cert-home-card');
        $out .= \html_writer::tag('h3', get_string('dashboard_home_title', 'local_tm_certification'),
            ['class' => 'tm-cert-home-title']);
        $out .= \html_writer::span($viewlabel, 'tm-cert-badge tm-cert-view-label');
        $out .= \html_writer::tag('h4', s($cover['title']), ['class' => 'tm-cert-home-subtitle']);

        if (!empty($cover['badges'])) {
            $out .= \html_writer::start_div('tm-cert-badges');
            foreach ($cover['badges'] as $badge) {
                $out .= \html_writer::span(s($badge), 'tm-cert-badge');
            }
            $out .= \html_writer::end_div();
        }

        $out .= \html_writer::tag('p', s($cover['meta']), ['class' => 'tm-cert-meta']);
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
}
