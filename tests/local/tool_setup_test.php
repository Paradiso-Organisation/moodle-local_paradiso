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

use core\check\result;
use local_plmslti\check\connection;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * Tests for tool_setup and the connection status check.
 *
 * @package    local_plmslti
 * @category   test
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_plmslti\local\tool_setup
 * @covers     \local_plmslti\check\connection
 * @covers     \local_plmslti\local\connect
 */
final class tool_setup_test extends \advanced_testcase {
    /** @var string Workspace the tests register from. */
    private const WORKSPACE = 'https://acme.paradiso.example';

    /**
     * Creates a pending LTI 1.3 tool, the way Dynamic Registration leaves it.
     *
     * @param string $url Tool URL (the registered redirect URI).
     * @return int Tool type id.
     */
    private function add_tool(string $url): int {
        $type = (object) [
            'name' => 'Tool at ' . $url,
            'baseurl' => $url,
            'ltiversion' => LTI_VERSION_1P3,
            'state' => LTI_TOOL_STATE_PENDING,
            'coursevisible' => LTI_COURSEVISIBLE_PRECONFIGURED,
        ];
        $config = (object) ['lti_toolurl' => $url, 'lti_launchcontainer' => LTI_LAUNCH_CONTAINER_EMBED];
        return lti_add_type($type, $config);
    }

    public function test_kind_of(): void {
        $this->assertSame(tool_setup::KIND_PAT, tool_setup::kind_of((object) ['baseurl' => self::WORKSPACE . '/lti/pat/callback']));
        $roleplay = (object) ['baseurl' => self::WORKSPACE . '/lti/callback'];
        $this->assertSame(tool_setup::KIND_ROLEPLAY, tool_setup::kind_of($roleplay));
        $this->assertNull(tool_setup::kind_of((object) ['baseurl' => self::WORKSPACE . '/other']));
        $this->assertNull(tool_setup::kind_of((object) []));
    }

    public function test_find_tools_needs_workspace(): void {
        $this->resetAfterTest();
        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $this->assertSame([], tool_setup::find_tools());
    }

    public function test_find_tools_only_returns_paradiso_tools_from_the_workspace(): void {
        $this->resetAfterTest();
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $pat = $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $roleplay = $this->add_tool(self::WORKSPACE . '/lti/callback');
        $this->add_tool(self::WORKSPACE . '/something/else');
        $this->add_tool('https://other.example/lti/pat/callback');

        $this->assertEqualsCanonicalizing([$pat, $roleplay], array_keys(tool_setup::find_tools()));
    }

    public function test_activate_all(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $pat = $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $roleplay = $this->add_tool(self::WORKSPACE . '/lti/callback');

        $this->assertSame(2, tool_setup::activate_all());
        // Running it again changes nothing.
        $this->assertSame(2, tool_setup::activate_all());

        foreach ([$pat, $roleplay] as $id) {
            $type = $DB->get_record('lti_types', ['id' => $id]);
            $this->assertTrue(tool_setup::is_active($type));
            $config = lti_get_type_config($id);
            $this->assertEquals(LTI_LAUNCH_CONTAINER_WINDOW, $config['launchcontainer']);
            $this->assertEquals(LTI_SETTING_ALWAYS, $config['sendname']);
            $this->assertEquals(LTI_SETTING_ALWAYS, $config['sendemailaddr']);
        }
        $this->assertSame(
            get_string('tooldescription_pat', 'local_plmslti'),
            $DB->get_field('lti_types', 'description', ['id' => $pat])
        );
    }

    public function test_connect_again_skips_connected_products(): void {
        $this->resetAfterTest();
        $links = ['pat' => 'https://api.example/pat', 'par' => 'https://api.example/par'];
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $this->assertSame($links, connect::without_connected($links));

        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $this->assertSame(['par' => 'https://api.example/par'], connect::without_connected($links));

        $this->add_tool(self::WORKSPACE . '/lti/callback');
        $this->assertSame([], connect::without_connected($links));

        // A different workspace starts from scratch.
        set_config('workspaceurl', 'https://other.paradiso.example', 'local_plmslti');
        $this->assertSame($links, connect::without_connected($links));
    }

    public function test_remove_all(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $other = $this->add_tool('https://other.example/lti/pat/callback');

        $this->assertSame(1, tool_setup::remove_all());
        $this->assertSame([], tool_setup::find_tools());
        $this->assertTrue($DB->record_exists('lti_types', ['id' => $other]));
    }

    /**
     * The status check with its network probe replaced, so tests stay offline.
     *
     * @param bool $reachable What the probe reports.
     * @return connection
     */
    private function check(bool $reachable): connection {
        return new class ($reachable) extends connection {
            /** @var bool What is_reachable() reports. */
            private bool $reachable;

            /**
             * Constructor.
             *
             * @param bool $reachable
             */
            public function __construct(bool $reachable) {
                $this->reachable = $reachable;
            }

            /**
             * Reports the canned answer instead of probing.
             *
             * @param string $url
             * @return bool
             */
            protected function is_reachable(string $url): bool {
                return $this->reachable;
            }
        };
    }

    public function test_activate_all_sets_missing_icons_only(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $pat = $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $roleplay = $this->add_tool(self::WORKSPACE . '/lti/callback');
        $DB->set_field('lti_types', 'icon', 'https://cdn.example/own.png', ['id' => $roleplay]);

        tool_setup::activate_all();

        $this->assertSame(self::WORKSPACE . '/lti-icons/presentations.svg', $DB->get_field('lti_types', 'icon', ['id' => $pat]));
        $this->assertSame('https://cdn.example/own.png', $DB->get_field('lti_types', 'icon', ['id' => $roleplay]));
    }

    public function test_connection_check(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $check = $this->check(true);
        $this->assertSame(result::NA, $check->get_result()->get_status());

        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $this->assertSame(result::WARNING, $check->get_result()->get_status());

        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        $this->assertSame(result::WARNING, $check->get_result()->get_status());

        tool_setup::activate_all();
        $this->assertSame(result::OK, $check->get_result()->get_status());
    }

    public function test_activate_all_switches_on_external_tool(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $DB->set_field('modules', 'visible', 0, ['name' => 'lti']);
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');

        // No Paradiso tools yet: leave the site's choice alone.
        tool_setup::activate_all();
        $this->assertFalse(tool_setup::lti_enabled());

        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        tool_setup::activate_all();
        $this->assertTrue(tool_setup::lti_enabled());
        $this->assertFalse(tool_setup::enable_lti());
    }

    public function test_connection_check_warns_when_external_tool_is_off(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        tool_setup::activate_all();
        $DB->set_field('modules', 'visible', 0, ['name' => 'lti']);

        $result = $this->check(true)->get_result();
        $this->assertSame(result::WARNING, $result->get_status());
        $this->assertSame(get_string('checkconnection_ltidisabled', 'local_plmslti'), $result->get_summary());
    }

    public function test_connection_check_reports_unreachable_paradiso(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('workspaceurl', self::WORKSPACE, 'local_plmslti');
        $this->add_tool(self::WORKSPACE . '/lti/pat/callback');
        tool_setup::activate_all();

        $result = $this->check(false)->get_result();
        $this->assertSame(result::ERROR, $result->get_status());
        $this->assertStringContainsString(self::WORKSPACE, $result->get_summary());
    }
}
