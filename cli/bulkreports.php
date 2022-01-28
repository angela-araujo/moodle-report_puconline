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
 * CLI bulkreports for report PUC ONLINE , use for debugging or immediate bulkreports
 * of all courses.
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    report_puconline
 * @copyright  2022 Angela de Araujo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once("$CFG->dirroot/report/puconline/lib.php");

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array('category' => null, 'verbose'=>false, 'help'=>false, 'page' => -1), 
    array('c' => 'category', 'v'=>'verbose', 'h'=>'help', 'p' => 'page'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Execute report puconline bulkreports.
    
Options:
-h, --help            Print out this help
-c, --category        Category information
-p, --page            Page (100 records per page)
-v, --verbose         Print verbose progess information
    
Example:
\$sudo -u www-data /usr/bin/php report/puconline/cli/bulkreports.php --verbose --category=123
\$sudo -u www-data /usr/bin/php report/puconline/cli/bulkreports.php --verbose --category=123 --page=1
or    
\$sudo -u www-data /usr/bin/php report/puconline/cli/bulkreports.php -v -c=123
\$sudo -u www-data /usr/bin/php report/puconline/cli/bulkreports.php -v -c=123 -p=1
    
";

if ($options['help']) {
    echo $help;
    die;
}

$verbose = !empty($options['verbose']);
$category = $options['category'];
$page = $options['page']? $options['page']:-1;

if (!empty($category)) {

    $result = report_puconline_bulk_pdf($category, $verbose, $page);    
    exit();
    
} else {
    
    exit('É necessário informar uma categoria. ' . PHP_EOL . PHP_EOL . $help);
    
}
