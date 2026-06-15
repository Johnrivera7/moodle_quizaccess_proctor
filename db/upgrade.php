<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_quizaccess_moodle_proctor_upgrade($oldversion) {
    if ($oldversion < 2026061500) {
        upgrade_plugin_savepoint(true, 2026061500, 'quizaccess', 'moodle_proctor');
    }

    if ($oldversion < 2026061600) {
        upgrade_plugin_savepoint(true, 2026061600, 'quizaccess', 'moodle_proctor');
    }
    return true;
}
