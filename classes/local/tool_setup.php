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

namespace local_paradiso\local;

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
 * @package    local_paradiso
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
     * Domain of the configured Paradiso workspace, normalised the way mod_lti
     * stores tool domains.
     *
     * @return string|null Null when the workspace address is not set.
     */
    public static function workspace_domain(): ?string {
        $url = get_config('local_paradiso', 'workspaceurl');
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
     * Activate every Paradiso tool and put it in the activity chooser.
     *
     * Idempotent: running it again rewrites the same values.
     *
     * @return int Number of tools updated.
     */
    public static function activate_all(): int {
        $count = 0;
        foreach (self::find_tools() as $type) {
            $type->state = LTI_TOOL_STATE_CONFIGURED;
            $config = new \stdClass();
            $config->lti_coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
            $config->lti_description = get_string('tooldescription_' . self::kind_of($type), 'local_paradiso');
            $config->lti_sendname = LTI_SETTING_ALWAYS;
            $config->lti_sendemailaddr = LTI_SETTING_ALWAYS;
            // Inside a Moodle page Paradiso's sign-in cookie is a third-party
            // cookie, which Safari and private windows block. A new window
            // works in every browser.
            $config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
            lti_update_type($type, $config);
            $count++;
        }
        return $count;
    }
}
