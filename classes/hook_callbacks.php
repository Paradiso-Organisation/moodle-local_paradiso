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
}
