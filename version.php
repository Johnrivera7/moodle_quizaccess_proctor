<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    quizaccess_moodle_proctor
// @copyright  2026 John Rivera <https://github.com/Johnrivera7>
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$plugin->component   = 'quizaccess_moodle_proctor';
$plugin->version     = 2026062510;
$plugin->requires    = 2026042000; // Moodle 5.2.
$plugin->supported   = [502, 503];
$plugin->maturity    = MATURITY_STABLE;
$plugin->release     = '1.4.0-moodle52';
$plugin->dependencies = [
    'local_moodle_proctor' => 2026062510,
];
