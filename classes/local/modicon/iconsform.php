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
 * Icon form.
 *
 * @package     format_vsf
 * @copyright   2022 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

namespace format_vsf\local\modicon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/formslib.php");

/**
 * format_vsf\local\modicon\iconsform
 *
 * @package     format_vsf
 *
 * @copyright   2022 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iconsform extends \moodleform {
    /**
     * @var \stdClass
     */
    protected $course;
    /**
     * @var \context
     */
    protected $context;

    /**
     * Form definition.
     */
    protected function definition() {
        global $SITE;
        $mform = $this->_form;

        $courseid = $this->_customdata['courseid'];
        if ($courseid == SITEID) {
            $this->context = \context_system::instance();
            $this->course = $SITE;
        } else {
            $this->context = \context_course::instance($courseid);
            $this->course = get_course($courseid);
        }

        util::add_modules_form_elements($mform, $this->context, $this->course);

        $this->add_action_buttons();
    }

    /**
     * Process incoming data
     *
     * @return bool
     */
    public function save_data() {
        $rs = util::store_modules_form_elements($this, $this->context, $this->course);
        return $rs;
    }

}
