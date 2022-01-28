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
 * Public API of the PUC ONLINE report.
 *
 * Defines the APIs used by PUC ONLINE reports
 *
 * @package    report_puconline
 * @copyright  2020 CCEAD PUC-Rio (angela@ccead.puc-rio.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_puconline\datareport;

defined('MOODLE_INTERNAL') || die;

define('STUDENT_PER_PAGE', 100);

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function report_puconline_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    
    if (has_capability('report/puconline:view', context_user::instance($user->id))) {
        
        $url = new moodle_url('/report/puconline/index.php', array('userid' => $user->id));
        $node = new core_user\output\myprofile\node('reports', 'puconline', get_string('pluginname', 'report_puconline'), null, $url);
        $tree->add_node($node); 
        
    }
       
}


/**
 * 
 * @param int $categoryid
 * @param boolean $verbose
 * @return boolean
 */
function report_puconline_bulk_pdf(int $categoryid, $verbose = false, int $page = -1) {
    global $DB, $COURSE, $PAGE;
    
    $is_paginated = ($page > 0 )? true: false;
    
    $category = $DB->get_record('course_categories', array('id' => $categoryid), '*', IGNORE_MISSING);
    
    if (!$category) {
        if ($verbose) {
            mtrace('Error: Invalid category. Try again.');
        }
        return false;
    }
    
    $students = \report_puconline\local\datareport::get_students($category->id);    
   
    if (!$students) {
        if ($verbose) {
            mtrace(date('Y-m-d H:i:s') .' - No students found...');
        }        
        return false;
    } else {
        
        $count_students = count($students);
        
        if ($is_paginated) {
            
            $totalpage = ceil($count_students/STUDENT_PER_PAGE);
            
            if ( !(($page >= 1) and ($page <= $totalpage)) ) {
                if ($verbose) {
                    mtrace('Error: The page must be in the range 1 - ' . $totalpage);
                }
                return false;
            } else {
                $index_start = (($page - 1) * STUDENT_PER_PAGE) + 1;
                $index_end = $index_start + (STUDENT_PER_PAGE - 1);
            }
        }
        
        if ($verbose) {
            mtrace(date('Y-m-d H:i:s') . ' Start \''. get_string('pluginname', 'report_puconline') . '\' for '. 
                $count_students . ' students in PDF format [Category: ' . $category->id . ' - ' . $category->name. ']') ;
        }
    }
    
    // Display page.
    $PAGE->set_course($COURSE);
    $PAGE->set_heading(get_string('pluginname', 'report_puconline'));
    $PAGE->set_pagelayout('print');
    $PAGE->set_title(get_string('pluginname', 'report_puconline'));
    $renderer = $PAGE->get_renderer('report_puconline');
    
    $i = 0;
    
    foreach ($students as $s) {
        
        ++$i;
        
        if ($is_paginated) {
            if (!($i >= $index_start and $i <= $index_end)) {
                continue;
            }
        }
        
        if ($verbose) {
            mtrace('  student ' . $i . '/' . $count_students . ': ' . $s->username . '-' . $s->firstname . ' ' . $s->lastname);
        }
        if ($data_report = $renderer->pdf_report($s, $category)) {
            create_pdf($data_report, $s, $category);
        } else {
            if ($verbose) {
                mtrace('  ' . $s->username . '-' . $s->firstname . ' ' . $s->lastname . ' no data.');
            }
        }
    }
    
    if ($verbose) {
        mtrace(date('Y-m-d H:i:s') . ' Finish');
    }
       
    return true;

}

function create_pdf($data_report, $user, $category) {
    
    global $CFG;    
    //require_once($CFG->libdir . '/tcpdf/config/tcpdf_config.php');
    
    $categoryname = substr($category->name, 11);
    $path = $CFG->dataroot . DIRECTORY_SEPARATOR. 'jornada';
    $filename = $category->name . '-' .  $user->username . '_' . date("Ymd").'.pdf';
    $filename = $path . DIRECTORY_SEPARATOR. $filename;    
    
    $fontfamily = 'helvetica'; //PDF_FONT_NAME_MAIN;
    
    ob_start();
    
    $doc = new report_puconline\local\pdf_puconline;
    $title = get_string('pluginname','report_puconline');
    $doc->SetTitle($title);
    $doc->SetAuthor('Moodle ' . $CFG->release);
    $doc->SetCreator('PUC-Rio');
    $doc->SetSubject('This has been generated by Moodle as its PDF library');
    $doc->SetMargins(10, 20);
    
    $doc->setPrintHeader(true);
    $doc->setHeaderMargin(5);
    
    $doc->setPrintFooter(true);
    $doc->setFooterMargin(10);
    $doc->setFooterFont(array($fontfamily, '', 8));
    
    $doc->AddPage();
    $doc->SetFontSize(8);
    
    $doc->writeHTML($data_report);
    
    ob_end_clean();
    $doc->Output($filename, 'F');    
    unset($doc);
}

