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

namespace local_paradiso\check;

use core\check\check;
use core\check\result;
use local_paradiso\local\tool_setup;

/**
 * Status check: is Paradiso connected and are its tools switched on?
 *
 * Shown on Site administration > Reports > System status.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connection extends check {
    /**
     * Name shown in the status report.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('checkconnection', 'local_paradiso');
    }

    /**
     * Link to the page that fixes what this check reports.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/local/paradiso/index.php'),
            get_string('connect', 'local_paradiso')
        );
    }

    /**
     * Works out the connection status.
     *
     * @return result
     */
    public function get_result(): result {
        if (tool_setup::workspace_domain() === null) {
            return new result(result::NA, get_string('checkconnection_nourl', 'local_paradiso'));
        }
        $tools = tool_setup::find_tools();
        if (!$tools) {
            return new result(result::WARNING, get_string('notools', 'local_paradiso'));
        }
        $pending = array_filter($tools, fn($type) => !tool_setup::is_active($type));
        if ($pending) {
            return new result(result::WARNING, get_string('checkconnection_pending', 'local_paradiso', count($pending)));
        }
        return new result(result::OK, get_string('checkconnection_ok', 'local_paradiso', count($tools)));
    }
}
