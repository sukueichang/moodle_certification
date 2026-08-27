<?php
/**
 * CSV batch roster parse / preview / commit helpers.
 *
 * @package    local_tm_certification
 */

namespace local_tm_certification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/candidate_manager.php');
require_once(__DIR__ . '/enrolment_manager.php');
require_once(__DIR__ . '/prerequisite_helper.php');

class batch_helper {

    /**
     * Public CSV template body (UTF-8 BOM for Excel).
     * Header labels follow the current UI language; import accepts EN + ZH aliases.
     */
    public static function template_csv(): string {
        $bom = "\xEF\xBB\xBF";
        // Moodle lastname = 姓, firstname = 名; username will be allocated candidate ID.
        $header = self::csv_escape_line([
            get_string('batch_csv_lastname', 'local_tm_certification'),
            get_string('batch_csv_firstname', 'local_tm_certification'),
            get_string('batch_csv_email', 'local_tm_certification'),
            get_string('batch_csv_institution', 'local_tm_certification'),
        ]);
        $sample = self::csv_escape_line(['王', '美', 'mei.wang@example.com', 'Techman']);
        return $bom . $header . "\n" . $sample . "\n";
    }

    /**
     * Build class-day login sheet CSV for a session roster.
     *
     * Account column = real Moodle username (not candidate ID).
     * Password = Tmai + candidate ID (aligned on roster add).
     *
     * @param \stdClass[] $roster from enrolment_manager::get_session_roster()
     */
    public static function roster_login_csv(array $roster): string {
        $bom = "\xEF\xBB\xBF";
        // Headers follow current UI language (zh_tw site → Chinese; en → English).
        $header = self::csv_escape_line([
            get_string('roster_csv_username', 'local_tm_certification'),
            get_string('roster_csv_fullname', 'local_tm_certification'),
            get_string('roster_csv_password', 'local_tm_certification'),
            get_string('roster_csv_institution', 'local_tm_certification'),
        ]);
        $lines = [$header];
        foreach ($roster as $row) {
            $candidateid = trim((string)($row->candidateid ?? ''));
            if ($candidateid === '' && candidate_manager::is_candidate_id_format((string)($row->username ?? ''))) {
                $candidateid = (string)$row->username;
            }
            // Always prefer the real Moodle login username (existing accounts keep theirs).
            $username = trim((string)($row->username ?? ''));
            if ($username === '') {
                $username = $candidateid;
            }
            // Avoid fullname() here — download scripts must not depend on $PAGE name display.
            $fullname = trim((string)($row->lastname ?? '') . (string)($row->firstname ?? ''));
            if ($fullname === '') {
                $fullname = trim((string)($row->firstname ?? '') . ' ' . (string)($row->lastname ?? ''));
            }
            $password = $candidateid !== '' ? candidate_manager::default_password_for($candidateid) : '';
            $institution = (string)($row->institution ?? '');
            $lines[] = self::csv_escape_line([$username, $fullname, $password, $institution]);
        }
        return $bom . implode("\n", $lines) . "\n";
    }

    /**
     * @param string[] $fields
     */
    public static function csv_escape_line(array $fields): string {
        $out = [];
        foreach ($fields as $field) {
            $field = (string)$field;
            if (strpbrk($field, ",\"\n\r") !== false) {
                $field = '"' . str_replace('"', '""', $field) . '"';
            }
            $out[] = $field;
        }
        return implode(',', $out);
    }

    /**
     * Parse uploaded CSV content into normalised rows.
     *
     * @return array{rows:array,errors:string[]}
     */
    public static function parse_csv_string(string $content): array {
        $content = self::strip_bom($content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_values(array_filter(explode("\n", $content), static function ($line) {
            return trim($line) !== '';
        }));

        $errors = [];
        $rows = [];
        if (empty($lines)) {
            return ['rows' => [], 'errors' => [get_string('error_batch_csv_empty', 'local_tm_certification')]];
        }

        $header = str_getcsv(array_shift($lines));
        $map = self::map_headers($header);
        if ($map['email'] === null || $map['firstname'] === null || $map['lastname'] === null) {
            return ['rows' => [], 'errors' => [get_string('error_batch_csv_header', 'local_tm_certification')]];
        }
        // Accept either column order; values always map to Moodle lastname=姓 / firstname=名.

        $lineno = 1;
        $seen = [];
        foreach ($lines as $line) {
            $lineno++;
            $cols = str_getcsv($line);
            $email = isset($map['email'], $cols[$map['email']]) ? trim((string)$cols[$map['email']]) : '';
            $firstname = isset($map['firstname'], $cols[$map['firstname']]) ? trim((string)$cols[$map['firstname']]) : '';
            $lastname = isset($map['lastname'], $cols[$map['lastname']]) ? trim((string)$cols[$map['lastname']]) : '';
            $institution = ($map['institution'] !== null && isset($cols[$map['institution']]))
                ? trim((string)$cols[$map['institution']]) : '';

            if ($email === '' && $firstname === '' && $lastname === '') {
                continue;
            }
            if ($email === '' || !validate_email($email)) {
                $errors[] = get_string('error_batch_csv_row_email', 'local_tm_certification', $lineno);
                continue;
            }
            if ($firstname === '' || $lastname === '') {
                $errors[] = get_string('error_batch_csv_row_name', 'local_tm_certification', $lineno);
                continue;
            }
            $key = \core_text::strtolower($email);
            if (isset($seen[$key])) {
                $errors[] = get_string('error_batch_csv_row_dup', 'local_tm_certification', $email);
                continue;
            }
            $seen[$key] = true;
            $rows[] = [
                'email' => $key,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'institution' => $institution,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Classify rows for a target certification level.
     *
     * Basic: three candidate scenarios. Intermediate/advanced: prerequisite check (no new accounts).
     *
     * @param array $rows
     * @param string $certlevel session_manager::LEVEL_*
     * @return array{items:array,counts:array,eligible_count:int,blocked_count:int}
     */
    public static function preview_rows(array $rows, string $certlevel = 'basic'): array {
        $items = [];
        $counts = [
            candidate_manager::SCENARIO_NEW => 0,
            candidate_manager::SCENARIO_ISSUE_ID => 0,
            candidate_manager::SCENARIO_KEEP_ID => 0,
            prerequisite_helper::SCENARIO_PREREQ_OK => 0,
            prerequisite_helper::SCENARIO_PREREQ_FAIL => 0,
            'total' => 0,
        ];
        $eligible = 0;
        $blocked = 0;
        foreach ($rows as $row) {
            $item = prerequisite_helper::evaluate_row(
                (string)$row['email'],
                (string)$row['firstname'],
                (string)$row['lastname'],
                (string)($row['institution'] ?? ''),
                $certlevel
            );
            $items[] = $item;
            $scenario = (string)$item['scenario'];
            if (!isset($counts[$scenario])) {
                $counts[$scenario] = 0;
            }
            $counts[$scenario]++;
            $counts['total']++;
            if (!empty($item['eligible'])) {
                $eligible++;
            } else {
                $blocked++;
            }
        }
        return [
            'items' => $items,
            'counts' => $counts,
            'eligible_count' => $eligible,
            'blocked_count' => $blocked,
            'certlevel' => $certlevel,
            'requires_prereq' => prerequisite_helper::requires_prerequisite($certlevel),
        ];
    }

    /**
     * Commit previewed rows into an open session (provision + candidate id + roster).
     *
     * Mid/adv: only eligible rows; link existing account only (never create).
     *
     * @param array $items from preview_rows
     * @return array{ok:int,failed:int,skipped:int,details:array}
     */
    public static function commit_to_session(int $sessionid, array $items, int $addedby = 0): array {
        require_once(__DIR__ . '/session_manager.php');
        $session = session_manager::get_session($sessionid);
        if (!session_manager::can_add_examinees($session)) {
            throw new \moodle_exception('error_session_ended_no_enrol', 'local_tm_certification');
        }
        $certlevel = (string)$session->certlevel;
        $needsprereq = prerequisite_helper::requires_prerequisite($certlevel);

        $ok = 0;
        $failed = 0;
        $skipped = 0;
        $details = [];
        foreach ($items as $item) {
            if ($needsprereq && empty($item['eligible'])) {
                $skipped++;
                $details[] = [
                    'email' => $item['email'] ?? '',
                    'ok' => false,
                    'skipped' => true,
                    'error' => prerequisite_helper::format_reasons($item['reasons'] ?? []),
                ];
                continue;
            }
            try {
                if ($needsprereq) {
                    $userid = (int)($item['userid'] ?? 0);
                    if ($userid <= 0) {
                        throw new \moodle_exception('error_prereq_account_not_found', 'local_tm_certification');
                    }
                    $candidateid = (string)($item['candidateid'] ?? '');
                    if ($candidateid === '') {
                        throw new \moodle_exception('error_prereq_no_candidate_id', 'local_tm_certification');
                    }
                    // Re-check at commit time (scores may have changed).
                    $recheck = prerequisite_helper::evaluate_row(
                        (string)$item['email'],
                        (string)$item['firstname'],
                        (string)$item['lastname'],
                        (string)($item['institution'] ?? ''),
                        $certlevel
                    );
                    if (empty($recheck['eligible'])) {
                        throw new \moodle_exception(
                            'error_prereq_blocked',
                            'local_tm_certification',
                            '',
                            prerequisite_helper::format_reasons($recheck['reasons'] ?? [])
                        );
                    }
                    $userid = (int)$recheck['userid'];
                    $candidateid = (string)$recheck['candidateid'];
                    $password = enrolment_manager::apply_default_password($userid, $candidateid);
                    $enrolid = enrolment_manager::add_to_session(
                        $sessionid,
                        $userid,
                        $addedby,
                        enrolment_manager::ENROL_APPROVED,
                        true
                    );
                    $details[] = [
                        'email' => $item['email'],
                        'userid' => $userid,
                        'candidateid' => $candidateid,
                        'created' => false,
                        'enrolid' => $enrolid,
                        'initial_password' => $password,
                        'ok' => true,
                    ];
                    require_once(__DIR__ . '/notification_helper.php');
                    notification_helper::notify_examinee_added(
                        $userid,
                        $sessionid,
                        false,
                        $candidateid,
                        $password
                    );
                    $ok++;
                    continue;
                }

                $provision = enrolment_manager::provision_or_link_user(
                    (string)$item['email'],
                    (string)$item['firstname'],
                    (string)$item['lastname'],
                    (string)($item['institution'] ?? '')
                );
                $userid = (int)$provision['userid'];
                $candidateid = candidate_manager::ensure_candidate_id($userid, $addedby);
                $password = enrolment_manager::apply_default_password($userid, $candidateid);
                $enrolid = enrolment_manager::add_to_session(
                    $sessionid,
                    $userid,
                    $addedby,
                    enrolment_manager::ENROL_APPROVED,
                    true
                );
                $details[] = [
                    'email' => $item['email'],
                    'userid' => $userid,
                    'candidateid' => $candidateid,
                    'created' => !empty($provision['created']),
                    'enrolid' => $enrolid,
                    'initial_password' => $password,
                    'ok' => true,
                ];
                require_once(__DIR__ . '/notification_helper.php');
                notification_helper::notify_examinee_added(
                    $userid,
                    $sessionid,
                    !empty($provision['created']),
                    $candidateid,
                    $password
                );
                $ok++;
            } catch (\Throwable $e) {
                $details[] = [
                    'email' => $item['email'] ?? '',
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }
        }
        return ['ok' => $ok, 'failed' => $failed, 'skipped' => $skipped, 'details' => $details];
    }

    /**
     * Persist draft learners on an application (no account / ID write yet).
     */
    public static function save_app_learners(int $applicationid, array $items): void {
        global $DB;

        $DB->delete_records('local_tm_cert_app_learners', ['applicationid' => $applicationid]);
        $now = time();
        foreach ($items as $item) {
            $row = new \stdClass();
            $row->applicationid = $applicationid;
            $row->email = (string)$item['email'];
            $row->firstname = (string)$item['firstname'];
            $row->lastname = (string)$item['lastname'];
            $row->institution = (string)($item['institution'] ?? '') !== '' ? (string)$item['institution'] : null;
            $row->userid = !empty($item['userid']) ? (int)$item['userid'] : null;
            $row->candidateid = !empty($item['candidateid']) ? (string)$item['candidateid'] : null;
            $row->scenario = (string)($item['scenario'] ?? '');
            $row->timecreated = $now;
            $DB->insert_record('local_tm_cert_app_learners', $row);
        }
    }

    /**
     * @return \stdClass[]
     */
    public static function get_app_learners(int $applicationid): array {
        global $DB;
        return array_values($DB->get_records('local_tm_cert_app_learners', ['applicationid' => $applicationid], 'id ASC'));
    }

    /**
     * On approve: provision + candidate ID + enrol each draft learner.
     *
     * @return array{ok:int,failed:int}
     */
    public static function commit_app_learners_to_session(int $applicationid, int $sessionid, int $addedby = 0): array {
        require_once(__DIR__ . '/session_manager.php');
        $learners = self::get_app_learners($applicationid);
        if (empty($learners)) {
            return ['ok' => 0, 'failed' => 0, 'skipped' => 0];
        }
        $session = session_manager::get_session($sessionid);
        $items = [];
        foreach ($learners as $l) {
            $items[] = [
                'email' => $l->email,
                'firstname' => $l->firstname,
                'lastname' => $l->lastname,
                'institution' => (string)($l->institution ?? ''),
            ];
        }
        $preview = self::preview_rows($items, (string)$session->certlevel);
        return self::commit_to_session($sessionid, $preview['items'], $addedby);
    }

    /**
     * @param string[] $header
     * @return array{firstname:?int,lastname:?int,email:?int,institution:?int}
     */
    private static function map_headers(array $header): array {
        $map = ['firstname' => null, 'lastname' => null, 'email' => null, 'institution' => null];
        // Fixed synonyms + current-language template labels (so download ↔ upload always match).
        $aliases = [
            'lastname' => self::header_alias_list('batch_csv_lastname', [
                'lastname', 'last_name', 'last name', 'family name', 'familyname',
                '姓', '姓氏',
            ]),
            'firstname' => self::header_alias_list('batch_csv_firstname', [
                'firstname', 'first_name', 'first name', 'given name', 'givenname',
                '名', '名字',
            ]),
            'email' => self::header_alias_list('batch_csv_email', [
                'email', 'e-mail', 'mail', 'emailaddress', 'email address',
                '電子郵件', '电子信箱', '电子邮箱', '信箱', '邮箱',
            ]),
            'institution' => self::header_alias_list('batch_csv_institution', [
                'institution', 'company', 'org', 'organization', 'school', 'schoolname', 'school name',
                '單位', '单位', '機構', '机构', '公司', '學校名', '学校名', '學校', '学校',
            ]),
        ];
        foreach ($header as $idx => $raw) {
            $key = \core_text::strtolower(trim((string)$raw));
            $key = preg_replace('/^\xef\xbb\xbf/', '', $key);
            foreach ($aliases as $field => $names) {
                if (in_array($key, $names, true) && $map[$field] === null) {
                    $map[$field] = (int)$idx;
                }
            }
        }
        return $map;
    }

    /**
     * @param string[] $fixed
     * @return string[] lowercased unique aliases
     */
    private static function header_alias_list(string $langkey, array $fixed): array {
        $names = $fixed;
        $names[] = get_string($langkey, 'local_tm_certification');
        $out = [];
        foreach ($names as $name) {
            $n = \core_text::strtolower(trim((string)$name));
            if ($n !== '') {
                $out[$n] = true;
            }
        }
        return array_keys($out);
    }

    private static function strip_bom(string $content): string {
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return substr($content, 3);
        }
        return $content;
    }
}
