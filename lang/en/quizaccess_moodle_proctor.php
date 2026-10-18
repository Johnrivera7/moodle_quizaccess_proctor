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
 * Strings for component 'quizaccess_moodle_proctor', language 'en'.
 *
 * @package    quizaccess_moodle_proctor
 * @copyright  2026 John Rivera <https://github.com/Johnrivera7>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Proctor Chile quiz access rule';
$string['privacy:metadata'] = 'The quiz access rule does not store personal data. Data is stored by local_moodle_proctor.';

$string['proctoringrequired'] = 'Enable proctoring';
$string['proctoringrequired_help'] = 'Requires Proctor Chile (local_moodle_proctor) to be installed and configured. Captures webcam images during the attempt and logs incidents.';
$string['proctoringenabled'] = 'Proctoring is enabled for this quiz';
$string['requirewebcam'] = 'Require camera';
$string['requireextension'] = 'Require browser extension';
$string['needenrollment'] = 'You must complete proctoring enrollment before this quiz. <a href="{$a}">Complete enrollment</a>';
$string['needenrollment_short'] = 'You must complete proctoring enrollment before this quiz.';
$string['preflight_consent'] = 'I consent to webcam monitoring during this attempt';
$string['preflight_consentrequired'] = 'You must accept the monitoring consent to continue';

$string['cameralost'] = 'Webcam access was lost. Please re-enable your camera.';
$string['focuslost'] = 'The exam window lost focus. Keep this tab active during the attempt.';
$string['uploadfailed'] = 'Proctoring upload failed. If the problem persists, contact your teacher.';
$string['cameradenied'] = 'Camera permission is required for this proctored quiz.';
$string['cameranotsupported'] = 'Your browser does not support webcam access.';
$string['tabhidden'] = 'The exam tab was hidden. This incident has been logged.';
$string['identity_verifying'] = 'Verifying your identity against the registered ID...';
$string['identity_verified'] = 'Identity verified successfully.';
$string['identity_retry'] = 'Retry';
$string['identity_leave'] = 'Leave the quiz';
$string['settings_info_heading'] = 'Configuration';
$string['settings_info_desc'] = 'Main proctoring, storage, RUT and identity settings are in <a href="/admin/settings.php?section=local_moodle_proctor_settings">Local plugins → Proctor Chile</a>.';
$string['extension_missing'] = 'This exam requires the proctoring browser extension and it is not responding. Install or enable it and reload the page to continue.';
$string['webcam_required'] = 'This exam requires your camera. Allow camera access to continue.';
$string['webcam_retry'] = 'Allow camera';
$string['focus_away_seconds'] = 'Was away from the exam window for {$a} s.';
$string['proctorsection'] = 'Proctoring (Proctor Chile)';
$string['rules_heading'] = 'What to do on each incident';
$string['rules_intro'] = 'For each kind of incident choose what happens and from which occurrence in the same attempt. <strong>Record only</strong>: it stays in the report. <strong>Notify</strong>: also alerts the teacher and the student (Moodle message and email) and keeps the evidence. <strong>Block the exam</strong>: also blocks the attempt; the student cannot continue until a teacher, manager or administrator reviews the evidence and gives another chance.';
$string['rule_action_log'] = 'Record only';
$string['rule_action_notify'] = 'Notify the teacher';
$string['rule_action_block'] = 'Block the exam';
$string['rule_after'] = 'from';
$string['rule_multiple_faces'] = 'More than one person in front of the camera';
$string['rule_multiple_faces_help'] = 'An exam photo shows more than one person.';
$string['rule_identity_mismatch'] = 'Someone else instead of the student';
$string['rule_identity_mismatch_help'] = 'The identity check says the person in front of the camera is not the enrolled student.';
$string['rule_no_face'] = 'Student away from the camera';
$string['rule_no_face_help'] = 'Counted as consecutive captures with nobody in front of the camera (the student may have stepped away briefly).';
$string['rule_tab_switch'] = 'Switching tabs or leaving the window';
$string['rule_tab_switch_help'] = 'The student switches tabs, leaves the exam window (for 2 seconds or more) or moves to another browser or app.';
$string['rule_ai_tool'] = 'Using AI tools';
$string['rule_ai_tool_help'] = 'ChatGPT, Claude, Gemini, Copilot, Perplexity or another AI tool is opened during the exam (requires the browser extension).';
$string['rule_copy_paste'] = 'Copy, paste or keyboard shortcuts';
$string['rule_copy_paste_help'] = 'Attempts to copy, paste or use blocked shortcuts (for example Ctrl+C or Ctrl+V).';
$string['rule_screen'] = 'Leaving full screen, stopping screen sharing or extra monitors';
$string['rule_screen_help'] = 'The student leaves full screen, stops sharing the screen or connects another monitor.';
$string['rule_incident_total'] = 'Total serious incidents';
$string['rule_incident_total_help'] = 'Counts every serious incident of the attempt, of any kind.';
$string['staff_violations_link'] = 'Proctoring violations: {$a} blocked attempt(s) — review';
$string['requirefullscreen'] = 'Require full screen';
$string['requirefullscreen_help'] = 'The exam is covered if the student leaves full screen. Browsers leave full screen on every page change, so the student must turn it back on with a click on each page (fewer clicks with more questions per page).';
$string['requirescreenshare'] = 'Require sharing the entire screen';
$string['requirescreenshare_help'] = 'Forces sharing the entire screen during the exam. Browsers stop screen sharing on every page change and ask to pick it again, so it is best used with several questions per page. It is only required; the screen is not recorded.';
$string['rule_after_option_times'] = 'occurrence {$a}';
$string['rule_after_option_captures'] = 'consecutive capture {$a}';
