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

namespace local_plmslti\check;

use core\check\check;
use core\check\result;
use local_plmslti\local\tool_setup;

/**
 * Status check: is Paradiso connected and are its tools switched on?
 *
 * Shown on Site administration > Reports > System status.
 *
 * @package    local_plmslti
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
        return get_string('checkconnection', 'local_plmslti');
    }

    /**
     * Link to the page that fixes what this check reports.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/local/plmslti/index.php'),
            get_string('connect', 'local_plmslti')
        );
    }

    /**
     * Whether this server gets an answer from the Paradiso tool address.
     *
     * Any reply below 500 counts: a sign-in redirect still proves Paradiso is
     * up and reachable. Short timeouts keep the status page quick when it is not.
     *
     * @param string $url Paradiso tool address.
     * @return bool
     */
    protected function is_reachable(string $url): bool {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->head($url, ['CURLOPT_CONNECTTIMEOUT' => 3, 'CURLOPT_TIMEOUT' => 5]);
        $code = (int) ($curl->get_info()['http_code'] ?? 0);
        return !$curl->get_errno() && $code > 0 && $code < 500;
    }

    /**
     * Works out the connection status.
     *
     * @return result
     */
    public function get_result(): result {
        if (tool_setup::workspace_domain() === null) {
            return new result(result::NA, get_string('checkconnection_nourl', 'local_plmslti'));
        }
        $workspaceurl = (string) get_config('local_plmslti', 'workspaceurl');
        if (!$this->is_reachable($workspaceurl)) {
            return new result(result::ERROR, get_string('checkconnection_unreachable', 'local_plmslti', $workspaceurl));
        }
        $tools = tool_setup::find_tools();
        if (!$tools) {
            return new result(result::WARNING, get_string('notools', 'local_plmslti'));
        }
        if (!tool_setup::lti_enabled()) {
            return new result(result::WARNING, get_string('checkconnection_ltidisabled', 'local_plmslti'));
        }
        $pending = array_filter($tools, fn($type) => !tool_setup::is_active($type));
        if ($pending) {
            return new result(result::WARNING, get_string('checkconnection_pending', 'local_plmslti', count($pending)));
        }
        return new result(result::OK, get_string('checkconnection_ok', 'local_plmslti', count($tools)));
    }
}
