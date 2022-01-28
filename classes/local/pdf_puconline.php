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
 * Class pdf_puconline
 *
 *
 * @package   report_puconline
 * @copyright 2020 CCEAD PUC-Rio <angela@ccead.puc-rio.br>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_puconline\local;
//require_once('../../config.php');
require_once($CFG->libdir . '/pdflib.php');
defined('MOODLE_INTERNAL') || die();

/**
 * Extend the TCPDF class to create custom Header and Footer
 * 
 * @author angela
 * @link https://tcpdf.org/examples/
 *
 */
class pdf_puconline extends \TCPDF {
    
    //Page header
    public function Header() {
        
        $title = get_string('pluginname','report_puconline');
        
        // Logo
        // Set font
        $this->SetFont('helvetica', '', 20);
        
        // Title
        //$this->Cell(0, 15, $title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        $this->Ln();
        
        $this->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'solid' => 4, 'color' => array(0, 0, 0)));
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0,0,0);
        
        $this->Cell(0, 0, $title, 1, 1, 'C', 1, 0);
        
        $this->Ln();
    }
    
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
