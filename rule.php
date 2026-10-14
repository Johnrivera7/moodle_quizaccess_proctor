<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    quizaccess_moodle_proctor
// @copyright  2026 John Rivera
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/moodle_proctor/lib.php');

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

    /**
     * Enrollment block message for students who have not completed identity verification.
     *
     * @return string|false
     */
    protected function get_enrollment_block_message(): string|false {
        global $USER;

        $courseid = (int) $this->quizobj->get_course()->id;
        if (!local_moodle_proctor_user_requires_enrollment((int) $USER->id, $courseid)) {
            return false;
        }

        $enrollment = new \local_moodle_proctor\enrollment_manager();
        if ($enrollment->is_complete((int) $USER->id, $courseid)) {
            return false;
        }

        $returnurl = new moodle_url('/mod/quiz/view.php', ['id' => $this->quizobj->get_cmid()]);
        $url = local_moodle_proctor_enrollment_url($courseid, $returnurl);
        return get_string('needenrollment', 'quizaccess_moodle_proctor', $url->out(false));
    }

    public function prevent_access(): string|false {
        return $this->get_enrollment_block_message() ?: false;
    }

    public function prevent_new_attempt($numprevattempts, $lastattempt): string|false {
        return $this->get_enrollment_block_message() ?: false;
    }

    public function is_preflight_check_required($attemptid): bool {
        return true;
    }

    public function add_preflight_check_form_fields(preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid): void {
        $blockmessage = $this->get_enrollment_block_message();
        if ($blockmessage) {
            $mform->addElement('static', 'proctorenrollmentblock', '', $blockmessage);
            return;
        }

        $config = local_moodle_proctor_get_config();
        $mform->addElement('static', 'proctorconsentstatic', '', format_text(
            $config->consenttext ?: get_string('settings_consenttext_default', 'local_moodle_proctor'),
            FORMAT_HTML
        ));
        $mform->addElement('advcheckbox', 'proctorconsent', get_string('preflight_consent', 'quizaccess_moodle_proctor'));
    }

    public function validate_preflight_check($data, $files, $errors, $attemptid): array {
        $blockmessage = $this->get_enrollment_block_message();
        if ($blockmessage) {
            $errors['proctorenrollmentblock'] = get_string('needenrollment_short', 'quizaccess_moodle_proctor');
            return $errors;
        }

        if (empty($data['proctorconsent'])) {
            $errors['proctorconsent'] = get_string('preflight_consentrequired', 'quizaccess_moodle_proctor');
        }

        return $errors;
    }

    public function setup_attempt_page($page): void {
        if ($page->pagetype !== 'mod-quiz-attempt') {
            return;
        }

        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if ($attemptid <= 0) {
            return;
        }

        $config = local_moodle_proctor_get_config();
        $interval = max(10, (int) $config->camshotinterval);

        $identityenabled = !empty($config->requirequizidentity);

        $page->requires->css('/local/moodle_proctor/styles/proctor_live.css');
        $page->requires->js_call_amd('quizaccess_moodle_proctor/proctor', 'init', [
            $attemptid,
            (int) $page->cm->id,
            (int) $config->camshotwidth,
            (int) $config->camshotheight,
            $interval * 1000,
            !empty($this->quizobj->get_quiz()->requireextension),
            get_string('livealert_title', 'local_moodle_proctor'),
            [
                'enabled' => $identityenabled,
                'block' => !empty($config->blockquizidentity),
                'checking' => get_string('identity_verifying', 'quizaccess_moodle_proctor'),
                'ok' => get_string('identity_verified', 'quizaccess_moodle_proctor'),
                'mismatch' => get_string('rekognition_quiz_mismatch', 'local_moodle_proctor'),
                'blocked' => get_string('rekognition_quiz_blocked', 'local_moodle_proctor'),
                'retry' => get_string('identity_retry', 'quizaccess_moodle_proctor'),
                'leave' => get_string('identity_leave', 'quizaccess_moodle_proctor'),
                'courseurl' => (new moodle_url('/course/view.php', ['id' => (int) $page->cm->course]))->out(false),
                'fullscreen' => get_string('livealert_fullscreen_exit', 'local_moodle_proctor'),
                'displays' => get_string('livealert_multiple_displays', 'local_moodle_proctor'),
                'screenstopped' => get_string('livealert_screen_share_stopped', 'local_moodle_proctor'),
                'audio' => get_string('livealert_audio_detected', 'local_moodle_proctor'),
                'returnfullscreen' => get_string('lock_return_fullscreen', 'local_moodle_proctor'),
                'sharescreen' => get_string('lock_share_screen', 'local_moodle_proctor'),
            ],
            [
                'focuslost' => get_string('focuslost', 'quizaccess_moodle_proctor'),
                'uploadfailed' => get_string('uploadfailed', 'quizaccess_moodle_proctor'),
                'cameradenied' => get_string('cameradenied', 'quizaccess_moodle_proctor'),
                'cameranotsupported' => get_string('cameranotsupported', 'quizaccess_moodle_proctor'),
            ],
        ]);
    }
}
