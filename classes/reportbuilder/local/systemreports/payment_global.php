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
 * Global payment
 *
 * @package    report_payments
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_payments\reportbuilder\local\systemreports;

use context_system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\entities\course;
use core_course\reportbuilder\local\entities\enrolment;
use core_reportbuilder\local\report\action;
use lang_string;
use moodle_url;
use pix_icon;
use core_reportbuilder\system_report;
use report_payments\reportbuilder\local\entities\payment;


class payment_global extends system_report {

   /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        $main = new payment();
        $mainalias = $main->get_table_alias('payments');

        $this->set_main_table('payments', $mainalias);
        $this->add_entity($main);

        $this->add_base_fields("{$mainalias}.id");

        $user = new user();
        $useralias = $user->get_table_alias('user');
        $this->add_entity($user->add_join(
            "LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$mainalias}.userid"
        ));

        $enrol = new enrolment();
        $enrolalias = $enrol->get_table_alias('enrol');
        $userenrolalias = $enrol->get_table_alias('user_enrolments');

        $course = new course();
        $coursealias = $course->get_table_alias('course');
        $this->add_entity($course->add_join(
            "LEFT JOIN {user_enrolments} {$userenrolalias} ON {$userenrolalias}.userid = {$mainalias}.userid
             LEFT JOIN {enrol} {$enrolalias} ON {$enrolalias}.id = {$userenrolalias}.enrolid
             LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$enrolalias}.courseid"
        ));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true, get_string('tasklogs', 'admin'));
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Get the visible name of the report
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('payments');
    }

    /**
     * Adds the columns we want to display in the report
     */
    public function add_columns(): void {
        $columns = [
            'payment:accountid',
            'course:fullname',
            'payment:gateway',
            'user:fullname',
            'payment:amount',
            'payment:currency',
            'payment:timecreated',
        ];
        $this->add_columns_from_entities($columns);
        if ($column = $this->get_column('course:fullname')) {
            $column->set_title(new lang_string('course'));
        }
        $this->set_initial_sort_column('payment:gateway', SORT_DESC);
    }

    /**
     * Adds the filters we want to display in the report
     */
    protected function add_filters(): void {
        $filters = [
            'course:fullname',
            'user:fullname',
            'payment:gateway',
            'payment:currency',
            'payment:timecreated',
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     */
    protected function add_actions(): void {
        // TODO: Which actions should be implemented?
        $this->add_action((new action(
            new moodle_url('/admin/tasklogs.php', ['logid' => ':id']),
            new pix_icon('e/search', ''),
            [],
            true,
            new lang_string('view'),
        )));

        $this->add_action((new action(
            new moodle_url('/admin/tasklogs.php', ['logid' => ':id', 'download' => true]),
            new pix_icon('t/download', ''),
            [],
            false,
            new lang_string('download'),
        )));
    }
}
