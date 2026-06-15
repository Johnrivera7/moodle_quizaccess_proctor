<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Moodle Proctor quiz access rule';
$string['privacy:metadata'] = 'The quiz access rule does not store personal data. Data is stored by local_moodle_proctor.';

$string['proctoringrequired'] = 'Enable proctoring';
$string['proctoringrequired_help'] = 'Requires Moodle Proctor (local_moodle_proctor) to be installed and configured. Captures webcam images during the attempt and logs incidents.';
$string['proctoringenabled'] = 'Proctoring is enabled for this quiz';
$string['requirewebcam'] = 'Require webcam during attempt';
$string['requireextension'] = 'Require browser extension during attempt';
$string['needenrollment'] = 'You must complete proctoring enrollment before this quiz. <a href="{$a}">Complete enrollment</a>';
$string['preflight_consent'] = 'I consent to webcam monitoring during this attempt';
$string['preflight_consentrequired'] = 'You must accept the monitoring consent to continue';

$string['cameralost'] = 'Webcam access was lost. Please re-enable your camera.';
$string['tabhidden'] = 'The exam tab was hidden. This incident has been logged.';
