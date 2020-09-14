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
    
    public static function fetch_data_report ($user , $category) {
        
        global $CFG;    
        
        require_once("$CFG->libdir/gradelib.php");
        
        // iniciando variaveis.
        $userid = '';
        $userfullname = '';
        $username = '';
        $periodo = '';
        $datereport = '';
        $totalcourses = '';
        $_courses = array();  
        $_modules = array();
        
        if (is_object($user) and is_object($category)) {
        
            $courses = self::get_courses($user->id, $category->id);            
            
            $totalcourses = count($courses);
            
            foreach ($courses as $course) {
                $userid = $user->id;
                $userfullname = fullname($user);
                $username = $user->username;
                $periodo = substr($course->categoryname, -6);
                $datereport = date('d/m/Y');
                
                $groups = self::get_groups($course->id, $user->id);                
                $listgroup = array();
                foreach ($groups as $group) {
                    $listgroup[] = $group->groupname;
                }

                $modules = self::get_modules_info($user->id, $course->id);
                
                unset($_modules);

                foreach ($modules as $cm) {
                    
                    if (! info_module::is_user_visible($cm->cmid, $user->id)) {
                        continue;
                    }                    
                    
                    // Pega dados outline
                    $outline = self::get_module_outline($course, $user, $cm->cmid);
                    
                    if ($outline['info']!=='') {
                        
                        $fields = explode(', ', $outline['info']);
                        //echo '<pre><br>fields: '; print_r($fields); echo '</pre>';                        
                        
                        $arraygrade = array_filter($fields, function($field){
                            return ( strpos($field, get_string('grade')) !== false );
                        });
                        
                        $grade_outline = $arraygrade[array_key_first($arraygrade)];
                        
                        $novo = array_diff_key($fields, $arraygrade);
                        $info_outline = implode(', ', $novo);
                        //echo '<pre><br>$grade_outline: ';print_r($grade_outline); echo '</pre>';
                        //echo '<pre><br>$info_outline: ';print_r($info_outline); echo '</pre>';
                        
                        //echo '<pre><br>fields: ';print_r($fields); echo '</pre>';
                        //echo '<pre><br>grade outline: ';print_r($grade_outline); echo '</pre>';
                    }
                    
                    if (!$cm->grademax == '') {
                        $grade = number_format((float)$cm->finalgrade, 2, '.', ''); 
                        $grade .= ' / ' . number_format((float)$cm->grademax, 2, '.', '');
                    }
                    else $grade = '-';
                    
                    $_modules[] = array(
                        'cmid' => $cm->cmid,
                        'modulename' => $cm->modulename,
                        'cminstanceid' => $cm->cminstanceid,
                        'cmname' => $cm->cmname,
                        'lastaccess' => ($cm->lastaccess)? ($cm->lastaccess) : '-',
                        'finalgrade' => $grade,
                        'info' => $info_outline,
                        'hits' => ($cm->hits)?($cm->hits):'-');
                }           
                
                $_courses[] = array(
                    'courseid' => $course->id,
                    'coursefullname' => $course->fullname,
                    'dateenrol' => $course->dateenrol,
                    'userstatusname' => $course->statusname,
                    'groupsname' => implode(",", $listgroup),
                    'finalgrade' => $course->finalgrade,
                    'modules' => $_modules
                );
            }        
        }
        
        $data = new \stdClass();
        $data->userid = $userid;        
        $data->userfullname = $userfullname;
        $data->username = $username;
        $data->periodo = $periodo;
        $data->datereport = $datereport;
        $data->totalcourses = $totalcourses;        
        $data->courses = $_courses;        
        
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
     * @return array [courseid, coursefullname, categoryname, userid, username, userfullname, userstatusname, dateenrol, finalgrade]
     */
    protected static function get_courses($userid, $categoryid) {
        global $DB;
        $sql = "SELECT c.id, c.shortname, c.fullname, c.category, cat.name categoryname,
                	ue.userid, ue.status,
                	case when ue.status = 0 then 'ATIVO' ELSE 'SUSPENSO' END statusname,
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
     * Get all modules visible for a user in the course
     * 
     * @param int $userid
     * @param int $courseid
     * @return array of objct cmid, modulename, cminstanceid, cmname, 
     *                  section, setcionname, sequence, ordersequence,
     *                  lastacess, hits
     */
    protected static function get_modules_info($userid, $courseid) {
        
        global $DB;
        
        $sql_mod_from = '';
        $sql_mod_select = '';
        
        $modules = $DB->get_fieldset_select('modules', 'name', ''); 
        
        for ($i = 0; $i < count($modules); $i++) {
            $alias = 'm'.$i;
            $table = '{'. $modules[$i] .'}';
            $sql_mod_select .= "        WHEN $alias.name IS NOT NULL THEN $alias.name ";
            $sql_mod_from .= "    LEFT JOIN $table AS $alias ON $alias.course = cm.course AND $alias.id = cm.instance AND m.name = \"$modules[$i]\" ";
        }
        
        $sql_select = " ";
        $sql_select.= "SELECT DISTINCT cm.id cmid, m.name modulename, cm.instance cminstanceid, ";
        $sql_select.= "   CASE $sql_mod_select ";
        $sql_select.= "    END cmname, ";
        $sql_select.= "    cs.section, cs.name sectionname, cs.sequence, INSTR( concat(',',cs.sequence, ','), concat(',', cm.id, ',')) ordersequence, ";
        $sql_select.= "    l.lastaccess, l.hits, gg.finalgrade, gi.grademax ";
        
		$sql_from = " ";
		$sql_from.= " ";
		$sql_from.= "FROM {course_modules} cm  ";
		$sql_from.= "    INNER JOIN {course_sections} cs ON cs.course = cm.course AND cm.section = cs.id ";
		$sql_from.= "    INNER JOIN {modules} m ON cm.module = m.id ";
		$sql_from.= "    INNER JOIN {context} AS ctx ON ctx.contextlevel = 70 AND ctx.instanceid = cm.id ";
		$sql_from.= "	$sql_mod_from ";
		$sql_from.= "    LEFT JOIN ( ";
		$sql_from.= "    		  SELECT l1.contextinstanceid cmid, l1.courseid, l1.userid, ";
		$sql_from.= "    		  		FROM_UNIXTIME(MAX(l1.timecreated), '%d/%m/%Y %H:%i') lastaccess, ";
		$sql_from.= "    				COUNT(l1.contextinstanceid) hits ";
		$sql_from.= "    		  FROM {logstore_standard_log} l1 ";
		$sql_from.= "    		  WHERE l1.contextlevel = 70 AND l1.userid = :userid ";
		$sql_from.= "    		  GROUP BY 1,2,3 ";
		$sql_from.= "    		  ) l  ON l.cmid = cm.id  ";
		$sql_from.= "    LEFT JOIN mdl_grade_items gi ON gi.itemtype = 'mod' AND gi.itemmodule = m.name AND gi.courseid = l.courseid AND gi.iteminstance = cm.instance ";
		$sql_from.= "    LEFT JOIN mdl_grade_grades gg ON gg.itemid = gi.id AND l.userid = gg.userid ";

        $sql_where = " ";
        $sql_where.= "WHERE cm.course = :courseid ";
        $sql_where.= "	AND cm.visible = 1 ";
        $sql_where.= "  AND cs.visible = 1 ";
        $sql_where.= "	AND cm.deletioninprogress = 0 ";
        $sql_where.= "	AND m.name NOT IN ('label') ";
        $sql_where.= "ORDER BY cm.course, cs.section, ordersequence";
        
    	$sql = $sql_select. $sql_from . $sql_where;
    	$params = array('userid' => $userid, 'courseid' => $courseid);
    	
    	return $DB->get_records_sql($sql, $params);
        
    }
    

    
    protected static function get_module_outline($course, $user, $cmid) {
        
        global $DB, $CFG;        
        
        $mod = self::get_module_from_cmid($cmid);
        $instance = $DB->get_record("$mod->modulename", array("id"=>$mod->instanceid));
        $libfile = "$CFG->dirroot/mod/$mod->modulename/lib.php";
        $locallibfile = "$CFG->dirroot/report/outline/locallib.php";
        
        if (file_exists($libfile)) {            
            require_once($libfile);
            require_once($locallibfile);
            
            $user_outline = $mod->modulename."_user_outline";
            
            if (function_exists($user_outline)) {
                $output = $user_outline($course, $user, $mod, $instance);
            } else {
                $output = report_outline_user_outline($user->id, $cmid, $mod->modulename, $mod->instanceid);
            }            
            
            $module = array(
                'cmid' => $cmid,
                'modulename' => $mod->modulename,
                'instanceid' => $mod->instanceid,
                'instancename' => $mod->instancename,
                'info' => $output->info,
                'lastaccess' => $output->lastaccess
                );                                

            return $module;   
        }
        
    }
    
    /**
     * retorna um objeto de course_modules com nome da instancia 
     * @param int $cmid
     * @return \stdClass
     */
    protected static function get_module_from_cmid($cmid) {
        global $DB;
        
        $sql = "SELECT cm.*, md.name as modname
               FROM {course_modules} cm,
                    {modules} md
               WHERE cm.id = :cmid AND
                     md.id = cm.module";
        $params = array('cmid' => $cmid );
        $result = $DB->get_record_sql($sql, $params);
        
        $cm = new \stdClass();
        $cm->id = $result->id;
        $cm->course = $result->course;
        $cm->section = $result->section;
        $cm->visible = $result->visible;
        $cm->moduleid = $result->module;
        $cm->modulename = $result->modname;
        $cm->instanceid = $result->instance;
        
        $instance = $DB->get_record($cm->modulename, array('id' => $cm->instanceid));
        
        $cm->instancename = $instance->name;        
        
        return $cm;
    }
    
    
    
}






