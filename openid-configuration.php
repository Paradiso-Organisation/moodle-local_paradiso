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
 * This site's LTI platform configuration, for Moodle versions before 3.10.
 *
 * Paradiso reads it while it registers a tool started from register.php.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- Public by design, like mod_lti's openid-configuration.php.
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');

$token = optional_param('registration_token', '', PARAM_ALPHANUM);

@header('Content-Type: application/json; charset=utf-8');
echo json_encode(\local_plmslti\local\registration::platform_configuration($token), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
