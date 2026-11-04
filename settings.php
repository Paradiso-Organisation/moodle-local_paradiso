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
 * Admin settings for local_plmslti.
 *
 * @package    local_plmslti
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// The Connect page needs only local/plmslti:manage, which the Manager role
// has, so the menu is added for those users too. The settings page itself
// keeps Moodle's usual site-config requirement.
if ($hassiteconfig || has_capability('local/plmslti:manage', context_system::instance())) {
    $ADMIN->add('localplugins', new admin_category(
        'local_plmslti_category',
        get_string('pluginname', 'local_plmslti')
    ));

    $settings = new admin_settingpage('local_plmslti', get_string('settings', 'local_plmslti'));
    $settings->add(new admin_setting_configtext(
        'local_plmslti/signinurl',
        get_string('signinurl', 'local_plmslti'),
        get_string('signinurl_desc', 'local_plmslti', \local_plmslti\local\connect::DEFAULT_SIGNIN_URL),
        '',
        PARAM_URL,
        60
    ));
    $settings->add(new admin_setting_configtext(
        'local_plmslti/workspaceurl',
        get_string('workspaceurl', 'local_plmslti'),
        get_string('workspaceurl_desc', 'local_plmslti'),
        '',
        PARAM_URL,
        60
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_plmslti/removeonuninstall',
        get_string('removeonuninstall', 'local_plmslti'),
        get_string('removeonuninstall_desc', 'local_plmslti'),
        0
    ));
    $ADMIN->add('local_plmslti_category', $settings);

    $ADMIN->add('local_plmslti_category', new admin_externalpage(
        'local_plmslti_connect',
        get_string('connect', 'local_plmslti'),
        new moodle_url('/local/plmslti/index.php'),
        'local/plmslti:manage'
    ));
}
