<?php
/**
 * Permissions helper — logical identities via capability and/or configured Moodle roles.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

class permissions_manager {

    public const IDENTITY_MANAGE = 'manage';
    public const IDENTITY_APPROVE = 'approve';
    public const IDENTITY_INSTRUCT = 'instruct';
    public const IDENTITY_EXAMINEE = 'examinee';

    /** Config keys storing comma-separated role ids. */
    public const CFG_ROLES_MANAGE = 'roles_manage';
    public const CFG_ROLES_APPROVE = 'roles_approve';
    public const CFG_ROLES_INSTRUCT = 'roles_instruct';
    public const CFG_ROLES_EXAMINEE = 'roles_examinee';
    public const CFG_ROLES_VIEW_DASHBOARD = 'roles_view_dashboard';

    public const CFG_DASHBOARD_ENABLED = 'dashboard_enabled';
    public const CFG_DASHBOARD_SHOW_MANAGE = 'dashboard_show_manage';
    public const CFG_DASHBOARD_SHOW_INSTRUCT = 'dashboard_show_instruct';
    public const CFG_DASHBOARD_SHOW_EXAMINEE = 'dashboard_show_examinee';

    /**
     * @return string[]
     */
    public static function identity_keys(): array {
        return [
            self::IDENTITY_MANAGE,
            self::IDENTITY_APPROVE,
            self::IDENTITY_INSTRUCT,
            self::IDENTITY_EXAMINEE,
        ];
    }

    public static function capability_for_identity(string $identity): string {
        switch ($identity) {
            case self::IDENTITY_MANAGE:
                return 'local/tm_certification:manage';
            case self::IDENTITY_APPROVE:
                return 'local/tm_certification:approve';
            case self::IDENTITY_INSTRUCT:
                return 'local/tm_certification:instruct';
            case self::IDENTITY_EXAMINEE:
                return 'local/tm_certification:examinee';
            default:
                return '';
        }
    }

    public static function config_key_for_identity(string $identity): string {
        switch ($identity) {
            case self::IDENTITY_MANAGE:
                return self::CFG_ROLES_MANAGE;
            case self::IDENTITY_APPROVE:
                return self::CFG_ROLES_APPROVE;
            case self::IDENTITY_INSTRUCT:
                return self::CFG_ROLES_INSTRUCT;
            case self::IDENTITY_EXAMINEE:
                return self::CFG_ROLES_EXAMINEE;
            default:
                return '';
        }
    }

    /**
     * @return int[]
     */
    public static function parse_roleids(string $raw): array {
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return [];
        }
        $roleids = array_map('intval', $parts);
        $roleids = array_filter($roleids, static function(int $id): bool {
            return $id > 0;
        });
        return array_values(array_unique($roleids));
    }

    /**
     * @return int[]
     */
    public static function get_configured_roleids(string $configkey): array {
        return self::parse_roleids((string)get_config('local_tm_certification', $configkey));
    }

    /**
     * @return int[]
     */
    public static function get_user_system_roleids(int $userid): array {
        $ctx = \context_system::instance();
        $assignments = get_user_roles($ctx, $userid, true);
        if (empty($assignments)) {
            return [];
        }
        $roleids = [];
        foreach ($assignments as $assignment) {
            $roleids[] = (int)$assignment->roleid;
        }
        return array_values(array_unique(array_filter($roleids, static function(int $id): bool {
            return $id > 0;
        })));
    }

    public static function user_has_configured_role(string $configkey, ?\stdClass $user = null): bool {
        global $USER;
        $user = $user ?? $USER;
        if (empty($user->id) || isguestuser($user)) {
            return false;
        }
        $configured = self::get_configured_roleids($configkey);
        if (empty($configured)) {
            return false;
        }
        $userroles = self::get_user_system_roleids((int)$user->id);
        return !empty(array_intersect($configured, $userroles));
    }

    public static function user_has_identity(string $identity, ?\stdClass $user = null): bool {
        global $USER;
        $user = $user ?? $USER;
        if (empty($user->id) || isguestuser($user)) {
            return false;
        }
        if (is_siteadmin($user) && in_array($identity, [self::IDENTITY_MANAGE, self::IDENTITY_APPROVE], true)) {
            return true;
        }

        $cap = self::capability_for_identity($identity);
        $ctx = \context_system::instance();
        if ($cap !== '' && has_capability($cap, $ctx, $user)) {
            return true;
        }

        $cfgkey = self::config_key_for_identity($identity);
        if ($cfgkey === '') {
            return false;
        }
        return self::user_has_configured_role($cfgkey, $user);
    }

    public static function user_can_manage(?\stdClass $user = null): bool {
        return self::user_has_identity(self::IDENTITY_MANAGE, $user);
    }

    public static function user_can_approve(?\stdClass $user = null): bool {
        return self::user_can_manage($user) || self::user_has_identity(self::IDENTITY_APPROVE, $user);
    }

    public static function user_can_instruct(?\stdClass $user = null): bool {
        return self::user_can_manage($user) || self::user_has_identity(self::IDENTITY_INSTRUCT, $user);
    }

    /**
     * Site-wide batch certificate download (dashboard entry; not session-scoped).
     * Manage / approve / instruct identities.
     */
    public static function user_can_batch_download_certs(?\stdClass $user = null): bool {
        return self::user_can_manage($user)
            || self::user_can_approve($user)
            || self::user_can_instruct($user);
    }

    public static function user_can_examinee(?\stdClass $user = null): bool {
        return self::user_has_identity(self::IDENTITY_EXAMINEE, $user);
    }

    /**
     * Global dashboard feature flag (default on).
     */
    public static function is_dashboard_globally_enabled(): bool {
        $raw = get_config('local_tm_certification', self::CFG_DASHBOARD_ENABLED);
        if ($raw === false || $raw === null || $raw === '') {
            return true;
        }
        return (bool)(int)$raw;
    }

    public static function is_dashboard_view_enabled(string $view): bool {
        $map = [
            'manage' => self::CFG_DASHBOARD_SHOW_MANAGE,
            'instruct' => self::CFG_DASHBOARD_SHOW_INSTRUCT,
            'examinee' => self::CFG_DASHBOARD_SHOW_EXAMINEE,
        ];
        if (!isset($map[$view])) {
            return false;
        }
        $raw = get_config('local_tm_certification', $map[$view]);
        if ($raw === false || $raw === null || $raw === '') {
            return true;
        }
        return (bool)(int)$raw;
    }

    /**
     * Whether the user may see plugin navigation / open dashboard entry.
     */
    public static function user_can_view_plugin(?\stdClass $user = null): bool {
        global $USER;
        $user = $user ?? $USER;
        if (empty($user->id) || isguestuser($user)) {
            return false;
        }
        if (is_siteadmin($user)) {
            return true;
        }
        if (!self::is_dashboard_globally_enabled()) {
            // Still allow managers to reach admin tools via direct settings URLs.
            return self::user_can_manage($user);
        }

        $ctx = \context_system::instance();
        if (has_capability('local/tm_certification:viewdashboard', $ctx, $user)) {
            return true;
        }
        if (self::user_has_configured_role(self::CFG_ROLES_VIEW_DASHBOARD, $user)) {
            return true;
        }

        // Anyone with a logical identity may view (subject to per-view toggles on render).
        return self::user_can_manage($user)
            || self::user_can_approve($user)
            || self::user_can_instruct($user)
            || self::user_can_examinee($user);
    }

    public static function require_view_access(): void {
        if (self::user_can_view_plugin()) {
            return;
        }
        redirect(
            new \moodle_url('/'),
            get_string('error_view_access_denied', 'local_tm_certification'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    /**
     * Resolve which dashboard shell to show. Priority: manage > instruct > examinee.
     *
     * @return string manage|instruct|examinee|none
     */
    public static function resolve_dashboard_view(?\stdClass $user = null): string {
        if (self::user_can_manage($user) || self::user_can_approve($user)) {
            if (self::is_dashboard_view_enabled('manage')) {
                return 'manage';
            }
        }
        if (self::user_can_instruct($user) && self::is_dashboard_view_enabled('instruct')) {
            return 'instruct';
        }
        if (self::user_can_examinee($user) && self::is_dashboard_view_enabled('examinee')) {
            return 'examinee';
        }
        return 'none';
    }

    /**
     * Role options for admin multiselect settings.
     *
     * @return array<int,string> roleid => name
     */
    public static function role_options(): array {
        $roles = role_get_names(\context_system::instance(), ROLENAME_ORIGINAL);
        $out = [];
        foreach ($roles as $role) {
            $out[(int)$role->id] = $role->localname;
        }
        asort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }
}
