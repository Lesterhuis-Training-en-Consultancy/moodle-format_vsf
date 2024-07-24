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
 * Icon modification for course format
 *
 * @package     format_vsf
 * @copyright   2022 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');

$id = required_param('id', PARAM_INT);
$view = required_param('v', PARAM_ALPHA);

switch ($view) {
    case 'cm':
        $context = \context_module::instance($id);
        list($course, $cm) = get_course_and_cm_from_cmid($id);
        break;
    case 'course':
        $context = \context_course::instance($id);
        $course = get_course($id);
        $cm = null;
        break;
}

$params = ['id' => $id, 'v' => $view];
if ($sr !== null) {
    $params['sr'] = $sr;
}
$url = new moodle_url('/course/format/vsf/iconused.php', $params);
require_login($course, false, $cm);

$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
// DISABLE specific activity header (which injects completion/description etc).
$PAGE->activityheader->disable();
$renderer = $PAGE->get_renderer('format_vsf');

$oneupurl = new moodle_url($CFG->wwwroot . '/course/format/vsf/courseicons.php', ['id' => $course->id]);
$widget = new format_vsf\local\modicon\overview($context);

echo $renderer->header();
echo $renderer->heading(get_string('modicon:usage', 'format_vsf'));
echo $renderer->render_from_template('format_vsf/local/modicons/overview',
        $widget->export_for_template($renderer));
echo $renderer->footer();
