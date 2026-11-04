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

use core\hook\output\before_http_headers;

/**
 * Tests for the post-install redirect to Connect Paradiso.
 *
 * @package    local_plmslti
 * @category   test
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_plmslti\hook_callbacks
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Runs the hook as if the given page were printing its headers.
     *
     * @param string $pagetype
     * @param string $layout
     * @return bool Whether the hook redirected.
     */
    private function run_hook(string $pagetype, string $layout = 'admin'): bool {
        global $PAGE;
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/admin/index.php');
        $PAGE->set_pagetype($pagetype);
        $PAGE->set_pagelayout($layout);
        try {
            hook_callbacks::before_http_headers(new before_http_headers($PAGE->get_renderer('core')));
        } catch (\moodle_exception $e) {
            // A redirect() refuses to run from the CLI, which is how a test sees it.
            if ($e->errorcode === 'redirecterrordetected') {
                return true;
            }
            throw $e;
        }
        return false;
    }

    public function test_install_sends_admin_to_connect_once(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('showconnect', time(), 'local_plmslti');

        $this->assertTrue($this->run_hook('admin-index'));
        $this->assertFalse(get_config('local_plmslti', 'showconnect'));
        $this->assertFalse($this->run_hook('admin-index'), 'Second visit stays put.');
    }

    public function test_first_ordinary_page_redirects_when_notifications_is_skipped(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('showconnect', time(), 'local_plmslti');

        $this->assertFalse($this->run_hook('admin-upgradesettings'), 'New settings must be saved first.');
        $this->assertFalse($this->run_hook('mod-lti-launch', 'popup'), 'Popups and frames stay put.');
        $this->assertFalse($this->run_hook('admin-index', 'maintenance'), 'Upgrade screens stay put.');
        $this->assertTrue($this->run_hook('local-custom-dashboard-index', 'standard'));
        $this->assertFalse(get_config('local_plmslti', 'showconnect'));
    }

    public function test_non_admin_is_not_redirected(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $installedat = time();
        set_config('showconnect', $installedat, 'local_plmslti');

        $this->assertFalse($this->run_hook('admin-index'));
        $this->assertEquals($installedat, get_config('local_plmslti', 'showconnect'), 'The flag waits for an admin.');
    }

    public function test_stale_flag_does_not_follow_another_plugin_install(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('showconnect', time() - hook_callbacks::SHOWCONNECT_TTL - 1, 'local_plmslti');

        $this->assertFalse($this->run_hook('admin-index'));
        $this->assertFalse(get_config('local_plmslti', 'showconnect'), 'A stale flag is cleared quietly.');
    }

    public function test_command_line_install_sets_no_flag(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/plmslti/db/install.php');
        unset_config('showconnect', 'local_plmslti');

        xmldb_local_plmslti_install();
        $this->assertFalse(get_config('local_plmslti', 'showconnect'));
    }
}
