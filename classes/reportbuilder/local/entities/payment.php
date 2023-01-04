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
 * Payment entity class implementation.
 *
 * @package   report_payments
 * @copyright 2023 Medical Access Uganda Limited
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_payments\reportbuilder\local\entities;

use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\duration;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\autocomplete;
use core_reportbuilder\local\helpers\format;
use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use stdClass;
use html_writer;
use core_collator;

/**
 * Payment entity class implementation.
 *
 * @package   report_payments
 * @copyright 2023 Medical Access Uganda Limited
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment extends base {

    /** @var int Result success */
    protected const SUCCESS = 0;

    /** @var int Result failed */
    protected const FAILED = 1;

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['payments' => 'pa'];
    }

    /**
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('payments');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('payments');
        $name = $this->get_entity_name();

        // Accountid column.
        $columns[] = (new column('accountid', new lang_string('name'), $name))
            ->add_joins($this->get_joins())
            ->add_join("LEFT JOIN {payment_accounts} pac ON {$tablealias}.accountid = pac.id")
            ->set_type(column::TYPE_TEXT)
            ->add_field("pac.name")
            ->set_is_sortable(true);

        // Component column.
        $columns[] = (new column('component', new lang_string('plugin'), $name))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.component")
            ->set_is_sortable(true);

        // Gateway column.
        $columns[] = (new column('gateway', new lang_string('type_paygw', 'plugin'), $name))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.gateway")
            ->set_is_sortable(true);

        // Amount column.
        $columns[] = (new column('amount', new lang_string('cost'), $name))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.amount")
            ->set_is_sortable(true)
            ->add_callback(function(string $value, stdClass $row) {
                return html_writer::span($value, 'text-right');
            });

        // Currency column.
        $columns[] = (new column('currency', new lang_string('currency'), $name))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.currency")
            ->set_is_sortable(true);

        // Date column.
        $columns[] = (new column('timecreated', new lang_string('date'), $name))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], get_string('strftimedatetimeshortaccurate', 'core_langconfig'));

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('payments');
        $name = $this->get_entity_name();

        // Component filter.
        $filters[] = (new filter(text::class, 'component', new lang_string('plugin'), $name, "{$tablealias}.component"))
            ->add_joins($this->get_joins());

        // Gateway filter.
        $filters[] = (new filter(text::class, 'gateway', new lang_string('type_paygw', 'plugin'), $name, "{$tablealias}.gateway"))
            ->add_joins($this->get_joins());

        // Currency filter.
        $filters[] = (new filter(text::class, 'currency', new lang_string('currency'), $name, "{$tablealias}.currency"))
            ->add_joins($this->get_joins());

        // Amount filter.
        $filters[] = (new filter(text::class, 'amount', new lang_string('cost'), $name, "{$tablealias}.amount"))
            ->add_joins($this->get_joins());

        // Date filter.
        $filters[] = (new filter(date::class, 'timecreated', new lang_string('date'), $name, "{$tablealias}.timecreated"))
            ->add_joins($this->get_joins())
            ->set_limited_operators([date::DATE_ANY, date::DATE_RANGE, date::DATE_PREVIOUS, date::DATE_CURRENT]);

        return $filters;
    }
}
