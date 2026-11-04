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
 * LTI registration endpoint, for Moodle versions before 3.10.
 *
 * Paradiso posts its tool's registration here with the one-time token from
 * register.php as a Bearer token. The tool is added pending, and the reply
 * carries the client id and deployment id Paradiso needs for launches.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- Authorised by the registration token, like mod_lti's openid-registration.php.
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

use local_plmslti\local\registration;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/lti/OAuth.php');

/**
 * Sends a JSON reply and stops.
 *
 * @param int $code HTTP status.
 * @param mixed $body Value to encode.
 */
function local_plmslti_registration_reply(int $code, $body): void {
    http_response_code($code);
    @header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    die();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    local_plmslti_registration_reply(405, ['error' => 'unsupported_method']);
}
$authheader = \moodle\mod\lti\OAuthUtil::get_headers()['Authorization'] ?? '';
if (strpos($authheader, 'Bearer ') !== 0 || !registration::use_token(trim(substr($authheader, 7)))) {
    local_plmslti_registration_reply(401, ['error' => 'invalid_registration_token']);
}
$request = json_decode(file_get_contents('php://input'), true);
if (!is_array($request)) {
    local_plmslti_registration_reply(400, ['error' => 'invalid_client_metadata']);
}
try {
    local_plmslti_registration_reply(200, registration::register($request));
} catch (invalid_parameter_exception $e) {
    local_plmslti_registration_reply(400, ['error' => 'invalid_client_metadata', 'error_description' => $e->debuginfo]);
}
