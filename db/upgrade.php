<?php
defined('MOODLE_INTERNAL') || die();

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
