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

namespace local_paradiso;

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_http_headers;

/**
 * Hook callbacks for local_paradiso.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Loads the deep linking fix on the External tool activity form.
     *
     * On Moodle 5.3 the form's content item handler throws while it fills in
     * the activity description, so every field after it is left empty. The
     * activity then saves without the custom parameter that tells Paradiso
     * which presentation or roleplay to launch.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;
        if ($PAGE->pagetype === 'mod-lti-mod') {
            $PAGE->requires->js_call_amd('local_paradiso/contentitem_fix', 'init');
        }
    }

    /**
     * Opens Connect Paradiso once, right after the plugin is installed.
     *
     * Moodle ends an install on Site administration > Notifications, and only
     * prints that page once every pending upgrade and new setting is done, so
     * reaching its headers means the install has finished. The flag set by
     * db/install.php is cleared first, so this happens a single time.
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE;
        if ($PAGE->pagetype !== 'admin-index' || !get_config('local_paradiso', 'showconnect') || !is_siteadmin()) {
            return;
        }
        unset_config('showconnect', 'local_paradiso');
        redirect(new \moodle_url('/local/paradiso/index.php', ['welcome' => 1]));
    }
}
