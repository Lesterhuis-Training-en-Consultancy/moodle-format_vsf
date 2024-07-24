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
 * Specialised restore for Progress Section course format.
 *
 * @package   format_vsf
 * @category  backup
 * @copyright &copy; 2022-onwards G J Barnard in respect to modifications of standard topics format.
 * @copyright &copy; 2024-onwards RvD in respect to modifications for custom icons.
 * @copyright 2017 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised restore for Progress Section course format.
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
class restore_format_vsf_plugin extends restore_format_plugin {

    /**
     * Holds data objects that refer to custom module instance icons.
     *
     * @var array
     */
    protected static $modicons = [];

    /** @var int */
    protected $originalnumsections = 0;

    /**
     * Checks if backup file was made on Moodle before 4.0 and we should respect the 'numsections'
     * and potential "orphaned" sections in the end of the course.
     *
     * @return bool
     */
    protected function need_restore_numsections() {
        $backupinfo = $this->step->get_task()->get_info();
        $backuprelease = $backupinfo->backup_release; // The major version: 2.9, 3.0, 3.10...
        return version_compare($backuprelease, '4.0', '<');
    }

    /**
     * Creates a dummy path element in order to be able to execute code after restore.
     *
     * @return restore_path_element[]
     */
    public function define_course_plugin_structure() {
        global $DB;

        // Since this method is executed before the restore we can do some pre-checks here.
        // In case of merging backup into existing course find the current number of sections.
        $target = $this->step->get_task()->get_target();
        if (($target == backup::TARGET_CURRENT_ADDING || $target == backup::TARGET_EXISTING_ADDING) &&
                $this->need_restore_numsections()) {
            $maxsection = $DB->get_field_sql(
                    'SELECT max(section) FROM {course_sections} WHERE course = ?',
                    [$this->step->get_task()->get_courseid()]);
            $this->originalnumsections = (int) $maxsection;
        }

        $paths = [];
        // Since this method is executed before the restore we can do some pre-checks here.
        // In case of merging backup into existing course find the current number of sections.
        // We will ONLY perform the specifics if we're NOT importing etc etc.
        $allowrestore = [backup::TARGET_NEW_COURSE];
        if (in_array($target, $allowrestore)) {
            // We'll restore :).
            $elename = 'courseicon'; // This defines the postfix of 'process_*' below.
            $elepath = $this->get_pathfor('/courseicons/courseicon');
            $paths[] = new restore_path_element($elename, $elepath);
        }

        // Dummy path element is needed in order for after_restore_course() to be called.
        return array_merge(
                [new restore_path_element('dummy_course', $this->get_pathfor('/dummycourse'))],
                $paths
        );
    }

    /**
     * Dummy process method.
     *
     * @return void
     */
    public function process_dummy_course() {

    }

    /**
     * Executed after course restore is complete.
     *
     * This method is only executed if course configuration was overridden.
     *
     * @return void
     */
    public function after_restore_course() {
        $this->vsf_after_restore_course_numsections();
        $this->vsf_after_restore_course_modicons();
    }

    /**
     * Restore numsections
     *
     * @return void
     */
    protected function vsf_after_restore_course_numsections() {
        global $DB;
        if (!$this->need_restore_numsections()) {
            // Backup file was made in Moodle 4.0 or later, we don't need to process 'numsecitons'.
            return;
        }

        $data = $this->connectionpoint->get_data();
        $backupinfo = $this->step->get_task()->get_info();
        if ($backupinfo->original_course_format !== 'vsf' || !isset($data['tags']['numsections'])) {
            // Backup from another course format or backup file does not even have 'numsections'.
            return;
        }

        $numsections = (int) $data['tags']['numsections'];
        foreach ($backupinfo->sections as $key => $section) {
            // For each section from the backup file check if it was restored and if was "orphaned" in the original
            // course and mark it as hidden. This will leave all activities in it visible and available just as it was
            // in the original course.
            // Exception is when we restore with merging and the course already had a section with this section number,
            // in this case we don't modify the visibility.
            if ($this->step->get_task()->get_setting_value($key . '_included')) {
                $sectionnum = (int) $section->title;
                if ($sectionnum > $numsections && $sectionnum > $this->originalnumsections) {
                    $DB->execute("UPDATE {course_sections} SET visible = 0 WHERE course = ? AND section = ?",
                        [$this->step->get_task()->get_courseid(), $sectionnum]);
                }
            }
        }
    }

    /**
     * Creates a dummy path element in order to be able to execute code after restore
     *
     * @return restore_path_element[]
     */
    public function define_module_plugin_structure() {
        // Since this method is executed before the restore we can do some pre-checks here.
        // In case of merging backup into existing course find the current number of sections.
        $target = $this->step->get_task()->get_target();

        $paths = [];
        // We will ONLY perform the specifics if we're NOT importing etc etc.
        $allowrestore = [backup::TARGET_NEW_COURSE, backup::TARGET_CURRENT_ADDING, backup::TARGET_EXISTING_ADDING];
        if (in_array($target, $allowrestore)) {
            // We'll restore :).
            $elename = 'modicon'; // This defines the postfix of 'process_*' below.
            $elepath = $this->get_pathfor('/modicon');
            $paths[] = new restore_path_element($elename, $elepath);
        }

        return $paths;
    }

    /**
     * Process course level icon customisations.
     */
    public function process_courseicon($data) {
        if (!is_object($data)) {
            $data = (object) $data;
        }
        $modname = $data->name;
        $filearea = "modicon_{$modname}";
        $itemid = 0;
        $mappingitemname = null;
        $filesctxid = $this->task->get_old_contextid();
        $this->add_related_files('format_vsf', $filearea, $mappingitemname, $filesctxid, $itemid);
    }

    /**
     * Process module level icon customisations.
     */
    public function process_modicon($data) {
        if (!is_object($data)) {
            $data = (object) $data;
        }
        // Data, as stated in the backup class, holds the itemid, module name and original context.
        // The reason for the original context is because at this stage, we DO NOT have
        // an original context.
        // It is due to $this->task->get_old_contextid() returning 0, consequently failing $this->add_related_files().
        // The only way to get this right now, is to hook into "after_restore_course()".
        // We can achieve this by filling a STATIC property (we CANNOT use an instance property, it WILL fail) with the data object.
        // Then, in "after_restore_course()", we can simply call "add_related_files()" with the correct data.

        // Add data object to static property for use later (see below).
        static::$modicons[] = $data;
    }

    /**
     * Restore the modicons that were added by this/in format.
     */
    protected function vsf_after_restore_course_modicons() {
        // For all the data objects that were added in "process_modicon()",
        // try to add the related files.
        foreach (static::$modicons as $data) {
            $filearea = "modicon";
            $itemid = 0;
            $mappingitemname = null;
            $filesctxid = $data->oldctxid;
            $this->add_related_files('format_vsf', $filearea, $mappingitemname, $filesctxid, $itemid);
        }
    }

}
