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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>;.
/**
 * Class datareport
 *
 *
 * @package   report_puconline
 * @copyright 2020 CCEAD PUC-Rio <angela@ccead.puc-rio.br>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_puconline\local;

use core_availability\info_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to fetch data from the database
 *
 * @package report_puconline
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */
class datareport {
    
    public static function fetch_data_report($user , $category) {
        
        global $CFG;    
        
        require_once("$CFG->libdir/gradelib.php");
        
        $data = new \stdClass();
        
        
        if (is_object($user) and is_object($category)) {
        
            $courses = self::get_courses($user->id, $category->id);
            
            if ($courses) {
                $_courses = array();
                $totalcourses = count($courses);
                
                foreach ($courses as $course) {
                    
                    $groups = self::get_groups($course->courseid, $user->id);
                    
                    $listgroup = array();
                    foreach ($groups as $group) {
                        $listgroup[] = $group->groupname;
                    }
                    
                    $modules = self::get_modules_course($course->courseid, $user->id);
                    
                    $course->groupsname = implode(",", $listgroup);
                    $course->modules = $modules;
                                    
                    $_courses[] = $course;
                }
                
                $data->userid = $user->id;
                $data->userfullname = fullname($user);
                $data->profilelink = new \moodle_url('/user/view.php', array('id' => $user->id));
                $data->username = $user->username;
                $data->periodo = substr($course->categoryname, -6);
                $data->datereport = date("d/m/Y H:i");
                $data->totalcourses = $totalcourses;
                $data->courses = $_courses;
            }
        }
        
        return $data;
        
    }

    /**
     * Get a array of groups of user in a course
     * 
     * @param int $courseid
     * @param int $userid
     * @return array [groupid, groupname]
     */
    protected static function get_groups($courseid, $userid) {
        global $DB;
        $sql = "SELECT g.id groupid, g.name groupname
                FROM {groups} g 
                	INNER JOIN {groups_members} gm ON g.id = gm.groupid
                WHERE g.courseid = :courseid
                AND gm.userid = :userid
                ORDER BY groupname";
        return $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        
    }
    
    /**
     * Get all courses from category to the user
     * 
     * @param int $userid
     * @param int $categoryid
     * @return \stdClass [courseid, coursefullname, categoryname, userid, username, userfullname, userstatusname, dateenrol, finalgrade]
     */
    protected static function get_courses($userid, $categoryid) {
        global $DB;
        $sql = "SELECT c.id courseid, c.shortname, c.fullname coursefullname, c.category, cat.name categoryname,
                	ue.userid, ue.status,
                	case when ue.status = 0 then 'ATIVO' ELSE 'SUSPENSO' END userstatusname,
                	FROM_UNIXTIME(CASE WHEN ue.timestart = 0 THEN ue.timecreated ELSE ue.timestart END, '%d/%m/%Y %H:%i') dateenrol,
                    gg.finalgrade
                FROM {course} c
                	INNER JOIN {course_categories} cat ON c.category = cat.id
                	INNER JOIN {enrol} e ON c.id = e.courseid
                	INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
                    LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                	LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = ue.userid
                WHERE c.visible = 1
                AND ue.userid = :userid
                AND c.category = :categoryid
                ORDER BY c.fullname";
        return $DB->get_records_sql($sql, ['userid' => $userid, 'categoryid' => $categoryid]);
    }
    

    /**
     * Get all modules from course and user
     * 
     * @param int $courseid
     * @param int $userid
     * @return array \stdClass[]
     *      $mod->cmid
     *      $mod->modulename e.g. 'forum'
     *      $mod->cmname
     *      $mod->icon_url
     *      $mod->grande
     *      $mod->outlineinfo
     */
    protected static function get_modules_course($courseid, $userid) {
        
        global $DB, $CFG;
        
        $modinfo = get_fast_modinfo($courseid, $userid);
        $cms = $modinfo->get_cms();        
        
        $modules = array();
        
        foreach ($cms as $cm) {
            
            if (! info_module::is_user_visible($cm->id, $userid)) {
                continue;
            }
            
            $mod = new \stdClass();
            $mod->cmid = $cm->id;
            $mod->modulename = $cm->modfullname; // e.g. Fórum
            $mod->cmname = $cm->name; // e.g. Notícias e Avisos
            $mod->moduletypename = $cm->modname; // e.g. forum
            $mod->icon_url = $cm->get_icon_url();            
            
            $nota = false;
            $gradesinfo = grade_get_grades($courseid, 'mod', $cm->modname, $cm->instance, $userid);
            
            if ($gradesinfo && $gradesinfo->items){
                foreach ($gradesinfo->items as $gradeitem) {
                    foreach ($gradeitem->grades as $userid => $grade) {
                        //$nota = $grade->grade; // formato: 90
                        $nota = $grade->str_long_grade; // formato: 90,00 / 100,00
                   }
                }
            }
            
            $mod->grade = ($nota)? $nota : '-';
            
            $instance = $DB->get_record("$cm->modname", array("id" => $cm->instance));
            
            $libfile = "$CFG->dirroot/mod/$cm->modname/lib.php";
            $locallibfile = "$CFG->dirroot/report/outline/locallib.php";
            
            
            if (file_exists($libfile)) {
                
                require_once($libfile);
                require_once($locallibfile);
                
                $user_outline = $cm->modname."_user_outline";
                
                if (function_exists($user_outline)) {
                    $user = $DB->get_record("user", array("id" => $userid));
                    $course = $DB->get_record("course", array("id" => $courseid));
                    $output = $user_outline($course, $user, $cm, $instance);
                } else {
                    $output = report_outline_user_outline($userid, $cm->id, $cm->modname, $cm->instance);
                }
                
                if (!empty($output->time)) {
                    $timeago = format_time(time() - $output->time);
                    $mod->lastaccess = userdate($output->time, '%d/%m/%Y')." ($timeago)";
                }
                
                if (!empty($output->info)) {
                    $string_grade = get_string('grade') . ': ' . $mod->grade;
                    $mod->info = str_replace($string_grade, '', $output->info);
                }
                
            }
            
            $modules[] = $mod;
            
            unset($mod);            
        }
        return $modules;
    }
    
    
}