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
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_paradiso\local\tool_setup;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_paradiso_connect');

$pageurl = new moodle_url('/local/paradiso/index.php');
$activateurl = new moodle_url($pageurl, ['action' => 'activate', 'sesskey' => sesskey()]);

if (optional_param('action', '', PARAM_ALPHA) === 'activate') {
    require_sesskey();
    $count = tool_setup::activate_all();
    redirect(
        $pageurl,
        get_string('toolsactivated', 'local_paradiso', $count),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$form = new \local_paradiso\form\register_form($pageurl);
$registrationurl = ($data = $form->get_data()) ? $data->registrationurl : null;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connect', 'local_paradiso'));

if (tool_setup::workspace_domain() === null) {
    echo $OUTPUT->notification(get_string('workspacemissing', 'local_paradiso'), 'warning');
    echo $OUTPUT->footer();
    die();
}

if ($registrationurl !== null) {
    // The mod_lti start page signs a registration token and forwards to
    // Paradiso. Paradiso posts org.imsglobal.lti.close to this window when it
    // is done, and setup finishes straight away.
    $starturl = new moodle_url(
        '/mod/lti/startltiadvregistration.php',
        ['url' => $registrationurl, 'sesskey' => sesskey()]
    );
    echo $OUTPUT->notification(get_string('registering', 'local_paradiso'), 'info');
    echo html_writer::tag('iframe', '', ['src' => $starturl->out(false), 'width' => '100%', 'height' => '320',
        'class' => 'border']);
    $PAGE->requires->js_amd_inline("
        window.addEventListener('message', function(e) {
            if (e.data && e.data.subject === 'org.imsglobal.lti.close') {
                window.location.href = " . json_encode($activateurl->out(false)) . ";
            }
        });");
    echo $OUTPUT->footer();
    die();
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

echo html_writer::tag('p', get_string('connectintro', 'local_paradiso'));
$form->display();

if (!empty($tools)) {
    echo $OUTPUT->single_button($activateurl, get_string('activate', 'local_paradiso'));
    echo html_writer::tag('p', get_string('activate_help', 'local_paradiso'), ['class' => 'text-muted']);
}

echo $OUTPUT->footer();
