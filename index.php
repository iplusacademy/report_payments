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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Global payments.
 *
 * @package    report_payments
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

use report_payments\reportbuilder\local\systemreports\payments_course;
use report_payments\reportbuilder\local\systemreports\payments_global;
use report_payments\reportbuilder\local\systemreports\payments_user;
use core_reportbuilder\system_report_factory;
use core_reportbuilder\external\system_report_exporter;

$courseid = optional_param('courseid', 1, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$download = optional_param('download', false, PARAM_BOOL);
$filter = optional_param('filter', null, PARAM_TEXT);

if ($courseid == 1) {
    if ($categoryid != 0) {
        $context = \context_coursecat::instance($categoryid);
        $params = ['categoryid' => $categoryid];
        $classname = payments_global::class;
    } else if ($userid != 0) {
        $context = \context_user::instance($userid);
        $params = ['userid' => $userid];
        $classname = payments_user::class;
    } else {
        $context = \context_system::instance();
        $params = ['courseid' => $courseid];
        $classname = payments_global::class;
    }
} else {
    $context = \context_course::instance($courseid);
    $params = ['courseid' => $courseid];
    $classname = payments_course::class;
}
require_login();

$PAGE->set_url(new \moodle_url('/report/payments/index.php', $params));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$strheading = get_string('payments');

$PAGE->set_title($strheading);
switch ($context->contextlevel) {
    case CONTEXT_COURSECAT:
        core_course_category::page_setup();
        break;
    case CONTEXT_COURSE:
        $course = get_course($courseid);
        $PAGE->set_heading($course->fullname);
        break;
    default:
        $PAGE->set_heading($strheading);
}
\report_payments\event\report_viewed::create(['context' => $context])->trigger();
$report = system_report_factory::create($classname, $context);
if (!empty($filter)) {
    $report->set_filter_values(['payment:name_values' => $filter]);
}
echo $OUTPUT->header();
echo $report->output();
echo $OUTPUT->footer();
