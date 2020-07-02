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

namespace mod_astra\grades;

defined('MOODLE_INTERNAL') || die();

use \core_grades\local\gradeitem\itemnumber_mapping;


class gradeitems implements itemnumber_mapping {

    /**
     * Get the grade item mapping of item number to item name.
     *
     * @return array
     */
    public static function get_itemname_mapping_for_component(): array {
        /* This is a new required API in Moodle 3.8:
         * https://github.com/moodle/moodle/blob/4557b7fed29d25e4fe3c14be8dc353ef39484ee7/mod/upgrade.txt#L7
         * Unfortunately, Moodle assumes that all instances of an activity use
         * the same grade item numbers. Astra has a varying number of exercises
         * in each exercise round and thus, a varying number of grade items.
         * As a quick workaround, this function assumes that no exercise round
         * has over 30 exercises. We hardcode dummy grade item names for
         * 30 exercises here.
         *
         * In the future, Astra should possibly completely remove grade items
         * for exercises. Only the grade item of the exercise round would remain
         * and it would show the total points of the exercise round as before.
         */
        $mapping = [
            0 => 'exerciseround',
        ];
        for ($i = 1; $i <= 30; ++$i) {
            $mapping[$i] = 'exercise' . $i;
        }
        return $mapping;
    }
}
