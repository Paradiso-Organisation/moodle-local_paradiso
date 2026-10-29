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
 * Keeps deep linking working on the External tool activity form.
 *
 * mod_lti fills the form from window.processContentItemReturnData. On Moodle
 * 5.3 that handler throws on the activity description (tinyMCE.get() returns
 * null), and every field after it, custom parameters included, stays empty.
 * The description field is never reset when it is missing from the data, so
 * we hand mod_lti the data without it and set the description ourselves.
 *
 * @module     local_paradiso/contentitem_fix
 * @copyright  2026 Paradiso Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Puts the returned description into the editor, or its textarea if there is no editor.
 *
 * @param {Object} intro The introeditor value from the content item, {text, format}.
 */
const setDescription = (intro) => {
    if (!intro || !intro.text) {
        return;
    }
    const editor = (typeof window.tinyMCE !== 'undefined') ? window.tinyMCE.get('id_introeditor') : null;
    if (editor) {
        editor.setContent(intro.text);
    }
    const textarea = document.getElementById('id_introeditor');
    if (textarea) {
        textarea.value = intro.text;
    }
};

/**
 * Wraps a content item handler so the description cannot stop the other fields.
 *
 * @param {Function} handler mod_lti's handler.
 * @returns {Function}
 */
const wrap = (handler) => function(returnData) {
    if (!returnData || returnData.multiple || !('introeditor' in returnData)) {
        return handler.call(this, returnData);
    }
    const data = Object.assign({}, returnData);
    const intro = data.introeditor;
    delete data.introeditor;
    const result = handler.call(this, data);
    setDescription(intro);
    return result;
};

export const init = () => {
    // The mod_lti module defines the handler when it starts, which can be
    // before or after this one, so catch both orders.
    let handler = window.processContentItemReturnData;
    Object.defineProperty(window, 'processContentItemReturnData', {
        configurable: true,
        get: () => handler,
        set: (value) => {
            handler = (typeof value === 'function') ? wrap(value) : value;
        },
    });
    if (typeof handler === 'function') {
        handler = wrap(handler);
    }
};
