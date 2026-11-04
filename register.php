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
 * Starts registering a Paradiso tool on Moodle versions before 3.10.
 *
 * Does what mod_lti's startltiadvregistration.php does on newer versions:
 * sends the admin to the registration link with this site's platform
 * configuration address and a one-time registration token.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_plmslti\local\registration;

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$link = required_param('url', PARAM_URL);
$url = new moodle_url($link, [
    'openid_configuration' => (new moodle_url('/local/plmslti/openid-configuration.php'))->out(false),
    'registration_token' => registration::new_token(),
]);
redirect($url);
