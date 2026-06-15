<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    quizaccess_moodle_proctor
// @copyright  2026 John Rivera
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

use mod_quiz\local\access_rule_base;
use mod_quiz\form\preflight_check_form;
use mod_quiz\quiz_settings;

/**
 * Quiz access rule for Moodle Proctor.
 */
class quizaccess_moodle_proctor extends access_rule_base {

    /** @var stdClass|null */
    protected $proctorsettings;

    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits): ?access_rule_base {
        if (empty($quizobj->get_quiz()->proctoringrequired)) {
            return null;
        }
        if (!local_moodle_proctor_is_configured()) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform): void {
        $mform->addElement('selectyesno', 'proctoringrequired', get_string('proctoringrequired', 'quizaccess_moodle_proctor'));
        $mform->addHelpButton('proctoringrequired', 'proctoringrequired', 'quizaccess_moodle_proctor');
        $mform->setDefault('proctoringrequired', 0);

        $mform->addElement('selectyesno', 'requirewebcam', get_string('requirewebcam', 'quizaccess_moodle_proctor'));
        $mform->setDefault('requirewebcam', 1);
        $mform->hideIf('requirewebcam', 'proctoringrequired', 'eq', 0);

        $mform->addElement('selectyesno', 'requireextension', get_string('requireextension', 'quizaccess_moodle_proctor'));
        $mform->setDefault('requireextension', 0);
        $mform->hideIf('requireextension', 'proctoringrequired', 'eq', 0);
    }

    public static function save_settings($quiz): void {
        global $DB;
        $record = $DB->get_record('quizaccess_moodle_proctor', ['quizid' => $quiz->id]);
        if (empty($quiz->proctoringrequired)) {
            if ($record) {
                $DB->delete_records('quizaccess_moodle_proctor', ['quizid' => $quiz->id]);
            }
            return;
        }
        if (!$record) {
            $record = (object) ['quizid' => $quiz->id];
        }
        $record->proctoringrequired = (int) !empty($quiz->proctoringrequired);
        $record->requirewebcam = (int) !empty($quiz->requirewebcam);
        $record->requireextension = (int) !empty($quiz->requireextension);
        if (empty($record->id)) {
            $DB->insert_record('quizaccess_moodle_proctor', $record);
        } else {
            $DB->update_record('quizaccess_moodle_proctor', $record);
        }
    }

    public static function delete_settings($quiz): void {
        global $DB;
        $DB->delete_records('quizaccess_moodle_proctor', ['quizid' => $quiz->id]);
    }

    public static function get_settings_sql($quizid): array {
        return [
            'proctor.proctoringrequired, proctor.requirewebcam, proctor.requireextension',
            'LEFT JOIN {quizaccess_moodle_proctor} proctor ON proctor.quizid = quiz.id',
            [],
        ];
    }

    public function description(): array {
        $lines = [];
        $lines[] = get_string('proctoringenabled', 'quizaccess_moodle_proctor');
        return $lines;
    }

    public function prevent_access(): string {
        global $USER;

        $config = local_moodle_proctor_get_config();
        if (!empty($config->requireenrollment)) {
            $courseid = $this->quizobj->get_course()->id;
            $enrollment = new \local_moodle_proctor\enrollment_manager();
            if (!$enrollment->is_complete($USER->id, $courseid)) {
                $url = new moodle_url('/local/moodle_proctor/enrollment.php', ['courseid' => $courseid]);
                return get_string('needenrollment', 'quizaccess_moodle_proctor', $url->out());
            }
        }
        return '';
    }

    public function is_preflight_check_required($attemptid): bool {
        return true;
    }

    public function add_preflight_check_form_fields(preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid): void {
        $config = local_moodle_proctor_get_config();
        $mform->addElement('static', 'proctorconsentstatic', '', format_text(
            $config->consenttext ?: get_string('settings_consenttext_default', 'local_moodle_proctor'),
            FORMAT_HTML
        ));
        $mform->addElement('advcheckbox', 'proctorconsent', get_string('preflight_consent', 'quizaccess_moodle_proctor'));
        $mform->addRule('proctorconsent', get_string('required'), 'required', null, 'client');
    }

    public function validate_preflight_check($data, $files, $errors, $attemptid): array {
        if (empty($data['proctorconsent'])) {
            $errors['proctorconsent'] = get_string('preflight_consentrequired', 'quizaccess_moodle_proctor');
        }
        return $errors;
    }

    public function setup_attempt_page($page): void {
        $config = local_moodle_proctor_get_config();
        $interval = max(10, (int) $config->camshotinterval);
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        $page->requires->js_call_amd('quizaccess_moodle_proctor/proctor', 'init', [
            $attemptid,
            (int) $page->cm->id,
            (int) $config->camshotwidth,
            (int) $config->camshotheight,
            $interval * 1000,
            !empty($this->quizobj->get_quiz()->requireextension),
        ]);
    }
}
