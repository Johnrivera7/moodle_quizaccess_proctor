<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    quizaccess_moodle_proctor
// @copyright  2026 John Rivera <https://github.com/Johnrivera7>
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$plugin->component   = 'quizaccess_moodle_proctor';
$plugin->version     = 2026062562;
$plugin->requires    = 2025041400; // Moodle 5.0.
$plugin->supported   = [500, 501];
$plugin->maturity    = MATURITY_STABLE;
$plugin->release     = '1.5.2-moodle50';
$plugin->dependencies = [
    'local_moodle_proctor' => 2026062565,
];
