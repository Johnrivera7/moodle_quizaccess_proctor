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
 * Upgrade steps for quizaccess_moodle_proctor.
 *
 * @package    quizaccess_moodle_proctor
 * @copyright  2026 John Rivera <https://github.com/Johnrivera7>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the quizaccess_moodle_proctor plugin.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool always true.
 */
function xmldb_quizaccess_moodle_proctor_upgrade($oldversion) {
    if ($oldversion < 2026061500) {
        upgrade_plugin_savepoint(true, 2026061500, 'quizaccess', 'moodle_proctor');
    }

    if ($oldversion < 2026061600) {
        upgrade_plugin_savepoint(true, 2026061600, 'quizaccess', 'moodle_proctor');
    }

    if ($oldversion < 2026062551) {
        upgrade_plugin_savepoint(true, 2026062551, 'quizaccess', 'moodle_proctor');
    }

    if ($oldversion < 2026062552) {
        upgrade_plugin_savepoint(true, 2026062552, 'quizaccess', 'moodle_proctor');
    }

    if ($oldversion < 2026101702) {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table('quizaccess_moodle_proctor');
        $field = new xmldb_field('rules', XMLDB_TYPE_TEXT, null, null, null, null, null, 'requireextension');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026101702, 'quizaccess', 'moodle_proctor');
    }

    if ($oldversion < 2026101703) {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table('quizaccess_moodle_proctor');
        $fields = [
            new xmldb_field('requirefullscreen', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'requireextension'),
            new xmldb_field('requirescreenshare', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'requirefullscreen'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2026101703, 'quizaccess', 'moodle_proctor');
    }

    return true;
}
