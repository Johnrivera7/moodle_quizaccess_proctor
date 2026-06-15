<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    quizaccess_moodle_proctor
// @copyright  2026 John Rivera
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace quizaccess_moodle_proctor\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider — data stored in local_moodle_proctor.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
