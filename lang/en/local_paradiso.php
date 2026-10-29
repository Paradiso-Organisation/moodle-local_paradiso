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
$string['checkconnection'] = 'Paradiso connection';
$string['checkconnection_nourl'] = 'Paradiso is not connected yet. Open Connect Paradiso and click Connect with Paradiso.';
$string['checkconnection_ok'] = '{$a} Paradiso tool(s) are active and in the activity chooser.';
$string['checkconnection_pending'] = '{$a} Paradiso tool(s) are registered but not switched on. Open Connect Paradiso and click Finish setup.';
$string['colchooser'] = 'In activity chooser';
$string['colname'] = 'Tool';
$string['colstatus'] = 'Status';
$string['connect'] = 'Connect Paradiso';
$string['connectexpired'] = 'That connection request has expired or was already used. Click Connect with Paradiso to try again.';
$string['connectfailed'] = 'Paradiso did not send anything to set up. Click Connect with Paradiso to try again.';
$string['connectintro'] = 'In Paradiso, open Integrations, choose Moodle, and copy the link for Presentations or Roleplay. Paste it below and register one product at a time.';
$string['connectwith'] = 'Connect with Paradiso';
$string['connectwithintro'] = 'Opens Paradiso, where you can create a free account or log in with an existing one. Approve the connection there, and Moodle registers Presentations and Roleplay and switches them on.';
$string['manualconnect'] = 'Connect with a registration link instead';
$string['no'] = 'No';
$string['notools'] = 'No Paradiso tools are registered from this workspace yet.';
$string['paradiso:manage'] = 'Manage the Paradiso integration';
$string['pluginname'] = 'Paradiso integration';
$string['privacy:metadata'] = 'The Paradiso integration plugin does not store any personal data. Course launches use the External tool activity, which has its own privacy settings.';
$string['register'] = 'Register';
$string['registering'] = 'Registering with Paradiso. This page moves on by itself when it is done.';
$string['registrationframe'] = 'Paradiso registration';
$string['registrationurl'] = 'Registration link';
$string['registrationurl_help'] = 'The link from the Moodle card in Paradiso Integrations. It starts with https.';
$string['registrationurlhttps'] = 'The Registration link must be a valid URL that starts with https.';
$string['registrationurlrequired'] = 'The Registration link field is required.';
$string['removeonuninstall'] = 'Remove the tools when uninstalling';
$string['removeonuninstall_desc'] = 'When on, uninstalling this plugin also deletes the Paradiso tools it set up. Course activities that use them stop working. Leave it off to keep existing activities working without the plugin.';
$string['settings'] = 'Settings';
$string['signinurl'] = 'Paradiso sign-in address';
$string['signinurl_desc'] = 'Where Connect with Paradiso sends you to sign in. Leave it blank to use {$a}. Set it only if your workspace has its own address.';
$string['statusactive'] = 'Active';
$string['statuspending'] = 'Waiting for setup';
$string['tooldescription_pat'] = 'Add a Paradiso presentation to this course. Learner scores come back to the gradebook.';
$string['tooldescription_roleplay'] = 'Add a Paradiso roleplay where learners practise a conversation with an AI character. Scores come back to the gradebook.';
$string['toolsactivated'] = 'Setup finished. {$a} Paradiso tool(s) are active and in the activity chooser.';
$string['welcome'] = 'The Paradiso integration is installed. Connect it to your Paradiso workspace to finish.';
$string['workspacemissing'] = 'Set the Paradiso tool address in the plugin settings first, or use Connect with Paradiso above.';
$string['workspaceurl'] = 'Paradiso tool address';
$string['workspaceurl_desc'] = 'Filled in for you by Connect with Paradiso. If you register by hand, copy it from the Moodle card under Integrations in Paradiso.';
$string['yes'] = 'Yes';
