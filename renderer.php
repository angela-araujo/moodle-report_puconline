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
 * Renderer for the report puconline.
 *
 *
 * @package   report_puconline
 * @copyright 2020 CCEAD PUC-Rio <angela@ccead.puc-rio.br>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class report_puconline_renderer extends plugin_renderer_base {
    
    public function display_report($records = null) {   
        
        // Start output to browser.
        echo $this->output->header();        
        
        $courses = array();
        
        if ($records) {

            foreach ($records->courses as $instancecourse) {
                
                $tablemodules = $this->print_table_modules_html($instancecourse['modules']);
                
                $courses[] = array(
                    'coursefullname' => $instancecourse['coursefullname'],
                    'dateenrol' => $instancecourse['dateenrol'],
                    'userstatusname' => $instancecourse['userstatusname'], 
                    'groupsname' => $instancecourse['groupsname'],
                    'finalgrade' => $instancecourse['finalgrade'],
                    'tablemodules' => $tablemodules);
            }
            
            //$tablehtml = $this->print_table_html($records->courses);
            
            $data->userfullname = $records->userfullname;
            //$data->userpix      = $this->output->user_picture($user, array('size' => 35));
            $data->profilelink = new moodle_url('/user/view.php', array('id' => $records->userid));
            $data->username     = $records->username;
            $data->periodo      = $records->periodo;
            $data->datereport    = $records->datereport;
            $data->totalcourses = $records->totalcourses;
            $data->courses      = $courses;
        }
        //    \core\dataformat::download_data($filename, $dataformat, $columns, $iterator);
        
        // Call our template to render the data.
        echo $this->render_from_template('report_puconline/overview', $data);
        
        // Finish the page.
        echo $this->output->footer();
    }
    
    /**
     * @todo funcao temporaria. Alterar enviar os dados para o template mustache ao inves de gerar aqui.
     * 
     * @param array $date array('courses'=> array('modules') )
     * @return string
     */
    private function print_table_html($data) {        
		
        global $OUTPUT;
        
        $tablecourseheader  = "<thead>";
        $tablecourseheader .= "	<th>Disciplina</th>";
        $tablecourseheader .= "	<th>Inscrição</th>";
        $tablecourseheader .= "	<th>Situação</th>";
        $tablecourseheader .= "	<th>Turma</th>";
        $tablecourseheader .= "	<th>Nota</th>";
        $tablecourseheader .= "</thead>";
		$tablecoursebody = '';
		
        $tablemoduleheader  = "<thead>";
        //$tablemoduleheader .= "	<th> </th>";
        $tablemoduleheader .= "	<th colspan=\"2\">Atividade/Recurso</th>";
        $tablemoduleheader .= "	<th>Último Acesso</th>";
        $tablemoduleheader .= "	<th>Hits</th>";
        $tablemoduleheader .= "	<th>Nota</th>";
        $tablemoduleheader .= "</thead>";		
        
		foreach ($data as $course) {
		    $grade = ( $course['finalgrade'] )?$course['finalgrade']: '-';
			$tablecoursebody .= "<tr>";
			$tablecoursebody .= "	<td>" . $course['coursefullname'] . "</td>";
			$tablecoursebody .= "	<td>" . $course['dateenrol']      . "</td>";
			$tablecoursebody .= "	<td>" . $course['userstatusname'] . "</td>";
			$tablecoursebody .= "	<td>" . $course['groupsname']     . "</td>";
			$tablecoursebody .= "	<td>" . $grade . "</td>";
			$tablecoursebody .= "</tr>";
			$tablecoursebody .= "<tr>";
			$tablecoursebody .= "	<td style=\"padding-right:0px; padding-left: 100px;\" colspan=\"5\">";
			
			$tablemodulebody = '';
			
			if (!$course['modules']) {
			    $tablecoursebody .= "	- </td>";
			   continue; 
			}
			foreach ($course['modules'] as $module) {
			    $image = $OUTPUT->image_icon('icon', $module['cmname'],'mod_'.$module['modulename']);
				$tablemodulebody .= "<tr>";
				$tablemodulebody .= "	<td class=\"col1\" valign=\"top\">" . $image . "</td>";
				$tablemodulebody .= "	<td class=\"col2\">" . $module['cmname']     . "</td>";
				$tablemodulebody .= "	<td class=\"col3\">" . $module['lastaccess'] . "</td>";
				$tablemodulebody .= "	<td class=\"col4\">" . $module['hits']       . "</td>";
				$tablemodulebody .= "	<td class=\"col5\">" . $module['finalgrade'] . "</td>";
				$tablemodulebody .= "</tr>";
			}
			$tablemodule = '<table class="table table-striped">' . $tablemoduleheader . $tablemodulebody . '</table>';
			
			$tablecoursebody .= $tablemodule . "	</td></tr>";		
			
		}

        return '<table class="table table-striped">' . $tablecourseheader . $tablecoursebody . '</table>';
    }
    
    /**
     * Retorna o código html da tabela de módulos para o curso.
     * 
     * @param array $data array of modules to course
     * @return string
     */
    private function print_table_modules_html($data) {
        
        global $OUTPUT;
        
        $tablemodule = '';
        
        if ($data) {
            
            $tablemoduleheader  = "<thead>";
            //$tablemoduleheader .= "	<th> </th>";
            $tablemoduleheader .= "	<th colspan=\"2\">Atividade/Recurso</th>";
            $tablemoduleheader .= "	<th>Último Acesso</th>";
            $tablemoduleheader .= "	<th>Hits</th>";
            $tablemoduleheader .= "	<th>Nota</th>";
            $tablemoduleheader .= "	<th>Resultado</th>";
            $tablemoduleheader .= "</thead>";
            
            $tablemodulebody = '';
            
            foreach ($data as $module) {
                $image = $OUTPUT->image_icon('icon', $module['cmname'],'mod_'.$module['modulename']);
                $grade = $module['finalgrade']; //($module['finalgrade'])? sprintf('%.2f',$module['finalgrade']) : '-';
                $tablemodulebody .= "<tr>";
                $tablemodulebody .= "	<td class=\"col1\" valign=\"top\">" . $image . "</td>";
                $tablemodulebody .= "	<td class=\"col2\">" . $module['cmname']     . "</td>";
                $tablemodulebody .= "	<td class=\"col3\">" . $module['lastaccess'] . "</td>";
                $tablemodulebody .= "	<td class=\"col4\">" . $module['hits']       . "</td>";
                $tablemodulebody .= "	<td class=\"col5\">" . $grade . "</td>";
                $tablemodulebody .= "	<td class=\"col6\">" . $module['info'] . "</td>";
                $tablemodulebody .= "</tr>";
            }
            
            $tablemodule = '<table class="table table-striped"">' . $tablemoduleheader . $tablemodulebody . '</table>';
        }
        
        return ($tablemodule)? $tablemodule: '<div class="bg-light" style="clear: both; padding: 10px;"><h6>Sem atividades/recursos</h6></div>';

    }
        
}
