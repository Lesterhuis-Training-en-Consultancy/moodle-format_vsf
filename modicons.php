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
 * Global icon modification
 *
 * This userpage represents the mod icons that can be adjusted on global level.
 * Under normal conditions, one would use the core/theme icons for activity modules,
 * but this userpage allows for a completely separate method.
 * Inherent to applicable scopes, the icons require special functionality, hence
 * will only be available for format_vsf.
 *
 * What does this do? In simple terms:
 * - display a form with one upload field per activity _type_.
 * - store each (btw completely optional) image in mdl_files under SYSCONTEXT with
 *   component 'format_vsf', filearea 'modicon_<mod>', itemid 0.
 * Please note the filearea specialisation!
 *
 * @package     format_vsf
 * @copyright   2022 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

admin_externalpage_setup('vsfmodicons');
$renderer = $PAGE->get_renderer('format_vsf');

$returnto = optional_param('return', null, PARAM_LOCALURL);
if (empty($returnto)) {
    $posturl = $returnto = new moodle_url($PAGE->url);
} else {
    $posturl = new moodle_url($PAGE->url, ['return' => $returnto]);
}

$form = new format_vsf\local\modicon\iconsform($posturl, ['courseid' => SITEID]);
if ($form->is_cancelled()) {
    redirect($returnto);
} else if ($form->save_data()) {
    \core\notification::success(get_string('modicons:global:changes:saved', 'format_vsf'));
    redirect($returnto);
}

echo $renderer->header();
echo $renderer->heading(get_string('modicon:image:defaults:head', 'format_vsf'));
echo html_writer::div(get_string('modicon:image:defaults:desc', 'format_vsf'), 'alert alert-info');
echo $form->render();
echo $renderer->footer();
