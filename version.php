<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_moodle_proctor
// @copyright  2026 John Rivera <https://github.com/Johnrivera7>
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$plugin->component   = 'quizaccess_moodle_proctor';
$plugin->version     = 2026061500;
$plugin->requires    = 2024100700;
$plugin->maturity    = MATURITY_STABLE;
$plugin->release     = '1.0.0-moodle45';
$plugin->dependencies = [
    'local_moodle_proctor' => 2026061500,
];
