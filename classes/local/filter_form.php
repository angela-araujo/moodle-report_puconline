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
 * Form for selective filters of report_puconline
 * 
 * @package    report_puconline
 * @copyright  2020 CCEAD PUC-Rio <angela@ccead.puc-rio.br>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_puconline\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * 
 * @author angela
 *
 */
class filter_form extends \moodleform {
    
    /**
     * @todo Ajustar formulario para receber categoria e campo com informacao do usuario
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    protected function definition(){
        global $DB; 
        
        $mform = $this->_form;        
        
        $mform->addElement('header', 'filters', 'Filtro');
        
        // Add element category PUC ONLINE YYYY.01.
        $options = array(            
            'multiple' => false,
            'noselectionstring' => '-',
        );        
        $select = " name LIKE 'PUC ONLINE%'";
        $categoriesPUCRio = $DB->get_records_select_menu('course_categories', $select, array(), 'name ASC', 'id,name');
        $categoryPUCRio[-1] = '';//get_string('noselectioncat', 'report_puconline');
        foreach ($categoriesPUCRio as $categoryid => $field) {
            $categoryPUCRio[$categoryid] = $field;
        }        
        $mform->addElement('select', 'categoryid', get_string('category', 'report_puconline'), $categoryPUCRio );
        $mform->addRule('categoryid', null, 'required', null, 'client');
        
        $this->add_action_buttons(false, 'view');       
        
    }
    
    public function validation($data, $files){
        
        $errors = array();

        if ($data['categoryid'] == -1) {
            $errors['categoryid'] = get_string('required');
        }
        
        return $errors;
    }

    
}