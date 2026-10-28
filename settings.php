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
 * Admin settings for local_paradiso.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_paradiso_category',
        get_string('pluginname', 'local_paradiso')
    ));

    $settings = new admin_settingpage('local_paradiso', get_string('settings', 'local_paradiso'));
    $settings->add(new admin_setting_configtext(
        'local_paradiso/workspaceurl',
        get_string('workspaceurl', 'local_paradiso'),
        get_string('workspaceurl_desc', 'local_paradiso'),
        '',
        PARAM_URL,
        60
    ));
    $ADMIN->add('local_paradiso_category', $settings);

    $ADMIN->add('local_paradiso_category', new admin_externalpage(
        'local_paradiso_connect',
        get_string('connect', 'local_paradiso'),
        new moodle_url('/local/paradiso/index.php'),
        'local/paradiso:manage'
    ));
}
