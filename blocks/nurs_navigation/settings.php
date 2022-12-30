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
 * This is the settings.php, which adds the font size setting.
 *
 * @package    block_nurs_navigation
 * @category   block
 * @copyright  2012 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/nurs_navigation/locallib.php');

$settings->add(new admin_setting_configtext(
        'nurs_navigation/Image_Height',
        get_string('imageheightlabel', BNN_LANG_TABLE),
        get_string('imageheightdescription', BNN_LANG_TABLE),
        50,
        PARAM_INT)
);

$settings->add(new admin_setting_configtext(
        'nurs_navigation/Image_Width',
        get_string('imagewidthlabel', BNN_LANG_TABLE),
        get_string('imagewidthdescription', BNN_LANG_TABLE),
        50,
        PARAM_INT)
);

$settings->add(new admin_setting_configtext(
        'nurs_navigation/Categories',
        get_string('categorieslabel', BNN_LANG_TABLE),
        get_string('categoriesdescription', BNN_LANG_TABLE),
        "0, 1, 1109, 1133, 1126, 1208, 1209, 1084, 78",
        PARAM_TEXT)
);

$settings->add(new admin_setting_configselect(
        'nurs_navigation/Placement',
        get_string('placementlabel', BNN_LANG_TABLE),
        get_string('placementdescription', BNN_LANG_TABLE),
        "before",
        array("before" => get_string('before', BNN_LANG_TABLE), "after" => get_string('after', BNN_LANG_TABLE)))
);

$settings->add(new admin_setting_configtext(
        'nurs_navigation/Activities',
        get_string('activitieslabel', BNN_LANG_TABLE),
        get_string('activitiesdescription', BNN_LANG_TABLE),
        "quiz, assign, quest, forum",
        PARAM_TEXT)
);
