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
 * Uninstall steps for local_paradiso.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Deletes the Paradiso tools when the admin chose to, and keeps them otherwise.
 *
 * Uninstall cannot ask a question, so the choice is the removeonuninstall
 * setting. Keeping the tools is the default: they are ordinary External tool
 * types and course activities keep working without this plugin.
 *
 * @return bool
 */
function xmldb_local_paradiso_uninstall() {
    if (get_config('local_paradiso', 'removeonuninstall')) {
        \local_paradiso\local\tool_setup::remove_all();
    }
    return true;
}
