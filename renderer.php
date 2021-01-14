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
    
    public function display_report($user, $category) {
        
        $data = new stdClass();
        $data = \report_puconline\local\datareport::fetch_data_report($user, $category);

        //\core\dataformat::download_data($filename, $dataformat, $columns, $iterator);
        
        echo $this->render_from_template('report_puconline/overview', $data);
        
    }   
        
}
