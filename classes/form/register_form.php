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

namespace local_paradiso\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Takes a Paradiso registration link and starts Dynamic Registration with it.
 *
 * @package    local_paradiso
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class register_form extends \moodleform {
    /**
     * Form fields.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'registrationurl', get_string('registrationurl', 'local_paradiso'), ['size' => 80]);
        $mform->setType('registrationurl', PARAM_URL);
        $mform->addHelpButton('registrationurl', 'registrationurl', 'local_paradiso');
        $mform->addRule('registrationurl', get_string('registrationurlrequired', 'local_paradiso'), 'required', null, 'client');
        $this->add_action_buttons(false, get_string('register', 'local_paradiso'));
    }

    /**
     * Only https links: Moodle sends its registration token to this address.
     *
     * @param array $data Submitted values.
     * @param array $files Uploaded files.
     * @return array Errors keyed by field.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!preg_match('#^https://#i', $data['registrationurl'] ?? '')) {
            $errors['registrationurl'] = get_string('registrationurlhttps', 'local_paradiso');
        }
        return $errors;
    }
}
