<?php
/**
 * AJAX: validate candidate ID change before user/editadvanced save.
 *
 * @package    local_tm_certification
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/candidate_manager.php');

require_login();
require_sesskey();
require_capability('moodle/user:update', context_system::instance());

$userid = required_param('userid', PARAM_INT);
$candidateid = required_param('candidateid', PARAM_RAW);

$err = \local_tm_certification\candidate_manager::validate_candidate_id_change($userid, trim($candidateid));

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => ($err === null),
    'error' => $err,
], JSON_UNESCAPED_UNICODE);
die;
