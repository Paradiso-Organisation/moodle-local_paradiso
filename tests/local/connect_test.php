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

/**
 * Tests for the Connect with Paradiso round trip.
 *
 * @package    local_paradiso
 * @category   test
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_paradiso\local\connect
 */
final class connect_test extends \advanced_testcase {
    public function test_start_url_uses_default_or_configured_signin_address(): void {
        $this->resetAfterTest();
        $url = connect::start_url();
        $this->assertStringStartsWith(connect::DEFAULT_SIGNIN_URL . '/connect/moodle?', $url->out(false));
        $this->assertSame(connect::return_url()->out(false), $url->get_param('return'));
        $this->assertNotEmpty($url->get_param('state'));

        set_config('signinurl', 'https://acme.example.com/', 'local_paradiso');
        $this->assertStringStartsWith('https://acme.example.com/connect/moodle?', connect::start_url()->out(false));
    }

    public function test_state_works_once(): void {
        $state = connect::new_state();
        $this->assertTrue(connect::check_state($state));
        $this->assertFalse(connect::check_state($state));
    }

    public function test_wrong_or_missing_state_is_rejected(): void {
        connect::new_state();
        $this->assertFalse(connect::check_state('not-the-state'));
        $this->assertFalse(connect::check_state(''));
    }

    public function test_old_state_is_rejected(): void {
        global $SESSION;
        $state = connect::new_state();
        $SESSION->local_paradiso_state['time'] -= connect::STATE_TTL + 1;
        $this->assertFalse(connect::check_state($state));
    }

    public function test_starting_again_replaces_the_state(): void {
        $first = connect::new_state();
        $second = connect::new_state();
        $this->assertFalse(connect::check_state($first));
        $this->assertFalse(connect::check_state($second), 'A rejected check also clears the stored state.');
    }

    public function test_valid_links_keeps_https_links_in_product_order(): void {
        $links = connect::valid_links([
            'par' => 'https://api.example/public/lti/register?token=b',
            'pat' => 'https://api.example/public/pat-lti/register?token=a',
            'other' => 'https://api.example/x',
        ]);
        $this->assertSame([
            'https://api.example/public/pat-lti/register?token=a',
            'https://api.example/public/lti/register?token=b',
        ], $links);

        $this->assertSame([], connect::valid_links(['pat' => 'http://api.example/x', 'par' => 'javascript:alert(1)']));
        $this->assertSame([], connect::valid_links([]));
    }

    public function test_tool_base_must_be_a_web_address(): void {
        $this->assertTrue(connect::is_valid_tool_base('https://app.paradiso.example'));
        $this->assertTrue(connect::is_valid_tool_base('http://app.paradiso.local:3000'));
        $this->assertFalse(connect::is_valid_tool_base('ftp://app.paradiso.example'));
        $this->assertFalse(connect::is_valid_tool_base(''));
    }

    public function test_queue_hands_out_links_in_order_then_empties(): void {
        connect::queue_links(['https://a.example/1', 'https://a.example/2']);
        $this->assertSame('https://a.example/1', connect::next_link());
        $this->assertSame('https://a.example/2', connect::next_link());
        $this->assertNull(connect::next_link());
    }
}
