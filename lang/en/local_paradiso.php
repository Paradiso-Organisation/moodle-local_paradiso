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
 * English strings for local_paradiso.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activate'] = 'Finish setup';
$string['activate_help'] = 'Turns on every Paradiso tool registered from your workspace, adds it to the activity chooser and sets it to open in a new window. Safe to run more than once.';
$string['colchooser'] = 'In activity chooser';
$string['colname'] = 'Tool';
$string['checkconnection'] = 'Paradiso connection';
$string['checkconnection_nourl'] = 'The Paradiso workspace address is not set.';
$string['checkconnection_ok'] = '{$a} Paradiso tool(s) are active and in the activity chooser.';
$string['checkconnection_pending'] = '{$a} Paradiso tool(s) are registered but not switched on. Open Connect Paradiso and click Finish setup.';
$string['colstatus'] = 'Status';
$string['connect'] = 'Connect Paradiso';
$string['connectintro'] = 'In Paradiso, copy the registration link for Presentations or Roleplay and paste it below. Each link works for 10 minutes. Register one tool at a time.';
$string['no'] = 'No';
$string['notools'] = 'No Paradiso tools are registered from this workspace yet.';
$string['paradiso:manage'] = 'Manage the Paradiso integration';
$string['pluginname'] = 'Paradiso integration';
$string['privacy:metadata'] = 'The Paradiso integration plugin does not store any personal data. Course launches use the External tool activity, which has its own privacy settings.';
$string['register'] = 'Register';
$string['registering'] = 'Finish the registration below. This page updates on its own when it is done.';
$string['registrationurl'] = 'Registration link';
$string['registrationurl_help'] = 'The link Paradiso shows for connecting an LMS. It starts with https and expires after 10 minutes.';
$string['registrationurlhttps'] = 'The Registration link must be a valid URL that starts with https.';
$string['registrationurlrequired'] = 'The Registration link field is required.';
$string['removeonuninstall'] = 'Remove the tools when uninstalling';
$string['removeonuninstall_desc'] = 'When on, uninstalling this plugin also deletes the Paradiso tools it set up. Course activities that use them stop working. Leave it off to keep existing activities working without the plugin.';
$string['settings'] = 'Settings';
$string['statusactive'] = 'Active';
$string['statuspending'] = 'Waiting for setup';
$string['tooldescription_pat'] = 'Add a Paradiso presentation to this course. Learner scores come back to the gradebook.';
$string['tooldescription_roleplay'] = 'Add a Paradiso roleplay where learners practise a conversation with an AI character. Scores come back to the gradebook.';
$string['toolsactivated'] = 'Setup finished. {$a} Paradiso tool(s) are active and in the activity chooser.';
$string['workspacemissing'] = 'Set your Paradiso workspace address in the plugin settings first.';
$string['workspaceurl'] = 'Paradiso workspace address';
$string['workspaceurl_desc'] = 'The address you use to sign in to Paradiso, for example https://yourcompany.paradiso.ai.';
$string['yes'] = 'Yes';
