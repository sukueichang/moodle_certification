<?php
/**
 * Library callbacks for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @return array{release:string,version:int,label:string}
 */
function local_tm_certification_plugin_version_info(): array {
    $plugin = new stdClass();
    include(__DIR__ . '/version.php');
    $release = isset($plugin->release) ? (string)$plugin->release : '';
    $version = isset($plugin->version) ? (int)$plugin->version : 0;
    $label = $release !== '' ? $release : (string)$version;
    if ($release !== '' && $version > 0) {
        $label = $release . ' (' . $version . ')';
    }
    return [
        'release' => $release,
        'version' => $version,
        'label' => $label,
    ];
}

/**
 * Inject stylesheet when user may view the plugin.
 *
 * @return string
 */
function local_tm_certification_before_standard_html_head(): string {
    if (!isloggedin() || isguestuser()) {
        return '';
    }
    require_once(__DIR__ . '/classes/permissions_manager.php');
    if (!\local_tm_certification\permissions_manager::user_can_view_plugin()) {
        return '';
    }
    $href = (new moodle_url('/local/tm_certification/styles.css'))->out(false);
    return html_writer::empty_tag('link', [
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => $href,
    ]);
}

/**
 * Inject certification dashboard onto site home (and My home) — same approach as local_tm_course.
 *
 * @return string
 */
function local_tm_certification_before_standard_top_of_body_html(): string {
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return '';
    }
    if (empty($PAGE)) {
        return '';
    }

    $layout = (string)($PAGE->pagelayout ?? '');
    $pagetype = (string)($PAGE->pagetype ?? '');
    $onfrontpage = ($layout === 'frontpage');
    $onmyhome = ($layout === 'mydashboard' || $pagetype === 'my-index');
    if (!$onfrontpage && !$onmyhome) {
        return '';
    }

    require_once(__DIR__ . '/classes/permissions_manager.php');
    require_once(__DIR__ . '/classes/dashboard_helper.php');

    if (!\local_tm_certification\permissions_manager::user_can_view_plugin()) {
        return '';
    }
    if (!\local_tm_certification\permissions_manager::is_dashboard_globally_enabled()) {
        if (!\local_tm_certification\permissions_manager::user_can_manage()) {
            return '';
        }
        // Managers still see a hint when globally disabled.
        $settingsurl = (new moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_dashboard']))->out(false);
        return html_writer::div(
            get_string('dashboard_hidden_admin_hint', 'local_tm_certification')
            . ' '
            . html_writer::link($settingsurl, get_string('setting_dashboard_heading', 'local_tm_certification')),
            'tm-cert-home-dashboard-wrapper alert alert-warning'
        );
    }

    $view = \local_tm_certification\permissions_manager::resolve_dashboard_view();
    if ($view === 'none') {
        if (is_siteadmin() || \local_tm_certification\permissions_manager::user_can_manage()) {
            $rolesurl = (new moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_roles']))->out(false);
            return html_writer::div(
                get_string('error_dashboard_view_none', 'local_tm_certification')
                . ' '
                . html_writer::link($rolesurl, get_string('setting_roles_heading', 'local_tm_certification')),
                'tm-cert-home-dashboard-wrapper alert alert-info'
            );
        }
        return '';
    }

    $out = \local_tm_certification\dashboard_helper::render_home_panel($view);

    // Inline safety styles + move into region-main (Plan A, no core patch).
    $out .= html_writer::tag('style', '
        .tm-cert-home-dashboard-wrapper { margin: 1rem 0; }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-card {
            background: #fff;
            border: 1px solid #d5e0e5;
            border-radius: 12px;
            padding: 16px 16px 14px;
            box-shadow: 0 1px 0 rgba(21,37,44,.04);
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-card-top {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 10px; margin-bottom: 10px;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-title {
            color: #005f7e; margin: 0; font-size: 1.05rem; font-weight: 700;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-subtitle {
            margin: 4px 0 0; font-size: .88rem; font-weight: 500; color: #15252c;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-full-link {
            font-size: .78rem; font-weight: 600; color: #5c717a; text-decoration: none; white-space: nowrap;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-full-link:hover { color: #005f7e; }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-kpis {
            display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin: 4px 0 12px;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-kpi {
            background: #f4f8fa; border: 1px solid #e2ebef; border-radius: 8px; padding: 10px;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-kpi .n {
            font-size: 1.35rem; font-weight: 700; color: #005f7e; line-height: 1.1;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-kpi .n.warn { color: #c45c00; }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-kpi .l {
            margin-top: 3px; font-size: .72rem; font-weight: 600; color: #5c717a;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-section-label {
            margin: 10px 0 6px; font-size: .72rem; font-weight: 700;
            letter-spacing: .04em; text-transform: uppercase; color: #5c717a;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-list {
            border: 1px solid #d7e1e6; border-radius: 8px; overflow: hidden; margin-bottom: 4px;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-item {
            display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center;
            padding: 10px 12px; border-bottom: 1px solid #d7e1e6; background: #fff;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-item:last-child { border-bottom: 0; }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-item-name {
            font-size: .88rem; font-weight: 600; color: #15252c;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-item-sub {
            margin-top: 2px; font-size: .78rem; color: #5c717a;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-item-hint {
            margin-top: 4px; font-size: .75rem; color: #5c717a;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-item-ops {
            display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-end;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-empty {
            padding: 14px 12px; text-align: center; font-size: .85rem; color: #5c717a;
            border: 1px dashed #c5d4db; border-radius: 8px; background: #fafcfd;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-home-actions {
            margin-top: 12px; padding-top: 12px; border-top: 1px solid #d7e1e6;
            display: flex; flex-wrap: wrap; gap: 6px;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-facts {
            display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-pill {
            display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px;
            font-size: .8rem; font-weight: 600; background: #e4eef2; color: #005f7e;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-pill.ok { background: #e6f5ea; color: #1b7a3d; }
        .tm-cert-home-dashboard-wrapper .tm-cert-pill.bad { background: #fdecea; color: #b42318; }
        .tm-cert-home-dashboard-wrapper .tm-cert-pill.dim { background: #eef1f3; color: #667985; }
        .tm-cert-home-dashboard-wrapper .tm-cert-deadline {
            margin: 6px 0 0; font-size: .82rem; color: #5c717a;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-deadline.urgent {
            color: #b42318; font-weight: 600;
        }
        .tm-cert-home-dashboard-wrapper .tm-cert-btn-accent {
            background: #74b42a !important; color: #fff !important; border-color: #74b42a !important;
        }
        @media (max-width: 480px) {
            .tm-cert-home-dashboard-wrapper .tm-cert-home-kpis { grid-template-columns: 1fr; }
            .tm-cert-home-dashboard-wrapper .tm-cert-home-item { grid-template-columns: 1fr; }
            .tm-cert-home-dashboard-wrapper .tm-cert-home-item-ops { justify-content: flex-start; }
        }
    ');

    $out .= html_writer::script("
        (function() {
            function placeCertDashboard() {
                var block = document.querySelector('.tm-cert-home-dashboard-wrapper');
                if (!block) { return false; }
                var main = document.getElementById('region-main')
                    || document.querySelector('main #region-main')
                    || document.querySelector('#region-main-box');
                if (!main) { return false; }
                if (block.parentNode === main || main.contains(block) && block.previousElementSibling) {
                    if (block.parentNode !== main) {
                        main.insertBefore(block, main.firstChild);
                    }
                    return true;
                }
                main.insertBefore(block, main.firstChild);
                return true;
            }
            var retries = 0;
            function run() {
                if (placeCertDashboard()) { return; }
                retries += 1;
                if (retries < 20) { setTimeout(run, 120); }
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
 * Shared child links for flat nav / settings nav (ops + admin setting shortcuts).
 *
 * @param navigation_node $node Parent node
 * @param string $keyprefix Unique key prefix (avoid collisions when both trees render)
 * @param int $nodetype navigation_node::TYPE_CUSTOM or TYPE_SETTING
 */
function local_tm_certification_add_nav_children(navigation_node $node, string $keyprefix, int $nodetype): void {
    $node->add(
        get_string('nav_dashboard', 'local_tm_certification'),
        new moodle_url('/local/tm_certification/index.php'),
        $nodetype,
        null,
        $keyprefix . '_dash'
    );
    $node->add(
        get_string('nav_my_certificates', 'local_tm_certification'),
        new moodle_url('/local/tm_certification/my_certificates.php'),
        $nodetype,
        null,
        $keyprefix . '_certs'
    );

    $canmanage = \local_tm_certification\permissions_manager::user_can_manage();
    $canapprove = \local_tm_certification\permissions_manager::user_can_approve();

    if ($canmanage || $canapprove) {
        $node->add(
            get_string('nav_sessions', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/sessions.php'),
            $nodetype,
            null,
            $keyprefix . '_sessions'
        );
        $node->add(
            get_string('nav_applications', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/review.php'),
            $nodetype,
            null,
            $keyprefix . '_apps'
        );
    }

    if ($canmanage) {
        $node->add(
            get_string('nav_venues', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/venues.php'),
            $nodetype,
            null,
            $keyprefix . '_venues'
        );
        $node->add(
            get_string('nav_instructor_levels', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/instructors.php'),
            $nodetype,
            null,
            $keyprefix . '_instr'
        );
        $node->add(
            get_string('nav_legacy_migrate', 'local_tm_certification'),
            new moodle_url('/local/tm_certification/admin/legacy_name_migrate.php'),
            $nodetype,
            null,
            $keyprefix . '_legacy_mig'
        );
    }

    // Admin settingpages require site config — only show shortcuts to those who can open them.
    $sysctx = context_system::instance();
    if (!has_capability('moodle/site:config', $sysctx)) {
        return;
    }

    $settingslinks = [
        'dashboard' => ['local_tm_certification_dashboard', 'setting_dashboard_heading'],
        'coursemap' => ['local_tm_certification_coursemap', 'setting_coursemap_heading'],
        'attmap' => ['local_tm_certification_attmap', 'setting_attendance_map_heading'],
        'assess' => ['local_tm_certification_assess', 'setting_assess_heading'],
        'instrval' => ['local_tm_certification_instr_validity', 'setting_instr_validity_heading'],
        'roles' => ['local_tm_certification_roles', 'setting_roles_heading'],
        'notify' => ['local_tm_certification_notify', 'setting_notify_heading'],
        'about' => ['local_tm_certification_about', 'nav_plugin_about'],
    ];
    foreach ($settingslinks as $suffix => $meta) {
        [$section, $strkey] = $meta;
        $node->add(
            get_string($strkey, 'local_tm_certification'),
            new moodle_url('/admin/settings.php', ['section' => $section]),
            $nodetype,
            null,
            $keyprefix . '_' . $suffix
        );
    }
}

/**
 * Left flat navigation (Boost drawer): 檢定管理 + children.
 */
function local_tm_certification_extend_navigation(global_navigation $nav): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    require_once(__DIR__ . '/classes/permissions_manager.php');
    if (!\local_tm_certification\permissions_manager::user_can_view_plugin()) {
        return;
    }

    $node = $nav->add(
        get_string('nav_main', 'local_tm_certification'),
        new moodle_url('/local/tm_certification/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'tm_certification',
        new pix_icon('i/completion-manual-y', '')
    );
    $node->showinflatnavigation = true;
    $node->force_open();

    local_tm_certification_add_nav_children($node, 'tm_certification', navigation_node::TYPE_CUSTOM);
}

/**
 * Classic / Administration block on site home (and frontpage settings).
 */
function local_tm_certification_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    require_once(__DIR__ . '/classes/permissions_manager.php');
    if (!\local_tm_certification\permissions_manager::user_can_manage() && !is_siteadmin()) {
        return;
    }

    $onfrontpage = ($context->contextlevel === CONTEXT_COURSE && (int)$context->instanceid === (int)SITEID);
    $onfpsettings = false;
    if (!empty($PAGE) && !empty($PAGE->pagetype)) {
        $onfpsettings = ($PAGE->pagetype === 'admin-setting-frontpagesettings');
        if (!$onfpsettings && strpos($PAGE->pagetype, 'admin-setting') === 0) {
            $onfpsettings = (optional_param('section', '', PARAM_ALPHANUMEXT) === 'frontpagesettings');
        }
    }
    if (!$onfrontpage && !$onfpsettings) {
        return;
    }

    $node = $settingsnav->add(
        get_string('nav_main', 'local_tm_certification'),
        new moodle_url('/local/tm_certification/index.php'),
        navigation_node::TYPE_SETTING,
        null,
        'tm_certification_fp_manage',
        new pix_icon('i/settings', '')
    );
    $node->force_open();

    local_tm_certification_add_nav_children($node, 'tm_cert_fp', navigation_node::TYPE_SETTING);
}

/**
 * Inject 考生編號 into user/editadvanced.php (next to 機構), admin-only.
 *
 * @return string
 */
function local_tm_certification_before_footer(): string {
    require_once(__DIR__ . '/classes/user_edit_candidate_field.php');
    return \local_tm_certification\user_edit_candidate_field::render_inject_markup();
}
