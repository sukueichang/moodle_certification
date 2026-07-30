<?php
/**
 * Create / edit a certification session.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../classes/permissions_manager.php');
require_once(__DIR__ . '/../classes/session_manager.php');
require_once(__DIR__ . '/../classes/catalog_manager.php');

use local_tm_certification\catalog_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
if (!permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/admin/edit_session.php', $id ? ['id' => $id] : []));
$PAGE->set_pagelayout('admin');
$title = $id ? get_string('session_edit', 'local_tm_certification') : get_string('session_create', 'local_tm_certification');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$session = null;
if ($id) {
    $session = session_manager::get_session($id);
}

class local_tm_certification_session_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $levels = session_manager::level_options();
        $mform->addElement('select', 'certlevel', get_string('session_level', 'local_tm_certification'), $levels);
        $mform->setType('certlevel', PARAM_ALPHANUMEXT);
        $mform->addRule('certlevel', null, 'required', null, 'client');

        $mform->addElement('static', 'name_preview', get_string('session_name', 'local_tm_certification'),
            get_string('session_name_auto_hint', 'local_tm_certification'));

        $mform->addElement('date_time_selector', 'starttime', get_string('session_start', 'local_tm_certification'));
        $mform->addElement('date_time_selector', 'endtime', get_string('session_end', 'local_tm_certification'));

        $initiallevel = session_manager::LEVEL_BASIC;
        if (!empty($this->_customdata['session']) && !empty($this->_customdata['session']->certlevel)) {
            $initiallevel = (string)$this->_customdata['session']->certlevel;
        }

        $venueopts = catalog_manager::venue_options($initiallevel);
        $venueopts = [0 => get_string('session_venue_none', 'local_tm_certification')] + $venueopts;
        $mform->addElement('select', 'venueid', get_string('session_venue', 'local_tm_certification'), $venueopts);
        $mform->setType('venueid', PARAM_INT);

        $instructoropts = catalog_manager::instructor_options($initiallevel);
        $instructoropts = [0 => get_string('session_instructor_none', 'local_tm_certification')] + $instructoropts;
        $mform->addElement('select', 'instructorid', get_string('session_instructor', 'local_tm_certification'), $instructoropts);
        $mform->setType('instructorid', PARAM_INT);

        $mform->addElement('select', 'status', get_string('session_status', 'local_tm_certification'), [
            session_manager::STATUS_OPEN => get_string('session_status_open', 'local_tm_certification'),
            session_manager::STATUS_CLOSED => get_string('session_status_closed', 'local_tm_certification'),
        ]);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}

$mform = new local_tm_certification_session_form(null, ['session' => $session]);

if ($session) {
    $mform->set_data([
        'id' => $session->id,
        'certlevel' => $session->certlevel,
        'starttime' => $session->starttime,
        'endtime' => $session->endtime,
        'venueid' => (int)($session->venueid ?? 0),
        'instructorid' => $session->instructorid,
        'status' => $session->status,
    ]);
} else {
    $mform->set_data([
        'id' => 0,
        'starttime' => time() + DAYSECS,
        'endtime' => time() + DAYSECS + 4 * HOURSECS,
        'status' => session_manager::STATUS_OPEN,
        'certlevel' => session_manager::LEVEL_BASIC,
    ]);
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/tm_certification/admin/sessions.php'));
}

if ($data = $mform->get_data()) {
    $payload = [
        'certlevel' => $data->certlevel,
        'starttime' => $data->starttime,
        'endtime' => $data->endtime,
        'venueid' => (int)$data->venueid,
        'instructorid' => (int)$data->instructorid,
        'status' => (int)$data->status,
    ];
        try {
        if (!empty($data->id)) {
            session_manager::update_session((int)$data->id, $payload);
            $sid = (int)$data->id;
        } else {
            $sid = session_manager::create_session($payload);
            require_once(__DIR__ . '/../classes/attendance_helper.php');
            \local_tm_certification\attendance_helper::ensure_attendance_slot_for_session($sid);
        }
        redirect(
            new moodle_url('/local/tm_certification/admin/roster.php', ['sessionid' => $sid]),
            get_string('session_saved', 'local_tm_certification'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }
}

$levelnames = session_manager::level_options();
$venuemap = catalog_manager::venue_options_by_level();
$instrmap = catalog_manager::instructor_options_by_level();

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('session_name_fixed_rule', 'local_tm_certification'), ['class' => 'alert alert-info']);
$mform->display();

$venuejson = json_encode($venuemap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$instrjson = json_encode($instrmap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$namejson = json_encode($levelnames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$prefix = json_encode(get_string('session_name_prefix', 'local_tm_certification'));
$suffix = json_encode(get_string('session_name_suffix', 'local_tm_certification'));
$nonevenue = json_encode(get_string('session_venue_none', 'local_tm_certification'));
$noneins = json_encode(get_string('session_instructor_none', 'local_tm_certification'));

echo html_writer::script("
(function() {
  try {
  var venueMap = $venuejson || {};
  var instructorMap = $instrjson || {};
  var levelNames = $namejson || {};
  var prefix = $prefix;
  var suffix = $suffix;
  var noneVenue = $nonevenue;
  var noneIns = $noneins;

  var levelSel = document.getElementById('id_certlevel');
  var venueSel = document.getElementById('id_venueid');
  var insSel = document.getElementById('id_instructorid');
  var preview = document.getElementById('id_name_preview');

  function fillSelect(sel, options, noneText) {
    if (!sel) return;
    var current = sel.value;
    sel.innerHTML = '';
    var opt = document.createElement('option');
    opt.value = '0';
    opt.textContent = noneText;
    sel.appendChild(opt);
    Object.keys(options || {}).forEach(function(k) {
      var o = document.createElement('option');
      o.value = k;
      o.textContent = options[k];
      sel.appendChild(o);
    });
    var found = false;
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value === current) {
        found = true;
        break;
      }
    }
    sel.value = found ? current : '0';
  }

  function refreshByLevel() {
    if (!levelSel) return;
    var lv = levelSel.value;
    fillSelect(venueSel, venueMap[lv] || {}, noneVenue);
    fillSelect(insSel, instructorMap[lv] || {}, noneIns);
    if (preview) {
      var label = levelNames[lv] || lv;
      preview.textContent = prefix + label + ']' + suffix;
    }
  }

  if (levelSel) {
    levelSel.addEventListener('change', refreshByLevel);
  }
  refreshByLevel();
  } catch (e) {
    if (window.console && console.error) {
      console.error('tm_certification session form script failed:', e);
    }
  }
})();
");

echo $OUTPUT->footer();
