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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../lib/behat/behat_field_manager.php');

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

use Behat\Behat\Context\Step\Given as Given,
    Behat\Behat\Context\Step\When as When,
    Behat\Behat\Context\Step\Then as Then,
    Behat\Gherkin\Node\PyStringNode as PyStringNode;

/**
 * Steps definitions to deal with the skills_group block.
 *
 * @package    block_skills_group
 * @category   test
 * @copyright  2014 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class behat_block_skills_group extends behat_base {

    /**
     * Adds the specified student to the group member selector.
     *
     * The autocomplete selector uses the "aria-hidden" attribute to determine which
     * one is currently visible on the page.
     *
     * @Given /^I add "(?P<student>(?:[^"]|\\")*)" to my group$/
     * @throws ElementNotFoundException
     * @param string $student The students's full name (fname lname)
     *
     */
    public function i_add_to_my_group($student) {

        $this->execute('behat_general::i_click_on', array('#groupmembers', 'css_element'));
        $this->execute('behat_general::i_click_on', array("//div[@aria-hidden='false']//li[text()='" . $student . "']",
            'xpath_element'));
    }

    /**
     * Removes the specified student from the group member selector.
     *
     * The <li> that holds the student has class "yui3-multivalueinput-listitem"
     *
     * @Given /^I remove "(?P<student>(?:[^"]|\\")*)" from my group$/
     * @throws ElementNotFoundException
     * @param string $student The students's full name (fname lname)
     *
     */
    public function i_remove_from_my_group($student) {

        $class = "contains(concat(' ', normalize-space(@class), ' '), 'yui3-multivalueinput-listitem ')";
        $text = "text()[contains(.,'{$student}')]";

        $this->execute('behat_general::i_click_on', array("//li[" . $class . " and " . $text . "]//a", 'xpath_element'));
    }

    /**
     * Adds the specified element to the given filter.  The button is clicked twice
     * to make the selector disappear after making a choice.
     *
     * @When /^I toggle "(?P<element>(?:[^"]|\\")*)" on the "(?P<filtertype>(?:[^"]|\\")*)" selector$/
     * @throws ElementNotFoundException
     * @param  string  $element     the item to add to the filter
     * @param  string  $filtertype  the filter to add the item to
     * @return array   Array of behat steps to complete filter toggle
     *
     */
    public function i_toggle_on_the_selector($element, $filtertype) {

        $f = $filtertype . 'filter';

        $this->execute('behat_general::i_click_on', array($f . ' button', 'css_element'));
        $this->execute('behat_general::i_click_on', array("//label[contains(text(), '" . $element . ")]", 'xpath_element'));
        $this->execute('behat_general::i_click_on', array($f . ' button', 'css_element'));
    }

    /**
     * Adds the specified element to the given graph filter.  The button is clicked twice
     * to make the selector disappear after making a choice.
     *
     * @When /^I toggle "(?P<element>(?:[^"]|\\")*)" on the "(?P<filtertype>(?:[^"]|\\")*)" graph filter$/
     * @param  string  $element     the item to add to the filter
     * @param  string  $filtertype  the filter to add the item to
     * @return array   Array of behat steps to complete filter toggle
     *
     */
    public function i_toggle_on_the_graph_filter($element, $filtertype) {

        $f = $filtertype . 'filter';
        $this->execute('behat_general::i_click_on', array('.' . $f . ' button', 'css_element'));
        $this->execute('behat_general::i_click_on', array('//label[contains(text(), "' . $element . '")]', 'xpath_element'));
        $this->execute('behat_general::i_click_on', array('.' . $f . ' button', 'css_element'));
    }

    /**
     * Tests to see if the bars are present.
     *
     * @Then /^the following bars exist:$/
     * @throws Exception
     * @param  TableNode $data  table data containing labels and total events
     *
     */
    public function the_following_bars_exist(TableNode $data) {

        $this->ensure_element_exists("//*[local-name()='g' and contains(@class, 'groupedbar')]", "xpath_element");

        foreach ($data->getHash() as $d) {
            $d3data = $this->getSession()->getDriver()->evaluateScript('return get_groupedbar_d3data("' . $d['label'] . '")');
            $labels = array('Pre-course', 'Post-course');
            foreach ($labels as $l) {
                if ($d[$l] != $d3data[$l]) {
                        throw new \Exception('Incorrect ' . $l . ' for: ' . $d['label'] . ', ' . $d3data[$l] . ' instead of ' .
                        $d[$l]);
                }
            }
        }
    }

    /**
     * Fills in answers on a feedback with the specified value.
     *
     * @Given /^I answer feedback with "(?P<value>\d+)"$/
     * @throws Exception
     * @param  TableNode $data  table data containing labels and total events
     *
     */
    public function i_answer_feedback_with($value) {
        $exception = new ElementNotFoundException($this->getSession(), 'Feedback questions not found');
        $page = $this->getSession()->getPage();
        $nodes = $page->findAll("xpath", "//input[@value='" . strval($value + 1) . "']", $exception);
        foreach ($nodes as $node) {
            $select = $node->getAttribute('name');
            $option = $node->getAttribute('value');
            $page->selectFieldOption($select, $option);
        }
    }

    /**
     * Download an excel file that's generated as part of a form submit.  The size of
     * the file is compared to the given parameters.
     *
     * A good bit of this function is taken from download_file_from_link() in
     * /lib/tests/behat/behat_general.php.  That function requires a clickable link,
     * so I've changed it up a bit here to accept a URL instead.
     *
     * @Then /^the "(?P<reportname>(?:[^"]|\\")*)" export is between "(?P<minbytes>\d+)" and "(?P<maxbytes>\d+)" bytes$/
     * @throws ExpectationException
     * @param  string  $report   the report to download {'pre'|'post'}
     * @param  number  $minbytes the minimum expected file size in bytes.
     * @param  number  $maxbytes the maximum expected file size in bytes.
     */
    public function the_export_on_is_between_and_bytes($report, $minbytes, $maxbytes) {

        $submitargs = array();
        $class = ($report == 'pre') ? 'precourseexport' : 'postcourseexport';
        $nodes = $this->find_all("xpath", "//div[contains(@class, '" . $class . "')]//form/input");
        foreach ($nodes as $node) {
            if ($node->hasAttribute('type')) {
                if ($node->getAttribute('type') == 'hidden') {
                    $name = $node->getAttribute('name');
                    $value = $node->getAttribute('value');
                    $submitargs[$name] = $value;
                }
            }
        }

        $url = new moodle_url('/blocks/skills_group/analysis_to_excel.php', $submitargs);
        $session = $this->getSession()->getCookie('MoodleSession');
        $result = download_file_content($url->out(false), array('Cookie' => 'MoodleSession=' . $session));
        $actualsize = (int)strlen($result);
        if ($actualsize < $minbytes || $actualsize > $maxbytes) {
            throw new ExpectationException('Downloaded data was ' . $actualsize . ' bytes, expecting between ' . $minbytes .
                ' and ' . $maxbytes, $this->getSession());
        }
    }

    /**
     * Checks the database to see if a particular user is in a group
     *
     * @Then /^"(?P<username>(?:[^"]|\\")*)" should be in group "(?P<groupname>(?:[^"]|\\")*)"$/
     * @throws Exception
     * @param string $username Username of user in question
     * @param string $groupname The name of the group to check
     *
     */
    public function should_be_in_group($username, $groupname) {

        $record = $this->get_group_record($username, $groupname);

        if ($record === false) {
            throw new Exception('User ' . $username . ' not found in group' . $groupname);
        }
    }

    /**
     * Checks the database to see if a particular user is not in a group
     *
     * @Then /^"(?P<username>(?:[^"]|\\")*)" should not be in group "(?P<groupname>(?:[^"]|\\")*)"$/
     * @throws Exception
     * @param string $username Username of user in question
     * @param string $groupname The name of the group to check
     *
     */
    public function should_not_be_in_group($username, $groupname) {

        $record = $this->get_group_record($username, $groupname);

        if ($record !== false) {
            throw new Exception('User ' . $username . ' was found in group' . $groupname);
        }
    }

    /**
     * Retrieve a group record from the database -> see if $username is in
     * $groupname.
     *
     * @param string $username Username of user in question
     * @param string $groupname The name of the group to check
     * @return object The group record from the database
     */
    private function get_group_record($username, $groupname) {
        global $DB;

        $groupid = $DB->get_field('groups', 'id', array('name' => $groupname));
        $userid = $this->get_user_id($username);
        return $record = $DB->get_record('groups_members', array('groupid' => $groupid, 'userid' => $userid));
    }

    /**
     * Transform a moodle username into the corresponding ID.  I expected that this
     * would already exist as part of the API, but it does not.
     *
     * @throws Exception
     * @param string $username The username to find
     * @return int The ID corresponding to the username
     */
    private function get_user_id($username) {
        global $DB;

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }
        return $id;
    }

}
