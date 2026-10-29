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
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_paradiso\local\connect;
use local_paradiso\local\tool_setup;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

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

admin_externalpage_setup('local_paradiso_connect');

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
            get_string('connectexpired', 'local_paradiso'),
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
            get_string('connectfailed', 'local_paradiso'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    set_config('workspaceurl', $toolbaseurl, 'local_paradiso');
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
        get_string('toolsactivated', 'local_paradiso', $count),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    $form = new \local_paradiso\form\register_form($pageurl);
    $registrationurl = ($data = $form->get_data()) ? $data->registrationurl : null;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connect', 'local_paradiso'));

if ($registrationurl !== null) {
    // The mod_lti start page signs a registration token and forwards to
    // Paradiso. Paradiso posts org.imsglobal.lti.close to this window when it
    // is done, which moves on to the next queued link or finishes setup.
    $starturl = new moodle_url(
        '/mod/lti/startltiadvregistration.php',
        ['url' => $registrationurl, 'sesskey' => sesskey()]
    );
    echo $OUTPUT->notification(get_string('registering', 'local_paradiso'), 'info');
    echo html_writer::tag('iframe', '', ['src' => $starturl->out(false), 'width' => '100%', 'height' => '320',
        'class' => 'border', 'title' => get_string('registrationframe', 'local_paradiso')]);
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
    echo $OUTPUT->notification(get_string('welcome', 'local_paradiso'), 'success');
}

$tools = [];
foreach (tool_setup::find_tools() as $type) {
    $active = $type->state == LTI_TOOL_STATE_CONFIGURED;
    $inchooser = $type->coursevisible == LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
    $tools[] = [
        'name' => format_string($type->name),
        'status' => get_string($active ? 'statusactive' : 'statuspending', 'local_paradiso'),
        'inchooser' => get_string($inchooser ? 'yes' : 'no', 'local_paradiso'),
    ];
}
echo $OUTPUT->render_from_template('local_paradiso/status', ['hastools' => !empty($tools), 'tools' => $tools]);

echo html_writer::tag('p', get_string('connectwithintro', 'local_paradiso'));
echo $OUTPUT->single_button(
    new moodle_url($pageurl, ['action' => 'start', 'sesskey' => sesskey()]),
    get_string('connectwith', 'local_paradiso'),
    'post',
    ['type' => single_button::BUTTON_PRIMARY]
);
echo html_writer::tag('p', get_string('startfree', 'local_paradiso', html_writer::link(
    connect::start_free_url(),
    get_string('startfreelink', 'local_paradiso'),
    ['target' => '_blank', 'rel' => 'noopener']
)), ['class' => 'mt-3']);

if (!empty($tools)) {
    echo $OUTPUT->single_button($activateurl, get_string('activate', 'local_paradiso'));
    echo html_writer::tag('p', get_string('activate_help', 'local_paradiso'), ['class' => 'text-muted']);
}

echo html_writer::start_tag('details', ['class' => 'mt-4']);
echo html_writer::tag('summary', get_string('manualconnect', 'local_paradiso'));
if (tool_setup::workspace_domain() === null) {
    echo $OUTPUT->notification(get_string('workspacemissing', 'local_paradiso'), 'warning');
} else {
    echo html_writer::tag('p', get_string('connectintro', 'local_paradiso'));
    $form->display();
}
echo html_writer::end_tag('details');

echo $OUTPUT->footer();
