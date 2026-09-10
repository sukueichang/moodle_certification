<?php
/**
 * Single menu registry for Site admin tree + left flat navigation.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/permissions_manager.php');

class admin_menu {

    public const GROUP_OPS = 'ops';
    public const GROUP_SETTINGS = 'settings';

    /**
     * Visible menu items (ops first, then settings). One source for both UIs.
     *
     * @return array<int,array{
     *   key:string,
     *   group:string,
     *   labelkey:string,
     *   url:\moodle_url,
     *   caps:string[],
     *   kind:string,
     *   section:string
     * }>
     */
    public static function items(): array {
        return [
            [
                'key' => 'sessions',
                'group' => self::GROUP_OPS,
                'labelkey' => 'nav_sessions',
                'url' => new \moodle_url('/local/tm_certification/admin/sessions.php'),
                'caps' => ['manage', 'approve'],
                'kind' => 'external',
                'section' => 'local_tm_certification_sessions_ext',
            ],
            [
                'key' => 'applications',
                'group' => self::GROUP_OPS,
                'labelkey' => 'nav_applications',
                'url' => new \moodle_url('/local/tm_certification/admin/review.php'),
                'caps' => ['manage', 'approve'],
                'kind' => 'external',
                'section' => 'local_tm_certification_review_ext',
            ],
            [
                'key' => 'venues',
                'group' => self::GROUP_OPS,
                'labelkey' => 'nav_venues',
                'url' => new \moodle_url('/local/tm_certification/admin/venues.php'),
                'caps' => ['manage'],
                'kind' => 'external',
                'section' => 'local_tm_certification_venues_ext',
            ],
            [
                'key' => 'instructors',
                'group' => self::GROUP_OPS,
                'labelkey' => 'nav_instructor_levels',
                'url' => new \moodle_url('/local/tm_certification/admin/instructors.php'),
                'caps' => ['manage'],
                'kind' => 'external',
                'section' => 'local_tm_certification_instructors_ext',
            ],
            [
                'key' => 'assess',
                'group' => self::GROUP_OPS,
                'labelkey' => 'setting_assess_heading',
                'url' => new \moodle_url('/local/tm_certification/admin/assess_components.php'),
                'caps' => ['manage'],
                'kind' => 'external',
                'section' => 'local_tm_certification_assess_ext',
            ],
            [
                'key' => 'legacy_migrate',
                'group' => self::GROUP_OPS,
                'labelkey' => 'nav_legacy_migrate',
                'url' => new \moodle_url('/local/tm_certification/admin/legacy_name_migrate.php'),
                'caps' => ['manage'],
                'kind' => 'external',
                'section' => 'local_tm_certification_legacy_migrate_ext',
            ],
            [
                'key' => 'dashboard',
                'group' => self::GROUP_SETTINGS,
                'labelkey' => 'setting_dashboard_heading',
                'url' => new \moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_dashboard']),
                'caps' => ['siteconfig'],
                'kind' => 'setting',
                'section' => 'local_tm_certification_dashboard',
            ],
            [
                'key' => 'coursemap',
                'group' => self::GROUP_SETTINGS,
                'labelkey' => 'setting_coursemap_heading',
                'url' => new \moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_coursemap']),
                'caps' => ['siteconfig'],
                'kind' => 'setting',
                'section' => 'local_tm_certification_coursemap',
            ],
            [
                'key' => 'roles',
                'group' => self::GROUP_SETTINGS,
                'labelkey' => 'setting_roles_heading',
                'url' => new \moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_roles']),
                'caps' => ['siteconfig'],
                'kind' => 'setting',
                'section' => 'local_tm_certification_roles',
            ],
            [
                'key' => 'notify',
                'group' => self::GROUP_SETTINGS,
                'labelkey' => 'setting_notify_heading',
                'url' => new \moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_notify']),
                'caps' => ['siteconfig'],
                'kind' => 'setting',
                'section' => 'local_tm_certification_notify',
            ],
            [
                'key' => 'about',
                'group' => self::GROUP_SETTINGS,
                'labelkey' => 'nav_plugin_about',
                'url' => new \moodle_url('/admin/settings.php', ['section' => 'local_tm_certification_about']),
                'caps' => ['siteconfig'],
                'kind' => 'setting',
                'section' => 'local_tm_certification_about',
            ],
        ];
    }

    /**
     * Moodle capability shortname for external admin pages.
     */
    public static function moodle_capability(array $item): string {
        $caps = $item['caps'] ?? [];
        if (in_array('approve', $caps, true) && !in_array('manage', $caps, true)) {
            return 'local/tm_certification:approve';
        }
        if (in_array('manage', $caps, true)) {
            return 'local/tm_certification:manage';
        }
        return 'moodle/site:config';
    }

    /**
     * Whether current user may see this item in flat nav.
     */
    public static function user_can_see(array $item): bool {
        $caps = $item['caps'] ?? [];
        foreach ($caps as $cap) {
            if ($cap === 'siteconfig' && has_capability('moodle/site:config', \context_system::instance())) {
                return true;
            }
            if ($cap === 'manage' && permissions_manager::user_can_manage()) {
                return true;
            }
            if ($cap === 'approve' && permissions_manager::user_can_approve()) {
                return true;
            }
        }
        return false;
    }
}
