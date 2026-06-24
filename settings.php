<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @package    quizaccess_moodle_proctor
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/moodle_proctor/lib.php');

$settings = new admin_settingpage('quizaccess_moodle_proctor_settings', get_string('pluginname', 'quizaccess_moodle_proctor'));
if ($ADMIN->fulltree) {
    $ADMIN->add('modsettingsquiz', $settings);

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
