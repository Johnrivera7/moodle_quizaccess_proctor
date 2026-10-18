<?php
// This file is part of Moodle - http://moodle.org/
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

global $CFG;
require_once($CFG->dirroot . '/local/moodle_proctor/lib.php');

use mod_quiz\local\access_rule_base;
use mod_quiz\form\preflight_check_form;
use mod_quiz\quiz_settings;

/**
 * Quiz access rule for Moodle Proctor.
 *
 * @copyright  2026 John Rivera <https://github.com/Johnrivera7>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_moodle_proctor extends access_rule_base {
    /** @var stdClass|null */
    protected $proctorsettings;

    /**
     * Return an instance of this rule when proctoring is required for the quiz and the
     * Moodle Proctor local plugin is configured, otherwise null.
     *
     * @param quiz_settings $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return access_rule_base|null the rule, if applicable, else null.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits): ?access_rule_base {
        if (empty($quizobj->get_quiz()->proctoringrequired)) {
            return null;
        }
        if (!local_moodle_proctor_is_configured()) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /** @var string[] Incident rules shown in the quiz settings, in order. */
    protected const RULE_KEYS = ['multiple_faces', 'identity_mismatch', 'no_face', 'tab_switch', 'ai_tool',
        'copy_paste', 'screen', 'incident_total'];

    /**
     * Own "Supervisión" section right after General (like Safe Exam Browser), with what to do
     * for each kind of incident.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform): void {
        global $DB;
        $before = $mform->elementExists('timing') ? 'timing' : null;
        $add = function (HTML_QuickForm_element $element) use ($mform, $before) {
            if ($before) {
                $mform->insertElementBefore($element, $before);
            } else {
                $mform->addElement($element);
            }
        };
        $quizid = (int) $quizform->get_instance();
        $rules = \local_moodle_proctor\quiz_rules::for_quiz($quizid);
        $enabled = $quizid && $DB->record_exists('quizaccess_moodle_proctor', ['quizid' => $quizid, 'proctoringrequired' => 1]);

        $add($mform->createElement('header', 'moodleproctorhdr', get_string('proctorsection', 'quizaccess_moodle_proctor')));
        $mform->setExpanded('moodleproctorhdr', $enabled);

        $add($mform->createElement(
            'selectyesno',
            'proctoringrequired',
            get_string('proctoringrequired', 'quizaccess_moodle_proctor')
        ));
        $mform->addHelpButton('proctoringrequired', 'proctoringrequired', 'quizaccess_moodle_proctor');
        $mform->setDefault('proctoringrequired', 0);

        $add($mform->createElement('selectyesno', 'requirewebcam', get_string('requirewebcam', 'quizaccess_moodle_proctor')));
        $mform->setDefault('requirewebcam', 1);
        $mform->hideIf('requirewebcam', 'proctoringrequired', 'eq', 0);

        $add($mform->createElement('selectyesno', 'requireextension', get_string('requireextension', 'quizaccess_moodle_proctor')));
        $mform->setDefault('requireextension', 0);
        $mform->hideIf('requireextension', 'proctoringrequired', 'eq', 0);

        $add($mform->createElement(
            'selectyesno',
            'requirefullscreen',
            get_string('requirefullscreen', 'quizaccess_moodle_proctor')
        ));
        $mform->setDefault('requirefullscreen', 1);
        $mform->addHelpButton('requirefullscreen', 'requirefullscreen', 'quizaccess_moodle_proctor');
        $mform->hideIf('requirefullscreen', 'proctoringrequired', 'eq', 0);

        $add($mform->createElement(
            'selectyesno',
            'requirescreenshare',
            get_string('requirescreenshare', 'quizaccess_moodle_proctor')
        ));
        $mform->setDefault('requirescreenshare', 0);
        $mform->addHelpButton('requirescreenshare', 'requirescreenshare', 'quizaccess_moodle_proctor');
        $mform->hideIf('requirescreenshare', 'proctoringrequired', 'eq', 0);

        $add($mform->createElement(
            'static',
            'proctorrulesintro',
            get_string('rules_heading', 'quizaccess_moodle_proctor'),
            get_string('rules_intro', 'quizaccess_moodle_proctor')
        ));
        $mform->hideIf('proctorrulesintro', 'proctoringrequired', 'eq', 0);

        $actions = [
            'log' => get_string('rule_action_log', 'quizaccess_moodle_proctor'),
            'notify' => get_string('rule_action_notify', 'quizaccess_moodle_proctor'),
            'block' => get_string('rule_action_block', 'quizaccess_moodle_proctor'),
        ];
        foreach (self::RULE_KEYS as $key) {
            $actionname = 'proctorrule_' . $key . '_action';
            $aftername = 'proctorrule_' . $key . '_after';
            // A fixed list (1st…20th) instead of free text: no letters, zero or negative numbers.
            $optionkey = $key === 'no_face' ? 'rule_after_option_captures' : 'rule_after_option_times';
            $counts = [];
            foreach (range(1, 20) as $n) {
                $counts[$n] = get_string($optionkey, 'quizaccess_moodle_proctor', $n);
            }
            $group = $mform->createElement(
                'group',
                'proctorrule_' . $key,
                get_string('rule_' . $key, 'quizaccess_moodle_proctor'),
                [
                    $mform->createElement('select', $actionname, '', $actions),
                    $mform->createElement(
                        'static',
                        $aftername . '_label',
                        '',
                        get_string('rule_after', 'quizaccess_moodle_proctor')
                    ),
                    $mform->createElement('select', $aftername, '', $counts),
                ],
                ' ',
                false
            );
            $add($group);
            $mform->setType($aftername, PARAM_INT);
            $mform->setDefault($actionname, $rules[$key]['action']);
            $mform->setDefault($aftername, min(20, max(1, (int) $rules[$key]['after'])));
            $mform->disabledIf($aftername, $actionname, 'eq', 'log');
            $mform->addHelpButton('proctorrule_' . $key, 'rule_' . $key, 'quizaccess_moodle_proctor');
            $mform->hideIf('proctorrule_' . $key, 'proctoringrequired', 'eq', 0);
        }
    }

    /**
     * Save the proctoring settings and incident rules when the quiz settings form is submitted.
     * The settings row is removed when proctoring is turned off.
     *
     * @param stdClass $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     */
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
        $record->requirefullscreen = (int) !empty($quiz->requirefullscreen);
        $record->requirescreenshare = (int) !empty($quiz->requirescreenshare);
        $input = [];
        foreach (self::RULE_KEYS as $key) {
            $input[$key] = [
                'action' => $quiz->{'proctorrule_' . $key . '_action'} ?? null,
                'after' => $quiz->{'proctorrule_' . $key . '_after'} ?? null,
            ];
        }
        $record->rules = json_encode(\local_moodle_proctor\quiz_rules::clean($input));
        if (empty($record->id)) {
            $DB->insert_record('quizaccess_moodle_proctor', $record);
        } else {
            $DB->update_record('quizaccess_moodle_proctor', $record);
        }
    }

    /**
     * Delete the proctoring settings when the quiz is deleted.
     *
     * @param stdClass $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     */
    public static function delete_settings($quiz): void {
        global $DB;
        $DB->delete_records('quizaccess_moodle_proctor', ['quizid' => $quiz->id]);
    }

    /**
     * Return the bits of SQL needed to load the proctoring settings with the other quiz settings.
     *
     * @param int $quizid the id of the quiz we are loading settings for.
     * @return array with three elements: fields to add to the select list, joins and params.
     */
    public static function get_settings_sql($quizid): array {
        return [
            'proctor.proctoringrequired, proctor.requirewebcam, proctor.requireextension, '
                . 'proctor.requirefullscreen, proctor.requirescreenshare',
            'LEFT JOIN {quizaccess_moodle_proctor} proctor ON proctor.quizid = quiz.id',
            [],
        ];
    }

    /**
     * Tell users the quiz is proctored. Staff with the viewreports capability also get
     * a link to the blocked attempts of this quiz.
     *
     * @return array messages explaining the restriction.
     */
    public function description(): array {
        global $DB;
        $lines = [];
        $lines[] = get_string('proctoringenabled', 'quizaccess_moodle_proctor');
        // Staff: blocked attempts of this quiz, with a link to review and give another chance.
        $context = context_module::instance((int) $this->quizobj->get_cmid());
        if (has_capability('local/moodle_proctor:viewreports', $context)) {
            $blocked = $DB->count_records_sql(
                'SELECT COUNT(DISTINCT attemptid) FROM {local_moodle_proctor_violation} WHERE quizid = ? AND status = ?',
                [(int) $this->quiz->id, \local_moodle_proctor\violation_manager::STATUS_BLOCKED]
            );
            $url = new moodle_url('/local/moodle_proctor/violations.php', [
                'courseid' => (int) $this->quizobj->get_course()->id,
                'quizid' => (int) $this->quiz->id,
            ]);
            $lines[] = html_writer::link(
                $url,
                get_string('staff_violations_link', 'quizaccess_moodle_proctor', $blocked),
                ['class' => $blocked ? 'btn btn-danger btn-sm' : 'btn btn-outline-secondary btn-sm']
            );
        }
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

    /**
     * Server-side block after an academic integrity violation. It holds whatever happens in
     * the browser: attempt pages and autosave are refused until a teacher releases it.
     *
     * @return string|false
     */
    protected function get_violation_block_message(): string|false {
        global $USER;
        $block = \local_moodle_proctor\violation_manager::get_quiz_block((int) $USER->id, (int) $this->quiz->id);
        if (!$block) {
            return false;
        }
        return \local_moodle_proctor\violation_manager::block_message($block);
    }

    /**
     * Block access while there is an unreleased integrity violation or identity
     * verification is pending.
     *
     * @return string|false false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     */
    public function prevent_access(): string|false {
        return $this->get_violation_block_message() ?: ($this->get_enrollment_block_message() ?: false);
    }

    /**
     * Block new attempts while there is an unreleased integrity violation or identity
     * verification is pending.
     *
     * @param int $numprevattempts the number of previous attempts this user has made.
     * @param stdClass $lastattempt information about the user's last completed attempt.
     * @return string|false false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt): string|false {
        return $this->get_violation_block_message() ?: ($this->get_enrollment_block_message() ?: false);
    }

    /**
     * Ask for consent once per attempt. attempt.php asks again on every page load, so returning
     * true unconditionally sent students back to the consent form in a loop.
     *
     * @param int|null $attemptid
     * @return bool
     */
    public function is_preflight_check_required($attemptid): bool {
        global $SESSION;
        return empty($SESSION->quizaccess_moodle_proctor_consented[$this->quiz->id]);
    }

    /**
     * Remember in the session that the student gave consent for this quiz.
     *
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     */
    public function notify_preflight_check_passed($attemptid): void {
        global $SESSION;
        $SESSION->quizaccess_moodle_proctor_consented[$this->quiz->id] = true;
    }

    /**
     * Clear the consent flag from the session when the current attempt is finished.
     */
    public function current_attempt_finished(): void {
        global $SESSION;
        unset($SESSION->quizaccess_moodle_proctor_consented[$this->quiz->id]);
    }

    /**
     * Add the consent text and checkbox to the pre-flight check form, or the identity
     * verification message when the student has not completed it.
     *
     * @param preflight_check_form $quizform the form being built.
     * @param MoodleQuickForm $mform The wrapped MoodleQuickForm.
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     */
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

    /**
     * Require the consent checkbox, and refuse the form while identity verification is pending.
     *
     * @param array $data the submitted form data.
     * @param array $files any files in the submission.
     * @param array $errors the list of validation errors that is being built up.
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     * @return array the updated $errors array.
     */
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

    /**
     * Load the proctoring JavaScript and styles on the attempt page.
     *
     * @param moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page): void {
        if ($page->pagetype !== 'mod-quiz-attempt') {
            return;
        }

        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if ($attemptid <= 0) {
            return;
        }

        $config = local_moodle_proctor_get_config();
        // Identity check (may use paid Rekognition) every camshotinterval; the free local
        // "more than one person / nobody" check every facecheckinterval (0 = only with identity).
        $interval = max(10, (int) $config->camshotinterval);
        $faceinterval = (int) ($config->facecheckinterval ?? 30);
        $faceinterval = ($faceinterval > 0 && $faceinterval < $interval) ? max(10, $faceinterval) : 0;

        $identityenabled = !empty($config->requirequizidentity);

        $page->requires->css('/local/moodle_proctor/styles/proctor_live.css');
        $page->requires->js_call_amd('quizaccess_moodle_proctor/proctor', 'init', [
            $attemptid,
            (int) $page->cm->id,
            (int) $config->camshotwidth,
            (int) $config->camshotheight,
            $interval * 1000,
            [
                'faceintervalms' => $faceinterval * 1000,
                'require' => !empty($this->quizobj->get_quiz()->requireextension),
                // Browsers drop full screen and screen sharing on every page load: each one is optional.
                'fullscreen' => !isset($this->quizobj->get_quiz()->requirefullscreen)
                    || !empty($this->quizobj->get_quiz()->requirefullscreen),
                'screenshare' => !empty($this->quizobj->get_quiz()->requirescreenshare),
                'missing' => get_string('extension_missing', 'quizaccess_moodle_proctor'),
                'webcam' => !isset($this->quizobj->get_quiz()->requirewebcam)
                    || !empty($this->quizobj->get_quiz()->requirewebcam),
                'webcamrequired' => get_string('webcam_required', 'quizaccess_moodle_proctor'),
                'retrycamera' => get_string('webcam_retry', 'quizaccess_moodle_proctor'),
            ],
            get_string('livealert_title', 'local_moodle_proctor'),
            [
                'enabled' => $identityenabled,
                'blockedtitle' => get_string('violation_blocked_title', 'local_moodle_proctor'),
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
                'awayseconds' => get_string('focus_away_seconds', 'quizaccess_moodle_proctor'),
                'uploadfailed' => get_string('uploadfailed', 'quizaccess_moodle_proctor'),
                'cameradenied' => get_string('cameradenied', 'quizaccess_moodle_proctor'),
                'cameranotsupported' => get_string('cameranotsupported', 'quizaccess_moodle_proctor'),
            ],
        ]);
    }
}
