<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Connect Paradiso: register the Presentations and Roleplay tools through
 * LTI Dynamic Registration, then activate them.
 *
 * Two ways in. Connect with Paradiso sends the admin to Paradiso, which posts
 * the registration links back here (action=connected). Or the admin pastes a
 * registration link into the form. Either way each link is registered in an
 * iframe, one at a time, and setup finishes when the last one is done.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_plmslti\local\connect;
use local_plmslti\local\registration;
use local_plmslti\local\tool_setup;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

$action = optional_param('action', '', PARAM_ALPHA);

// Paradiso's reply is a cross-site POST, so the browser may leave the Moodle
// session cookie off. Post it again from this site first, the way mod_lti's
// contentitem_return.php does.
if ($action === 'connected' && empty($_POST['repost']) && !isloggedin()) {
    $PAGE->set_url(connect::return_url());
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('popup');
    header_remove('Set-Cookie');
    $output = $PAGE->get_renderer('mod_lti');
    echo $output->header();
    echo $output->render(new \mod_lti\output\repost_crosssite_page($_SERVER['REQUEST_URI'], $_POST));
    echo $output->footer();
    die();
}

admin_externalpage_setup('local_plmslti_connect');

$pageurl = connect::return_url();
$nexturl = new moodle_url($pageurl, ['action' => 'next', 'sesskey' => sesskey()]);
$activateurl = new moodle_url($pageurl, ['action' => 'activate', 'sesskey' => sesskey()]);

if ($action === 'start') {
    require_sesskey();
    redirect(connect::start_url());
}

if ($action === 'connected') {
    // No sesskey can come back from Paradiso. The one-time state is the check
    // that this reply answers a Connect click made in this session.
    if (!connect::check_state(optional_param('state', '', PARAM_ALPHANUM))) {
        redirect(
            $pageurl,
            get_string('connectexpired', 'local_plmslti'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    $toolbaseurl = optional_param('toolbaseurl', '', PARAM_URL);
    $links = connect::valid_links([
        'pat' => optional_param('link_pat', '', PARAM_URL),
        'par' => optional_param('link_par', '', PARAM_URL),
    ]);
    if (!connect::is_valid_tool_base($toolbaseurl) || !$links) {
        redirect(
            $pageurl,
            get_string('connectfailed', 'local_plmslti'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    set_config('workspaceurl', $toolbaseurl, 'local_plmslti');
    $links = connect::without_connected($links);
    if (!$links) {
        redirect(
            $pageurl,
            get_string('alreadyconnected', 'local_plmslti'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }
    connect::queue_links($links);
    redirect($nexturl);
}

if ($action === 'next') {
    require_sesskey();
    $link = connect::next_link();
    if ($link === null) {
        redirect($activateurl);
    }
    $registrationurl = $link;
} else if ($action === 'activate') {
    require_sesskey();
    $count = tool_setup::activate_all();
    redirect(
        $pageurl,
        get_string('toolsactivated', 'local_plmslti', $count),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    $form = new \local_plmslti\form\register_form($pageurl);
    $registrationurl = ($data = $form->get_data()) ? $data->registrationurl : null;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connect', 'local_plmslti'));

if ($registrationurl !== null) {
    // The start page (mod_lti's, or this plugin's before Moodle 3.10) issues a
    // registration token and forwards to Paradiso. Paradiso posts
    // org.imsglobal.lti.close to this window when it is done, which moves on
    // to the next queued link or finishes setup.
    $starturl = registration::start_url(connect::with_plugin_version($registrationurl));
    echo $OUTPUT->notification(get_string('registering', 'local_plmslti'), 'info');
    echo html_writer::tag('iframe', '', ['src' => $starturl->out(false), 'width' => '100%', 'height' => '320',
        'class' => 'border', 'title' => get_string('registrationframe', 'local_plmslti')]);
    $PAGE->requires->js_amd_inline("
        window.addEventListener('message', function(e) {
            if (e.data && e.data.subject === 'org.imsglobal.lti.close') {
                window.location.href = " . json_encode($nexturl->out(false)) . ";
            }
        });");
    echo $OUTPUT->footer();
    die();
}

if (optional_param('welcome', 0, PARAM_BOOL)) {
    echo $OUTPUT->notification(get_string('welcome', 'local_plmslti'), 'success');
}

$tools = [];
$needssetup = false;
foreach (tool_setup::find_tools() as $type) {
    $active = $type->state == LTI_TOOL_STATE_CONFIGURED;
    $inchooser = $type->coursevisible == LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
    // A tool from 0.1.0 is active but has no icon; Finish setup adds it.
    $needssetup = $needssetup || !tool_setup::is_active($type) || empty($type->icon);
    $tools[] = [
        'name' => format_string($type->name),
        'status' => get_string($active ? 'statusactive' : 'statuspending', 'local_plmslti'),
        'inchooser' => get_string($inchooser ? 'yes' : 'no', 'local_plmslti'),
    ];
}

if ($tools && !tool_setup::lti_enabled()) {
    $needssetup = true;
    echo $OUTPUT->notification(get_string('ltidisabled', 'local_plmslti'), 'warning', false);
}

if ($tools) {
    echo $OUTPUT->notification(
        get_string('connectedto', 'local_plmslti', s(get_config('local_plmslti', 'workspaceurl'))),
        'success',
        false
    );
}
echo $OUTPUT->render_from_template('local_plmslti/status', ['hastools' => !empty($tools), 'tools' => $tools]);

if ($needssetup) {
    echo local_plmslti_button($activateurl, get_string('activate', 'local_plmslti'), true);
    echo html_writer::tag('p', get_string('activate_help', 'local_plmslti'), ['class' => 'text-muted']);
}

// Once connected, Connect again is still needed after the workspace gains a
// product or to switch workspace, so it stays, as a secondary button.
echo html_writer::tag('p', get_string($tools ? 'reconnectintro' : 'connectwithintro', 'local_plmslti'));
echo local_plmslti_button(
    new moodle_url($pageurl, ['action' => 'start', 'sesskey' => sesskey()]),
    get_string($tools ? 'reconnect' : 'connectwith', 'local_plmslti'),
    !$tools
);

echo html_writer::start_tag('details', ['class' => 'mt-4']);
echo html_writer::tag('summary', get_string('manualconnect', 'local_plmslti'));
if (tool_setup::workspace_domain() === null) {
    echo $OUTPUT->notification(get_string('workspacemissing', 'local_plmslti'), 'warning');
} else {
    echo html_writer::tag('p', get_string('connectintro', 'local_plmslti'));
    $form->display();
}
echo html_writer::end_tag('details');

echo $OUTPUT->footer();
