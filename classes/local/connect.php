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
 * The "Connect with Paradiso" round trip.
 *
 * The admin is sent to Paradiso with a one-time state value. Paradiso posts
 * back registration links, the address its tools live on and the state. The
 * links are queued in the session and registered one at a time on the
 * Connect page.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connect {
    /** @var string Paradiso's shared sign-in address, used when the admin sets none. */
    public const DEFAULT_SIGNIN_URL = 'https://app.paradiso.ai';

    /** @var int Seconds a state value stays valid. */
    public const STATE_TTL = 3600;

    /** @var string[] Products Paradiso may send a link for, in registration order. */
    public const PRODUCTS = ['pat', 'par'];

    /**
     * The Paradiso address the admin signs in at.
     *
     * @return string Without a trailing slash.
     */
    public static function signin_url(): string {
        $url = trim((string) get_config('local_paradiso', 'signinurl'));
        return rtrim($url !== '' ? $url : self::DEFAULT_SIGNIN_URL, '/');
    }

    /**
     * Where the plugin's own Connect page lives, which Paradiso sends the admin back to.
     *
     * @return \moodle_url
     */
    public static function return_url(): \moodle_url {
        return new \moodle_url('/local/paradiso/index.php');
    }

    /**
     * Starts a connection: stores a fresh state and returns the Paradiso URL to send the admin to.
     *
     * @return \moodle_url
     */
    public static function start_url(): \moodle_url {
        return new \moodle_url(self::signin_url() . '/connect/moodle', [
            'return' => self::return_url()->out(false),
            'state' => self::new_state(),
        ]);
    }

    /**
     * Paradiso's sign-up page for admins without an account.
     *
     * @return \moodle_url
     */
    public static function start_free_url(): \moodle_url {
        return new \moodle_url(self::signin_url() . '/connect/moodle/start-free');
    }

    /**
     * Creates a one-time state value and remembers it in the session.
     *
     * @return string
     */
    public static function new_state(): string {
        global $SESSION;
        $value = random_string(32);
        $SESSION->local_paradiso_state = ['value' => $value, 'time' => time()];
        return $value;
    }

    /**
     * Checks a returned state against the session. A value works once.
     *
     * @param string $value State posted back by Paradiso.
     * @return bool
     */
    public static function check_state(string $value): bool {
        global $SESSION;
        $stored = $SESSION->local_paradiso_state ?? null;
        unset($SESSION->local_paradiso_state);
        if (!is_array($stored) || $value === '') {
            return false;
        }
        return hash_equals($stored['value'], $value) && time() - $stored['time'] <= self::STATE_TTL;
    }

    /**
     * Whether a URL is fit to hand to Moodle's registration start page.
     *
     * @param string $url
     * @return bool
     */
    public static function is_valid_link(string $url): bool {
        return clean_param($url, PARAM_URL) === $url && str_starts_with($url, 'https://');
    }

    /**
     * Whether a URL is fit to store as the tool address.
     *
     * @param string $url
     * @return bool
     */
    public static function is_valid_tool_base(string $url): bool {
        return clean_param($url, PARAM_URL) === $url && preg_match('#^https?://#', $url) === 1;
    }

    /**
     * Keeps the valid links from Paradiso's reply, in registration order.
     *
     * @param array $links Product => registration link.
     * @return string[]
     */
    public static function valid_links(array $links): array {
        $queue = [];
        foreach (self::PRODUCTS as $product) {
            $link = (string) ($links[$product] ?? '');
            if ($link !== '' && self::is_valid_link($link)) {
                $queue[] = $link;
            }
        }
        return $queue;
    }

    /**
     * Stores the links still to register.
     *
     * @param string[] $links
     */
    public static function queue_links(array $links): void {
        global $SESSION;
        $SESSION->local_paradiso_queue = array_values($links);
    }

    /**
     * Takes the next link to register off the queue.
     *
     * @return string|null Null when the queue is empty.
     */
    public static function next_link(): ?string {
        global $SESSION;
        $queue = $SESSION->local_paradiso_queue ?? [];
        $next = array_shift($queue);
        if ($queue) {
            $SESSION->local_paradiso_queue = $queue;
        } else {
            unset($SESSION->local_paradiso_queue);
        }
        return $next;
    }
}
