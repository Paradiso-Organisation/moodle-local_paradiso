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
 * Install steps for local_paradiso.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Sends the admin to Connect Paradiso once the install has finished.
 *
 * The settings get their defaults here, so Moodle skips its "New settings"
 * step: both addresses are optional now that Connect with Paradiso fills in
 * the one that matters. The flag is read and cleared by
 * hook_callbacks::before_http_headers.
 *
 * @return bool
 */
function xmldb_local_paradiso_install() {
    set_config('signinurl', '', 'local_paradiso');
    set_config('workspaceurl', '', 'local_paradiso');
    set_config('removeonuninstall', 0, 'local_paradiso');
    set_config('showconnect', 1, 'local_paradiso');
    return true;
}
