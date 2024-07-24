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

$cmid = required_param('id', PARAM_INT);
$sr = optional_param('sr', null, PARAM_INT);
$context = context_module::instance($cmid);

$params = ['id' => $cmid];
if ($sr !== null) {
    $params['sr'] = $sr;
}
$url = new moodle_url('/course/format/vsf/modicon.php', $params);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
require_login($course, false, $cm);

$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
// DISABLE specific activity header (which injects completion/description etc).
$PAGE->activityheader->disable();
// JIT injection of secondary navigation (dunno how to do this otherwise).
$PAGE->secondarynav->add(get_string('modicon:cm', 'format_vsf'), $url);
$renderer = $PAGE->get_renderer('format_vsf');
$customdata = [
    'coursemodule' => $cm,
    'course' => $course,
    'context' => $context,
];

$rparams = ['id' => $course->id];
$anchor = (($sr === null) ? null : '!section' . $sr);
$redirect = new \moodle_url($CFG->wwwroot . '/course/view.php', $rparams, $anchor);

$form = new \format_vsf\local\modicon\editform($url, $customdata);
if ($form->is_cancelled()) {
    redirect($redirect);
} else if ($form->save_data()) {
    \core\notification::success(get_string('modicons:cm:changes:saved', 'format_vsf'));
    redirect($redirect);
}

$oneupurl = new moodle_url($CFG->wwwroot . '/course/format/vsf/courseicons.php', ['id' => $course->id]);

echo $renderer->header();
echo $renderer->heading(get_string('modicon:image:coursemodule:head', 'format_vsf',
        $cm->name));
echo html_writer::div(get_string('modicon:image:coursemodule:desc', 'format_vsf',
        $oneupurl->out(false)), 'alert alert-info');
echo $form->render();
echo $renderer->footer();
