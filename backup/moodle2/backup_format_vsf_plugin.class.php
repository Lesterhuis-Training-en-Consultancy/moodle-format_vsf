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
 * Specialised backup for Progress Section course format.
 *
 * @package   format_vsf
 * @category  backup
 * @copyright &copy; 2022-onwards G J Barnard in respect to modifications of standard topics format.
 * @copyright &copy; 2024-onwards RvD in respect to modifications for custom icons.
 * @copyright 2017 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised backup for Progress Section course format.
 *
 * Processes 'numsections' from the old backup files and hides sections that used to be "orphaned".
 *
 * @package   format_vsf
 * @category  backup
 * @copyright &copy; 2022-onwards G J Barnard in respect to modifications of standard topics format.
 * @copyright &copy; 2024-onwards RvD in respect to modifications for custom icons.
 * @copyright 2017 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_vsf_plugin extends backup_format_plugin {

    /**
     * Define course plugin structure for format_vsf
     *
     * @return array|void
     */
    protected function define_course_plugin_structure() {
        global $DB;
        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'vsf');

        // Create one standard named plugin element (the visible container).
        // The courseid not required as populated on restore.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // We'll simply do ALL installed modules.
        // It will be too much hassle to see what we may wish to include or not,
        // because this is "high level behaviour". Customizing icons also will not
        // see what may or may not be wishful. Custom icons is NOT dependent on
        // which modules are in use in the course.
        // Customisation can be done for ALL activity types.
        $courseiconwrapper = new backup_nested_element('courseicons');
        $courseicon = new backup_nested_element('courseicon', [], ['name']);
        $sql = 'SELECT DISTINCT m.name FROM {modules} m';
        $params = [];
        $courseicon->set_source_sql($sql, $params);

        $pluginwrapper->add_child($courseiconwrapper);
        $courseiconwrapper->add_child($courseicon);

        // Task context is course context.
        $ctxid = $this->task->get_contextid();
        $modules = $DB->get_fieldset_sql($sql, $params);
        foreach ($modules as $module) {
            $pluginwrapper->annotate_files('format_vsf', 'modicon_' . $module, null, $ctxid);
        }

        return $plugin;
    }

    /**
     * Define module plugin structure for format_vsf
     *
     * @return array|void
     */
    protected function define_module_plugin_structure() {
        // We'll start off by detecting whether we WANT to include a customicon.
        // In other words: is there a file at all?
        $actid = $this->task->get_activityid();
        $modulecontextid = $this->task->get_contextid();

        $fs = get_file_storage();
        $files = $fs->get_area_files($modulecontextid, 'format_vsf', 'modicon', 0, 'itemid', false);
        if (count($files) == 0) {
            // No custom icons. Break early.
            return;
        }

        // We have a file, so we'll add the "base essentials": itemid (0), module name and ORIGINAL CONTEXT ID.
        // This seems unnatural, but we're hooking into the MODULE.
        // This is not the same as the activity, which holds and stores the original context.
        // However, this context is not known on the restore process callback.
        // See the restore class for the rest of the details.

        // Define/get the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'vsf');

        // Create one standard named plugin element (the visible container) using the base recommended name.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        // Connect to container.
        $plugin->add_child($pluginwrapper);

        // Create an element for the modicon. Since half the information I expect is NOT present in restore,
        // such as the CRITICAL OLD CONTEXT we need for simple file restores, we'll add that here.
        // Please note we "hardcode" the source data, which we absolutely require to make this work.
        // GAWD, I hate backup/restore. Unclear, messy, lacking. Just... plain... wrong.
        // ELOY, YOUR IMPLEMENTATION SUCKS!
        $customicon = new backup_nested_element('modicon', [], ['itemid', 'modname', 'oldctxid']);
        $customicon->set_source_array([[
            'itemid' => 0,
            'modname' => $this->task->get_modulename(),
            'oldctxid' => $modulecontextid,
        ]]);
        // Connect to conainer.
        $pluginwrapper->add_child($customicon);

        // And finally, we can annotate the file ids so they'll be included in the MBZ.
        $customicon->annotate_files('format_vsf', 'modicon', null, $modulecontextid);

        return $plugin;
    }

}
