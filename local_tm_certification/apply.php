<?php
/**
 * Instructor: apply to open a certification course.
 *
 * @package    local_tm_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/classes/permissions_manager.php');
require_once(__DIR__ . '/classes/session_manager.php');
require_once(__DIR__ . '/classes/catalog_manager.php');
require_once(__DIR__ . '/classes/application_manager.php');

use local_tm_certification\application_manager;
use local_tm_certification\catalog_manager;
use local_tm_certification\permissions_manager;
use local_tm_certification\session_manager;

require_login();
if (!permissions_manager::user_can_instruct() && !permissions_manager::user_can_manage()) {
    throw new moodle_exception('error_view_access_denied', 'local_tm_certification');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/tm_certification/apply.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('app_apply_title', 'local_tm_certification'));
$PAGE->set_heading(get_string('app_apply_title', 'local_tm_certification'));

$applylevels = application_manager::applyable_levels_for_user((int)$USER->id);
if (empty($applylevels) && !permissions_manager::user_can_manage()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_app_no_qualified_level', 'local_tm_certification'), 'warning');
    echo html_writer::link(new moodle_url('/local/tm_certification/index.php'), get_string('nav_dashboard', 'local_tm_certification'));
    echo $OUTPUT->footer();
    exit;
}
if (empty($applylevels)) {
    $applylevels = session_manager::level_options();
}

class local_tm_certification_apply_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $levels = $this->_customdata['levels'];
        $initiallevel = session_manager::LEVEL_BASIC;
        foreach ($levels as $k => $unused) {
            $initiallevel = $k;
            break;
        }

        $mform->addElement('select', 'certlevel', get_string('session_level', 'local_tm_certification'), $levels);
        $mform->setType('certlevel', PARAM_ALPHANUMEXT);
        $mform->addRule('certlevel', null, 'required', null, 'client');
        $mform->setDefault('certlevel', $initiallevel);

        $mform->addElement('date_time_selector', 'starttime', get_string('session_start', 'local_tm_certification'));
        $mform->addElement('date_time_selector', 'endtime', get_string('session_end', 'local_tm_certification'));

        $venueopts = catalog_manager::venue_options($initiallevel);
        $venueopts = [0 => get_string('session_venue_none', 'local_tm_certification')] + $venueopts;
        $mform->addElement('select', 'venueid', get_string('session_venue', 'local_tm_certification'), $venueopts);
        $mform->setType('venueid', PARAM_INT);
        $mform->addRule('venueid', null, 'required', null, 'client');

        $instructoropts = catalog_manager::instructor_options($initiallevel);
        if (empty($instructoropts)) {
            $instructoropts = [(int)$this->_customdata['userid'] => fullname($this->_customdata['user'])];
        }
        $mform->addElement('select', 'instructorid', get_string('session_instructor', 'local_tm_certification'), $instructoropts);
        $mform->setType('instructorid', PARAM_INT);
        $mform->setDefault('instructorid', (int)$this->_customdata['userid']);

        $mform->addElement('textarea', 'applicantnote', get_string('app_applicant_note', 'local_tm_certification'),
            'wrap="virtual" rows="3" cols="50"');
        $mform->setType('applicantnote', PARAM_TEXT);

        $mform->addElement('static', 'roster_hint', get_string('app_roster_hint_label', 'local_tm_certification'),
            get_string('app_roster_hint', 'local_tm_certification'));

        $this->add_action_buttons(true, get_string('app_submit', 'local_tm_certification'));
    }
}

$mform = new local_tm_certification_apply_form(null, [
    'levels' => $applylevels,
    'userid' => (int)$USER->id,
    'user' => $USER,
]);
$mform->set_data([
    'starttime' => time() + DAYSECS,
    'endtime' => time() + DAYSECS + 4 * HOURSECS,
    'instructorid' => (int)$USER->id,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/tm_certification/index.php'));
}

if ($data = $mform->get_data()) {
    try {
        $appid = application_manager::submit([
            'certlevel' => $data->certlevel,
            'starttime' => (int)$data->starttime,
            'endtime' => (int)$data->endtime,
            'venueid' => (int)$data->venueid,
            'instructorid' => (int)$data->instructorid,
            'applicantnote' => (string)($data->applicantnote ?? ''),
            'applicantid' => (int)$USER->id,
        ]);
        redirect(
            new moodle_url('/local/tm_certification/index.php'),
            get_string('app_submit_success', 'local_tm_certification', $appid),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification($e->getMessage(), 'error');
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('app_apply_intro', 'local_tm_certification'), ['class' => 'text-muted']);
$mform->display();

$venuesbylevel = catalog_manager::venue_options_by_level();
$instructorsbylevel = catalog_manager::instructor_options_by_level();
$venuesjson = json_encode($venuesbylevel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$instjson = json_encode($instructorsbylevel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$nonevenue = json_encode(get_string('session_venue_none', 'local_tm_certification'), JSON_UNESCAPED_UNICODE);
$noneins = json_encode(get_string('session_instructor_none', 'local_tm_certification'), JSON_UNESCAPED_UNICODE);

echo html_writer::script("
(function() {
  try {
  var venueMap = $venuesjson || {};
  var instructorMap = $instjson || {};
  var noneVenue = $nonevenue;
  var noneIns = $noneins;
  var levelSel = document.getElementById('id_certlevel');
  var venueSel = document.getElementById('id_venueid');
  var insSel = document.getElementById('id_instructorid');
  function fillSelect(sel, options, noneText) {
    if (!sel) return;
    var current = sel.value;
    sel.innerHTML = '';
    if (noneText !== null) {
      var opt = document.createElement('option');
      opt.value = '0';
      opt.textContent = noneText;
      sel.appendChild(opt);
    }
    Object.keys(options || {}).forEach(function(k) {
      var o = document.createElement('option');
      o.value = k;
      o.textContent = options[k];
      sel.appendChild(o);
    });
    var found = false;
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value === current) { found = true; break; }
    }
    if (found) { sel.value = current; }
    else if (noneText !== null) { sel.value = '0'; }
    else if (sel.options.length) { sel.selectedIndex = 0; }
  }
  function refreshByLevel() {
    if (!levelSel) return;
    var lv = levelSel.value;
    fillSelect(venueSel, venueMap[lv] || {}, noneVenue);
    fillSelect(insSel, instructorMap[lv] || {}, noneIns);
  }
  if (levelSel) { levelSel.addEventListener('change', refreshByLevel); }
  refreshByLevel();
  } catch (e) {
    if (window.console && console.error) { console.error('tm_certification apply form script failed:', e); }
  }
})();
");

echo $OUTPUT->footer();
