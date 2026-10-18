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

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz access rule admin settings (included from mod/quiz/settings.php).
 *
 * @package    quizaccess_moodle_proctor
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/moodle_proctor/lib.php');

if ($hassiteconfig && $ADMIN->fulltree) {
    local_moodle_proctor_add_author_settings(
        $settings,
        'quizaccess_moodle_proctor',
        'https://github.com/Johnrivera7/moodle_quizaccess_proctor'
    );

    $settings->add(new admin_setting_description(
        'quizaccess_moodle_proctor/info',
        get_string('settings_info_heading', 'quizaccess_moodle_proctor'),
        get_string('settings_info_desc', 'quizaccess_moodle_proctor')
    ));
}
