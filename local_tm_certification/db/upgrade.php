<?php
/**
 * Upgrade steps for local_tm_certification.
 *
 * @package    local_tm_certification
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_tm_certification_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026072902) {
        $tablesxml = __DIR__ . '/install.xml';
        if (file_exists($tablesxml)) {
            $xmldbfile = new xmldb_file($tablesxml);
            if ($xmldbfile->loadXMLStructure()) {
                $structure = $xmldbfile->getStructure();
                foreach ($structure->getTables() as $table) {
                    if (!$dbman->table_exists($table)) {
                        $dbman->create_table($table);
                    }
                }
            }
        }
        upgrade_plugin_savepoint(true, 2026072902, 'local', 'tm_certification');
    }

    if ($oldversion < 2026072904) {
        $sessiontable = new xmldb_table('local_tm_cert_sessions');
        $venueid = new xmldb_field('venueid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'certlevel');
        if (!$dbman->field_exists($sessiontable, $venueid)) {
            $dbman->add_field($sessiontable, $venueid);
        }

        $venuetable = new xmldb_table('local_tm_cert_venues');
        if (!$dbman->table_exists($venuetable)) {
            $venuetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $venuetable->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $venuetable->add_field('certlevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'basic');
            $venuetable->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $venuetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $venuetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $venuetable->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $venuetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $venuetable->add_index('idx_level', XMLDB_INDEX_NOTUNIQUE, ['certlevel']);
            $venuetable->add_index('idx_active', XMLDB_INDEX_NOTUNIQUE, ['active']);
            $dbman->create_table($venuetable);
        }

        $insttable = new xmldb_table('local_tm_cert_instlvl');
        if (!$dbman->table_exists($insttable)) {
            $insttable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $insttable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $insttable->add_field('certlevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'basic');
            $insttable->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $insttable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $insttable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $insttable->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $insttable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $insttable->add_key('uq_user_level', XMLDB_KEY_UNIQUE, ['userid', 'certlevel']);
            $insttable->add_index('idx_user', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $insttable->add_index('idx_level', XMLDB_INDEX_NOTUNIQUE, ['certlevel']);
            $dbman->create_table($insttable);
        }

        upgrade_plugin_savepoint(true, 2026072904, 'local', 'tm_certification');
    }

    if ($oldversion < 2026072905) {
        $certtable = new xmldb_table('local_tm_cert_instrcert');
        if (!$dbman->table_exists($certtable)) {
            $certtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $certtable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $certtable->add_field('certifiedat', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $certtable->add_field('renewedat', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $certtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $certtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $certtable->add_field('updatedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $certtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $certtable->add_key('uq_userid', XMLDB_KEY_UNIQUE, ['userid']);
            $dbman->create_table($certtable);
        }
        upgrade_plugin_savepoint(true, 2026072905, 'local', 'tm_certification');
    }

    if ($oldversion < 2026072906) {
        $venuetable = new xmldb_table('local_tm_cert_venues');
        $venuecode = new xmldb_field('venuecode', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'id');
        if (!$dbman->field_exists($venuetable, $venuecode)) {
            $dbman->add_field($venuetable, $venuecode);
        }
        $key = new xmldb_key('uq_venue_code', XMLDB_KEY_UNIQUE, ['venuecode']);
        // Moodle 3.9 database_manager does not implement key_exists().
        if (method_exists($dbman, 'key_exists')) {
            if (!$dbman->key_exists($venuetable, $key)) {
                $dbman->add_key($venuetable, $key);
            }
        } else {
            try {
                $dbman->add_key($venuetable, $key);
            } catch (\ddl_exception $e) {
                // Ignore duplicate-key case on rerun without savepoint.
                if (stripos($e->getMessage(), 'already exists') === false
                    && stripos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
        upgrade_plugin_savepoint(true, 2026072906, 'local', 'tm_certification');
    }

    if ($oldversion < 2026072907) {
        $venuetable = new xmldb_table('local_tm_cert_venues');

        $passedat = new xmldb_field('passedat', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'certlevel');
        if (!$dbman->field_exists($venuetable, $passedat)) {
            $dbman->add_field($venuetable, $passedat);
        }

        // Replace unique(venuecode) with unique(venuecode, certlevel) to allow same venue code across levels.
        $oldkey = new xmldb_key('uq_venue_code', XMLDB_KEY_UNIQUE, ['venuecode']);
        try {
            $dbman->drop_key($venuetable, $oldkey);
        } catch (\ddl_exception $e) {
            // old key may not exist on some upgrade paths.
        }
        $newkey = new xmldb_key('uq_venue_code_lvl', XMLDB_KEY_UNIQUE, ['venuecode', 'certlevel']);
        if (method_exists($dbman, 'key_exists')) {
            if (!$dbman->key_exists($venuetable, $newkey)) {
                $dbman->add_key($venuetable, $newkey);
            }
        } else {
            try {
                $dbman->add_key($venuetable, $newkey);
            } catch (\ddl_exception $e) {
                if (stripos($e->getMessage(), 'already exists') === false
                    && stripos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026072907, 'local', 'tm_certification');
    }

    if ($oldversion < 2026072908) {
        $apptable = new xmldb_table('local_tm_cert_apps');
        if (!$dbman->table_exists($apptable)) {
            $apptable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $apptable->add_field('applicantid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('instructorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('certlevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'basic');
            $apptable->add_field('venueid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $apptable->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $apptable->add_field('applicantnote', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $apptable->add_field('managernote', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $apptable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('timesubmitted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_field('timereviewed', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $apptable->add_field('reviewedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $apptable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $apptable->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $apptable->add_index('idx_applicant', XMLDB_INDEX_NOTUNIQUE, ['applicantid']);
            $apptable->add_index('idx_instructor', XMLDB_INDEX_NOTUNIQUE, ['instructorid']);
            $apptable->add_index('idx_sessionid', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $dbman->create_table($apptable);
        }
        upgrade_plugin_savepoint(true, 2026072908, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073000) {
        $candtable = new xmldb_table('local_tm_cert_candidates');
        if (!$dbman->table_exists($candtable)) {
            $candtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $candtable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $candtable->add_field('candidateid', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $candtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $candtable->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $candtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $candtable->add_key('uq_userid', XMLDB_KEY_UNIQUE, ['userid']);
            $candtable->add_key('uq_candidateid', XMLDB_KEY_UNIQUE, ['candidateid']);
            $dbman->create_table($candtable);
        }

        $learntable = new xmldb_table('local_tm_cert_app_learners');
        if (!$dbman->table_exists($learntable)) {
            $learntable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $learntable->add_field('applicationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $learntable->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $learntable->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $learntable->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $learntable->add_field('institution', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $learntable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $learntable->add_field('candidateid', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $learntable->add_field('scenario', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $learntable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $learntable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $learntable->add_index('idx_app', XMLDB_INDEX_NOTUNIQUE, ['applicationid']);
            $learntable->add_index('idx_email', XMLDB_INDEX_NOTUNIQUE, ['email']);
            $dbman->create_table($learntable);
        }

        upgrade_plugin_savepoint(true, 2026073000, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073034) {
        $pwtable = new xmldb_table('local_tm_cert_pwbackup');
        if (!$dbman->table_exists($pwtable)) {
            $pwtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $pwtable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $pwtable->add_field('passwordhash', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $pwtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $pwtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $pwtable->add_key('uq_userid', XMLDB_KEY_UNIQUE, ['userid']);
            $dbman->create_table($pwtable);
        }
        upgrade_plugin_savepoint(true, 2026073034, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073037) {
        // Formerly enabled auto-harden of mapped courses; feature removed (see 2026073040).
        upgrade_plugin_savepoint(true, 2026073037, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073038) {
        $apptable = new xmldb_table('local_tm_cert_apps');
        $timedeleted = new xmldb_field('timedeleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'reviewedby');
        if (!$dbman->field_exists($apptable, $timedeleted)) {
            $dbman->add_field($apptable, $timedeleted);
        }
        $idx = new xmldb_index('idx_timedeleted', XMLDB_INDEX_NOTUNIQUE, ['timedeleted']);
        if (!$dbman->index_exists($apptable, $idx)) {
            $dbman->add_index($apptable, $idx);
        }
        upgrade_plugin_savepoint(true, 2026073038, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073039) {
        // Formerly re-applied auto-harden; feature removed (see 2026073040).
        upgrade_plugin_savepoint(true, 2026073039, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073040) {
        // Drop auto-hide setting; do not change course visibility (site admin may restore manually).
        unset_config('auto_harden_mapped_courses', 'local_tm_certification');
        upgrade_plugin_savepoint(true, 2026073040, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073041) {
        // Prerequisite practical: also accept legacy 〔參〕 on mapped attendance (no DB change).
        upgrade_plugin_savepoint(true, 2026073041, 'local', 'tm_certification');
    }

    if ($oldversion < 2026073051) {
        $sessiontable = new xmldb_table('local_tm_cert_sessions');
        $checkintoken = new xmldb_field('checkintoken', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'attendancesessid');
        if (!$dbman->field_exists($sessiontable, $checkintoken)) {
            $dbman->add_field($sessiontable, $checkintoken);
        }

        $rctable = new xmldb_table('local_tm_cert_rollcall');
        if (!$dbman->table_exists($rctable)) {
            $rctable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $rctable->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'present');
            $rctable->add_field('source', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'staff');
            $rctable->add_field('timemarked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_field('markedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rctable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $rctable->add_key('uq_session_enrol', XMLDB_KEY_UNIQUE, ['sessionid', 'enrolid']);
            $rctable->add_index('idx_session', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $rctable->add_index('idx_userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $dbman->create_table($rctable);
        }

        upgrade_plugin_savepoint(true, 2026073051, 'local', 'tm_certification');
    }

    if ($oldversion < 2026082800) {
        // Ensure admin user-edit field for candidate ID (requires profilefield_tmcandidate).
        require_once(__DIR__ . '/../classes/profile_candidate_field.php');
        \local_tm_certification\profile_candidate_field::ensure_installed();
        upgrade_plugin_savepoint(true, 2026082800, 'local', 'tm_certification');
    }

    if ($oldversion < 2026082801) {
        // JS inject path on editadvanced + user_updated observer (no profilefield required).
        upgrade_plugin_savepoint(true, 2026082801, 'local', 'tm_certification');
    }

    if ($oldversion < 2026082802) {
        // Fix namespaced html_writer on editadvanced inject.
        upgrade_plugin_savepoint(true, 2026082802, 'local', 'tm_certification');
    }

    if ($oldversion < 2026082803) {
        // Pre-submit duplicate candidate ID check on user edit.
        upgrade_plugin_savepoint(true, 2026082803, 'local', 'tm_certification');
    }

    return true;
}
