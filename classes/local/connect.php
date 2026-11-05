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

/**
 * The "Connect with Paradiso" round trip.
 *
 * The admin is sent to Paradiso with a one-time state value. Paradiso posts
 * back registration links, the address its tools live on and the state. The
 * links are queued in the session and registered one at a time on the
 * Connect page.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connect {
    /** @var string Paradiso's shared sign-in address, used when the admin sets none. */
    public const DEFAULT_SIGNIN_URL = 'https://app.paradisosolutions.com';

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
        $url = trim((string) get_config('local_plmslti', 'signinurl'));
        return rtrim($url !== '' ? $url : self::DEFAULT_SIGNIN_URL, '/');
    }

    /**
     * Where the plugin's own Connect page lives, which Paradiso sends the admin back to.
     *
     * @return \moodle_url
     */
    public static function return_url(): \moodle_url {
        return new \moodle_url('/local/plmslti/index.php');
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
     * Creates a one-time state value and remembers it in the session.
     *
     * @return string
     */
    public static function new_state(): string {
        global $SESSION;
        $value = random_string(32);
        $SESSION->local_plmslti_state = ['value' => $value, 'time' => time()];
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
        $stored = $SESSION->local_plmslti_state ?? null;
        unset($SESSION->local_plmslti_state);
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
        return clean_param($url, PARAM_URL) === $url && strpos($url, 'https://') === 0;
    }

    /**
     * Adds this plugin's release to a registration link as `plugin_version`.
     *
     * Moodle keeps the link's own query when it forwards the admin to
     * Paradiso, so Paradiso can record which plugin version registered each
     * tool. Support uses it to tell what a site runs.
     *
     * @param string $link Registration link from Paradiso.
     * @return string
     */
    public static function with_plugin_version(string $link): string {
        $release = \core_plugin_manager::instance()->get_plugin_info('local_plmslti')->release ?? '';
        if ($release === '') {
            return $link;
        }
        return (new \moodle_url($link, ['plugin_version' => $release]))->out(false);
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
     * @return string[] Product => registration link.
     */
    public static function valid_links(array $links): array {
        $queue = [];
        foreach (self::PRODUCTS as $product) {
            $link = (string) ($links[$product] ?? '');
            if ($link !== '' && self::is_valid_link($link)) {
                $queue[$product] = $link;
            }
        }
        return $queue;
    }

    /**
     * Drops the products this site already has a tool for, so Connect again
     * only adds what is new instead of a second copy of each tool.
     *
     * Reads tools from the saved workspace address, so save it first.
     *
     * @param string[] $links Product => registration link.
     * @return string[] Product => registration link.
     */
    public static function without_connected(array $links): array {
        $kinds = array_map([tool_setup::class, 'kind_of'], tool_setup::find_tools());
        $product = ['pat' => tool_setup::KIND_PAT, 'par' => tool_setup::KIND_ROLEPLAY];
        return array_filter($links, fn($key) => !in_array($product[$key] ?? null, $kinds, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Stores the links still to register.
     *
     * @param string[] $links
     */
    public static function queue_links(array $links): void {
        global $SESSION;
        $SESSION->local_plmslti_queue = array_values($links);
    }

    /**
     * Takes the next link to register off the queue.
     *
     * @return string|null Null when the queue is empty.
     */
    public static function next_link(): ?string {
        global $SESSION;
        $queue = $SESSION->local_plmslti_queue ?? [];
        $next = array_shift($queue);
        if ($queue) {
            $SESSION->local_plmslti_queue = $queue;
        } else {
            unset($SESSION->local_plmslti_queue);
        }
        return $next;
    }
}
