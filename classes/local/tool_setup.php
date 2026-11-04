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

namespace local_plmslti\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * Finds and activates the LTI tool types a Paradiso workspace registered.
 *
 * Dynamic Registration leaves a new tool pending and hidden from the
 * activity chooser. This class finishes the job so teachers can add the
 * tools without an admin editing each one by hand.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_setup {
    /** @var string Paradiso Presentations (PAT). */
    public const KIND_PAT = 'pat';

    /** @var string Paradiso Roleplay. */
    public const KIND_ROLEPLAY = 'roleplay';

    /**
     * Which Paradiso tool a type is, read from the redirect path Paradiso
     * registers with.
     *
     * @param \stdClass $type Row from lti_types.
     * @return string|null One of the KIND_ constants, or null if it is not ours.
     */
    public static function kind_of(\stdClass $type): ?string {
        $path = parse_url($type->baseurl ?? '', PHP_URL_PATH);
        if ($path === '/lti/pat/callback') {
            return self::KIND_PAT;
        }
        if ($path === '/lti/callback') {
            return self::KIND_ROLEPLAY;
        }
        return null;
    }

    /**
     * Whether a tool is switched on and in the activity chooser.
     *
     * @param \stdClass $type Row from lti_types.
     * @return bool
     */
    public static function is_active(\stdClass $type): bool {
        return $type->state == LTI_TOOL_STATE_CONFIGURED && $type->coursevisible == LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
    }

    /**
     * Domain of the configured Paradiso workspace, normalised the way mod_lti
     * stores tool domains.
     *
     * @return string|null Null when the workspace address is not set.
     */
    public static function workspace_domain(): ?string {
        $url = get_config('local_plmslti', 'workspaceurl');
        if (empty($url)) {
            return null;
        }
        return lti_get_domain_from_url($url) ?: null;
    }

    /**
     * LTI 1.3 tool types registered from the configured workspace.
     *
     * @return \stdClass[] lti_types rows keyed by id.
     */
    public static function find_tools(): array {
        global $DB;
        $domain = self::workspace_domain();
        if ($domain === null) {
            return [];
        }
        $types = $DB->get_records('lti_types', ['tooldomain' => $domain, 'ltiversion' => LTI_VERSION_1P3], 'id');
        return array_filter($types, fn($type) => self::kind_of($type) !== null);
    }

    /**
     * Whether the External tool activity is switched on for the site.
     *
     * When it is off, teachers cannot add any Paradiso activity, and Moodle
     * refuses to create one even from a direct link.
     *
     * @return bool
     */
    public static function lti_enabled(): bool {
        global $DB;
        return (bool) $DB->get_field('modules', 'visible', ['name' => 'lti']);
    }

    /**
     * Switch the External tool activity on, the way Site administration >
     * Manage activities does.
     *
     * @return bool True when it was off and is now on.
     */
    public static function enable_lti(): bool {
        global $CFG, $DB;
        if (self::lti_enabled()) {
            return false;
        }
        if (method_exists(\core\plugininfo\mod::class, 'enable_plugin')) {
            \core\plugininfo\mod::enable_plugin('lti', 1);
        } else {
            // Moodle 3.9 to 3.11: the same steps as admin/modules.php?show=lti.
            $moduleid = $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST);
            $DB->set_field('modules', 'visible', 1, ['id' => $moduleid]);
            $DB->set_field('course_modules', 'visible', 1, ['visibleold' => 1, 'module' => $moduleid]);
            increment_revision_number(
                'course',
                'cacherev',
                'id IN (SELECT DISTINCT course FROM {course_modules} WHERE visible = 1 AND module = ?)',
                [$moduleid]
            );
            \core_plugin_manager::reset_caches();
        }
        // Paradiso LMS keeps its own copy of the activity chooser and only
        // refreshes it from Site administration. Refresh it now, or External
        // tool stays hidden there until an admin happens to open that page.
        $plmstheme = $CFG->dirroot . '/theme/paradiso/lib.php';
        if (get_config('theme_paradiso', 'jsonmodules') && file_exists($plmstheme)) {
            require_once($plmstheme);
            if (function_exists('manage_mods')) {
                manage_mods();
            }
        }
        return true;
    }

    /**
     * Activate every Paradiso tool and put it in the activity chooser.
     *
     * Also switches on the External tool activity when the site has it off,
     * since the tools cannot be used without it.
     *
     * Idempotent: running it again rewrites the same values.
     *
     * @return int Number of tools updated.
     */
    public static function activate_all(): int {
        $tools = self::find_tools();
        if ($tools) {
            self::enable_lti();
        }
        $count = 0;
        foreach ($tools as $type) {
            $type->state = LTI_TOOL_STATE_CONFIGURED;
            $config = new \stdClass();
            $config->lti_coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
            $config->lti_description = get_string('tooldescription_' . self::kind_of($type), 'local_plmslti');
            $config->lti_sendname = LTI_SETTING_ALWAYS;
            $config->lti_sendemailaddr = LTI_SETTING_ALWAYS;
            // Inside a Moodle page Paradiso's sign-in cookie is a third-party
            // cookie, which Safari and private windows block. A new window
            // works in every browser.
            $config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
            if (empty($type->icon)) {
                $config->lti_icon = self::icon_url(self::kind_of($type));
            }
            lti_update_type($type, $config);
            $count++;
        }
        return $count;
    }

    /**
     * Address of the icon Paradiso publishes for a tool.
     *
     * Paradiso sends the same address when it registers a tool. Tools
     * registered before it did have no icon, so setup fills it in.
     *
     * @param string $kind One of the KIND_ constants.
     * @return string|null Null when the workspace address is not set.
     */
    public static function icon_url(string $kind): ?string {
        $base = rtrim((string) get_config('local_plmslti', 'workspaceurl'), '/');
        if ($base === '') {
            return null;
        }
        return $base . '/lti-icons/' . ($kind === self::KIND_PAT ? 'presentations' : 'roleplay') . '.svg';
    }

    /**
     * Delete every Paradiso tool registered from the workspace.
     *
     * @return int Number of tools deleted.
     */
    public static function remove_all(): int {
        $tools = self::find_tools();
        foreach ($tools as $type) {
            lti_delete_type($type->id);
        }
        return count($tools);
    }
}
