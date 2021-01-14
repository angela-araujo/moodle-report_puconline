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
 * Displays report PUC ONLINE.
 *
 * @package    report_puconline
 * @copyright  2020 CCEAD PUC-Rio (angela@ccead.puc-rio.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

// Get params.
$userid = optional_param('userid', -1, PARAM_INT);
$categoryid = optional_param('categoryid', -1, PARAM_INT);

// Getting objects user and category passed from post.
$user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0), '*', IGNORE_MISSING);
$category = $DB->get_record('course_categories', array('id' => $categoryid), '*', IGNORE_MISSING);

$userid = (!$user)? -1: $user->id;
$categoryid = (!$category)? -1: $category->id;

$params = array();

if ($userid <> -1) {
    $params['userid'] = $userid;
}
if ($categoryid <> -1) {
    $params['categoryid'] = $categoryid;
}

// Set url page.
$url = new moodle_url('/report/puconline/index.php', $params);
$PAGE->set_url($url);

// Check permissions.
require_login();
$context = context_user::instance($USER->id);
require_capability('report/puconline:view', $context);


// Display page.
$PAGE->set_course($COURSE);
$PAGE->set_heading(get_string('pluginname', 'report_puconline'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_puconline'));


// Trigger an report viewed event.
$event = \report_puconline\event\report_viewed::create(array(
    'context' => $context, 
    'userid' => $USER->id,
    'other' => array(
        'relateduserid' => $userid, 
        'categoryid' => $categoryid)    
));
$event->trigger();

// Display header.
echo $OUTPUT->header();


// Filter form.
$mform = new \report_puconline\local\filter_form($url);
if ($userid <> -1) {
    $mform->set_data(['userid' => $userid]);
}
if ($categoryid <> -1) {
    $mform->set_data(['categoryid' => $categoryid]);
}

// Display Filter Form.
$mform->display();

$renderer = $PAGE->get_renderer('report_puconline');
echo $renderer->display_report($user, $category);

echo $OUTPUT->footer();