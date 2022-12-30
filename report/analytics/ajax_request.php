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
 * This file handles ajax requests.  We create an instance of the ajax_controller
 * and then the controller handles the request.
 *
 * @package    report_analytics
 * @category   report
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

$controller = new \report_analytics\ajax_controller();
$request = required_param('request', PARAM_TEXT);
// Codechecker now requires check for require_login() in non-internal files.
$courseid = required_param('courseid', PARAM_INT);

try {
    require_login($courseid, false, null, false, true);
    $controller->perform_request($request);
} catch (\Exception $e) {
    echo(json_encode(array('result' => false, 'message' => $e->getMessage())));
}
