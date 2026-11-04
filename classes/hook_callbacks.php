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

namespace local_plmslti;

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_http_headers;

/**
 * Hook callbacks for local_plmslti.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var int Seconds after install during which the admin is sent to Connect Paradiso. */
    public const SHOWCONNECT_TTL = HOURSECS;

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
        self::load_contentitem_fix();
    }

    /**
     * Loads the deep linking fix when the page is the External tool activity form.
     */
    public static function load_contentitem_fix(): void {
        global $PAGE;
        if ($PAGE->pagetype === 'mod-lti-mod') {
            $PAGE->requires->js_call_amd('local_plmslti/contentitem_fix', 'init');
        }
    }

    /**
     * Opens Connect Paradiso once, right after the plugin is installed.
     *
     * Moodle ends an install on Site administration > Notifications, but some
     * sites replace that step (Paradiso LMS sends the admin to its dashboard),
     * so the first ordinary page a site admin opens afterwards does it. The
     * flag set by db/install.php is cleared first, so this happens a single
     * time, and it only counts within SHOWCONNECT_TTL of this plugin's install:
     * a stale flag must not fire after some other plugin's install.
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        self::show_connect_after_install();
    }

    /**
     * Sends a site admin to Connect Paradiso, once, after install.
     */
    public static function show_connect_after_install(): void {
        global $CFG, $PAGE;
        if (!is_siteadmin() || !self::can_redirect_from($PAGE) || !empty($CFG->upgraderunning)) {
            return;
        }
        $installedat = (int) get_config('local_plmslti', 'showconnect');
        if (!$installedat) {
            return;
        }
        unset_config('showconnect', 'local_plmslti');
        if (time() - $installedat <= self::SHOWCONNECT_TTL) {
            redirect(new \moodle_url('/local/plmslti/index.php', ['welcome' => 1]));
        }
    }

    /**
     * Whether a page is an ordinary one the admin can be moved away from.
     *
     * Leaves alone form posts, pages inside a frame or popup, the upgrade
     * screens, and the New settings page Moodle needs saved first.
     *
     * @param \moodle_page $page
     * @return bool
     */
    private static function can_redirect_from(\moodle_page $page): bool {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }
        if (in_array($page->pagelayout, ['popup', 'embedded', 'frametop', 'maintenance', 'redirect'], true)) {
            return false;
        }
        return $page->pagetype !== 'admin-upgradesettings';
    }
}
