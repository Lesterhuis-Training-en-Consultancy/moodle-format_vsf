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
 * Progress Section Format
 *
 * Contains the default section controls output class.
 *
 * @package    format_vsf
 * @copyright  &copy; 2021-onwards G J Barnard in respect to modifications of standard topics format, i.e:
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace format_vsf\output\courseformat\content;

use context_course;
use core_courseformat\base as course_format;
use core_courseformat\output\section_renderer;
use stdClass;

/**
 * Base class to render a course add section navigation.
 */
class sectionnavigation extends \core_courseformat\output\local\content\sectionnavigation {

    /** @var stdClass the calculated data to prevent calculations when rendered several times */
    private $data = null;

    /** @var section_renderer the section renderer */
    private $sectionrenderer = null;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param int $sectionno the section number
     */
    public function __construct(course_format $format, int $sectionno, section_renderer $sectionrenderer) {
        parent::__construct($format, $sectionno);
        $this->sectionrenderer = $sectionrenderer;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        global $USER;

        if ($this->data !== null) {
            return $this->data;
        }

        $format = $this->format;
        $course = $format->get_course();
        $context = context_course::instance($course->id);

        $modinfo = $this->format->get_modinfo();
        $sections = $modinfo->get_section_info_all();

        $linkicons = $this->sectionrenderer->vsf_get_nav_link_icons();

        // FIXME: This is really evil and should by using the navigation API.
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context, $USER);

        $data = (object)[
            'hasnext' => false,
            'nextclasses' => $linkicons['next'],
            'nexthidden' => false,
            'nextname' => '',
            'nexturl' => '',
            'previousclasses' => $linkicons['previous'],
            'previousurl' => '',
            'currentsection' => $this->sectionno,
        ];

        $back = $this->sectionno - 1;
        while ($back >= 0 && empty($data->previousurl)) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                if (!$sections[$back]->visible) {
                    $data->previoushidden = true;
                }
                $data->previousname = get_section_name($course, $sections[$back]);
                $data->previousurl = course_get_url($course, $back);
                $data->hasprevious = true;
            }
            $back--;
        }

        $forward = $this->sectionno + 1;
        $numsections = course_get_format($course)->get_last_section_number();
        while ($forward <= $numsections && empty($data->nexturl)) {
            if ($canviewhidden || $sections[$forward]->uservisible) {
                if (!$sections[$forward]->visible) {
                    $data->nexthidden = true;
                }
                $data->nextname = get_section_name($course, $sections[$forward]);
                $data->nexturl = course_get_url($course, $forward);
                $data->hasnext = true;
            }
            $forward++;
        }

        $this->data = $data;
        return $data;
    }
}
