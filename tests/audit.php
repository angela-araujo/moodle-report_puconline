<?php 
use report_puconline\local\datereport;
use core_availability\info_module;

require('../../../config.php');
require ($CFG->dirroot.'/report/puconline/classes/local/filter_form.php');
require ($CFG->dirroot.'/report/outline/locallib.php');
?>
<style>
table {
    border-collapse: collapse;
}
table, td {
    border: solid 1px;
}
</style>
<pre>
<?php 


clearstatcache();

global $DB;

global $CFG;
require_once("$CFG->libdir/gradelib.php");

$courseid = 3;
$userid = 5;

$modinfo = get_fast_modinfo($courseid, $userid);
$cms = $modinfo->get_cms();
$cm->get_course_module_record();


echo '<h1>ANGEL</h1><pre>';
print_r($cm);

/*
echo '<h2>TESTE</h2>';
foreach ($modinfo as $objeto) {
    echo 'objeto: <br>';
    print_r($objeto);
}*/

exit;


function getcourses($userid, $categoryid){
    global $DB;
    $sqlaluno = "SELECT c.id, c.shortname, c.fullname, c.category, cat.name categoryname,
                    	ue.userid, ue.status,
                    	case when ue.status = 0 THEN 'ATIVO' ELSE 'SUSPENSO' END statusname,
                    	FROM_UNIXTIME(CASE WHEN ue.timestart = 0 THEN ue.timecreated ELSE ue.timestart END, '%d/%m/%Y %H:%i') dateenrol,
                        gg.finalgrade
                    FROM {course} c
                    	INNER JOIN {course_categories} cat ON c.category = cat.id
                    	INNER JOIN {enrol} e ON c.id = e.courseid
                    	INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
                        LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                    	LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = ue.userid
                    WHERE ue.userid = :userid
                    AND c.category = :categoryid
                    ORDER BY c.fullname";
    $courses = $DB->get_records_sql($sqlaluno, ['userid' => $userid, 'categoryid' => $categoryid]);

    $user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0), '*', MUST_EXIST);

    foreach ($courses as $course) {
        echo '<h3>'. $course->shortname .'</h3>';
        $module = getmodules($course, $user);
        echo '<br>$module: '; print_r($module);
        echo '<br>';
    }
}



function getmodules($course, $user) {
    global $DB, $CFG;
    
    $modinfo = get_fast_modinfo($course, $user->id);
    $sections = $modinfo->get_section_info_all();
    
    foreach ($sections as $i => $section) {
        
        if ($section->uservisible) { // prevent hidden sections in user activity. Thanks to Geoff Wilbert!
            // Check the section has modules/resources, if not there is nothing to display.
            if (!empty($modinfo->sections[$i])) {
                
                foreach ($modinfo->sections[$i] as $cmid) {
                    
                    $mod = $modinfo->cms[$cmid];
                    
                    if (empty($mod->uservisible) or ($mod->modname == 'label')) {
                        continue;
                    }                    
                    
                    $instance = $DB->get_record("$mod->modname", array("id"=>$mod->instance));
                    $libfile = "$CFG->dirroot/mod/$mod->modname/lib.php";                    
                    
                    if (file_exists($libfile)) {
                        require_once($libfile);
        
                        $user_outline = $mod->modname."_user_outline";                   
                        
                        if (function_exists($user_outline)) {
                            $output = $user_outline($course, $user, $mod, $instance);
                        } else {
                            $output = report_outline_user_outline($user->id, $cmid, $mod->modname, $instance->id);
                        }                       
                    }
                    
                    //report_outline_print_row($mod, $instance, $output);
                    if (isset($output->info)) {
                        $info = "$output->info";
                    } else {
                        $info = "-";
                    }
                    $lastaccess = '';
                    
                    if (!empty($output->time)) {
                        $timeago = format_time(time() - $output->time);
                        $lastaccess = userdate($output->time, '%d/%m/%Y')." ($timeago)";
                    }
                    
                    $listmodules[] = array('modulename' => $mod->modname,
                        'cmid' => $cmid,
                        'moduleinstanceid' => $instance->id,
                        'moduleinstancename' => $instance->name,
                        'info' => $info,
                        'lastaccess' => $lastaccess,
                        'report_complete' => $complete
                    );
                }
                
                return $listmodules;
            }
            
        }
    }
    return '';
    
}

//print_r($result);

function get_outline_module($course, $user, $cmid) {
    
    global $DB, $CFG;
    
    $mod = get_module_from_cmid($cmid);
    
    $instance = $DB->get_record("$mod->name", array("id"=>$mod->instance));
    
    $libfile = "$CFG->dirroot/mod/$mod->name/lib.php";
    
    if (file_exists($libfile)) {
        require_once($libfile);
        
        $user_outline = $mod->name."_user_outline";
        
        if (function_exists($user_outline)) {
            $output = $user_outline($course, $user, $mod, $instance);
        } else {
            $output = report_outline_user_outline($user->id, $cmid, $mod->name, $mod->instance);
        }
        
        $info = (isset($output->info))? "$output->info": "-";
        $lastaccess = (!empty($output->time))? userdate($output->time, '%d/%m/%Y'): "-";
        
        $modules[] = array(
            'cmid' => $cmid,
            'modulename' => $mod->name,
            'instance' => $instance->id,
            'cmname' => $instance->name,
            'info' => $info,
            'lastaccess' => $lastaccess);
        
        return ($modules);
    }
    
}
function oldmodule(){
    
    $modules = $DB->get_fieldset_select('modules', 'name', '');
    
    //print_r($modules);
    
    echo '<br>';
    
    $sql_from_mod = '';
    $sql_selct_mod = '';
    
    for ($i = 0; $i < count($modules); $i++) {
        $alias = 'm' . $i;
        $table = '{' . $modules[$i] . '}';
        $sql_selct_mod .= "
            WHEN $alias.name IS NOT NULL THEN $alias.name ";
        $sql_from_mod .= "
        LEFT JOIN $table AS $alias ON $alias.course = c.id AND $alias.id = cm.instance AND m.name = \"$modules[$i]\" ";
    }
    
    
    $sql_select = "SELECT cm.id cmid, c.id courseid, c.fullname coursefullname, m.name modulename, cm.availability cmavailability, cs.section, cs.name sectionname,
        concat(',',cs.sequence, ',') sequence, cs.availability csavailability,
        REGEXP_INSTR( concat(',',cs.sequence, ','), concat(',', cm.id, ',')) ordersequence,
        CASE $sql_selct_mod
        END activityname,
        cm.groupmode, cm.groupingid, cm.completion, cm.completionview, cm.completionexpected, cm.availability,
        l.lastaccess, l.hits ";
    $sql_from = "
    FROM {course} c
        INNER JOIN {course_modules} cm ON c.id = cm.course
        INNER JOIN {course_sections} cs ON cs.course = cm.course AND cm.section = cs.id
        INNER JOIN {modules} m ON cm.module = m.id
        INNER JOIN {context} AS ctx ON ctx.contextlevel = 70 AND ctx.instanceid = cm.id
    	$sql_from_mod
        LEFT JOIN (
        		  SELECT l1.contextinstanceid cmid, l1.courseid, l1.userid,
        		  		FROM_UNIXTIME(MAX(l1.timecreated), '%d/%m/%Y %H:%i') lastaccess,
        				COUNT(l1.contextinstanceid) hits
        		  FROM {logstore_standard_log} l1
        		  WHERE l1.contextlevel = 70 AND l1.userid = 10
        		  GROUP BY 1,2,3
        		  ) l  ON l.cmid = cm.id
    ";
    	$sql_where = "
    WHERE c.category = 2
    	AND cm.visible = 1
        AND cs.visible = 1
    	AND cm.deletioninprogress = 0
    	AND m.name NOT IN ('label')
    ORDER BY c.id, cs.section, ordersequence";
    	
    	echo '<br>Query: <br>';
    	
    	$sql = $sql_select. $sql_from . $sql_where;
    	//print_r($sql);
    	
    	$result = $DB->get_records_sql($sql);
    	
    	$table = "<table>
        <tr>
            <td>courseid</td>
            <td>cmid</td>
            <td>modulename</td>
            <td>section</td>
            <td>activityname</td>
            <td>lastacess</td>
            <td>hits</td>
        </tr>";
    	
    	
    	foreach ($result as $obj=>$value){
    	    if (! info_module::is_user_visible($value->cmid, 10)) continue;
    	    
    	    $table .= " <tr>
            <td>$value->courseid</td>
            <td>$value->cmid</td>
            <td>$value->modulename</td>
            <td>$value->sectionname</td>
            <td>$value->activityname</td>
            <td>$value->lastaccess</td>
            <td>$value->hits</td>
        </tr>";
    	}
    	
    	$table .= "</table>";
    	echo '<hr>';
    	echo '<h2>Atividades/Recursos: </h2>';
    	echo $table;
    	
}

function get_module_from_cmid($cmid) {
    
    global $DB;
    
    $sql = "SELECT cm.*, md.name as modname
                               FROM {course_modules} cm,
                                    {modules} md
                               WHERE cm.id = :cmid AND
                                     md.id = cm.module";
    $params = array('cmid' => $cmid );
    $cmrec = $DB->get_record_sql($sql, $params);
    
    if ($module = $DB->get_record($cmrec->modname, array('id' => $cmrec->instance))) {
        $module->instance = $module->id;
        $module->cmid = $cmrec->id;
        $module->modname = $cmrec->modname;
    }

    return $module;
}


echo '<br><br>FIM!!!!!!!';
echo '<br><br>ACABOU AQUI---';
echo '<hr>';
?>
</pre>