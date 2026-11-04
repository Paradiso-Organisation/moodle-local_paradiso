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
 * Library callbacks for local_plmslti.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the Paradiso connection check to the system status report.
 *
 * @return \core\check\check[]
 */
function local_plmslti_status_checks(): array {
    return [new \local_plmslti\check\connection()];
}

/**
 * A single button, primary or secondary, on any supported Moodle version.
 *
 * Moodle 4.2 styles buttons with a type option; older versions only know a
 * primary flag and would print the type as an HTML attribute.
 *
 * @param moodle_url $url Where the button posts.
 * @param string $label
 * @param bool $primary
 * @return string HTML
 */
function local_plmslti_button(moodle_url $url, string $label, bool $primary): string {
    global $OUTPUT;
    if (defined('single_button::BUTTON_PRIMARY')) {
        $options = ['type' => $primary ? single_button::BUTTON_PRIMARY : single_button::BUTTON_SECONDARY];
    } else {
        $options = ['primary' => $primary];
    }
    return $OUTPUT->single_button($url, $label, 'post', $options);
}

/**
 * Opens Connect Paradiso after install, on Moodle before 4.4.
 *
 * Moodle 4.4 and later call hook_callbacks::before_http_headers instead and
 * skip this function, because db/hooks.php registers the hook.
 */
function local_plmslti_before_http_headers() {
    \local_plmslti\hook_callbacks::show_connect_after_install();
}

/**
 * Loads the deep linking fix, on Moodle before 4.4.
 *
 * Moodle 4.4 and later call hook_callbacks::before_footer_html_generation
 * instead and skip this function.
 *
 * @return string Nothing to add to the footer.
 */
function local_plmslti_before_footer() {
    \local_plmslti\hook_callbacks::load_contentitem_fix();
    return '';
}
