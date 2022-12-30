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

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions to deal with the report_analytics system.
 *
 * @package    report_analytics
 * @category   test
 * @copyright  2015 Craig Jamieson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class behat_report_analytics extends behat_base {

    /**
     * Presses the apply filter button on the specified graph.
     *
     * @When /^I press apply filter on "(?P<graphid>(?:[^"]|\\")*)"$/
     * @param  string  $graphclass  the class of the graph to press apply filter for
     *
     */
    public function i_press_apply_filter_on($graphclass) {

        $this->execute('behat_general::i_click_on', array('.' . $graphclass . ' .d3button', 'css_element'));
    }

    /**
     * Adds the specified date to the desired element (to/from).
     *
     * @Given /^I add "(?P<date>(?:[^"]|\\")*)" to the "(?P<element>(?:[^"]|\\")*)" date$/
     * @param  string  $date     the date to add to the element
     * @param  string  $element  the item to add the date to {'to', 'from'}
     *
     */
    public function i_add_to_the_date($date, $element) {

        $el = ($element == 'to') ? 'dateto' : 'datefrom';
        $datetime = DateTime::createFromFormat('Y-m-d', $date);
        // Format failed, using relative format.
        if ($datetime === false) {
            $datetime = new DateTime($date);
        }
        $els = array($el . '[day]', $el . '[month]', $el . '[year]', $el . '[hour]', $el . '[minute]');
        // Times default to 9:30 am: there are inconsistencies in how behat forces timezones.
        $vals = array((int) $datetime->format('d'), (int) $datetime->format('m'), (int) $datetime->format('Y'), '9', '30');
        for ($i = 0; $i < count($els); $i++) {
            $node = $this->find("xpath", "//select[contains(@name, '" . $els[$i] . "')]");
            $node->selectOption(strval($vals[$i]));
        }
    }

    /**
     * Checks the specified date on the desired element.
     *
     * @Then /^"(?P<element>(?:[^"]|\\")*)" date on "(?P<graph>(?:[^"]|\\")*)" should be "(?P<date>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param  string  $element  the portion of the date {'to', 'from'}
     * @param  string  $graph    the class of the graph
     * @param  string  $date     the date to add to the element
     *
     */
    public function date_on_should_be($element, $graph, $date) {

        $el = ($element == 'to') ? 'dateto' : 'datefrom';
        $datetime = DateTime::createFromFormat('Y-m-d', $date);
        // Format failed, using relative format.
        if ($datetime === false) {
            $datetime = new DateTime($date);
        }
        // Set the date field values.
        $els = array($el . '[day]', $el . '[month]', $el . '[year]');
        $vals = array((int) $datetime->format('d'), (int) $datetime->format('m'), (int) $datetime->format('Y'));
        for ($i = 0; $i < count($els); $i++) {
            $node = $this->find("xpath", "//div[contains(@class, '" . $graph . "')]//select[contains(@name, '" . $els[$i] . "')]");
            $temp = $node->getValue();
            if (intval($temp) !== $vals[$i]) {
                throw new ExpectationException('Incorrect date: ' . $temp . ' instead of: ' . $vals[$i] . ' for selector: ' .
                    $els[$i], $this->getSession());
            }
        }
    }

    /**
     * Adds the specified element to the given filter.  The button is clicked twice
     * to make the selector disappear after making a choice.
     *
     * @When /^I toggle "(?P<element>(?:[^"]|\\")*)" on the "(?P<filtertype>(?:[^"]|\\")*)" filter$/
     * @param  string  $element     the item to add to the filter
     * @param  string  $filtertype  the filter to add the item to
     * @return array   Array of behat steps to complete filter toggle
     *
     */
    public function i_toggle_on_the_filter($element, $filtertype) {

        $f = $filtertype . 'filter';
        $this->execute('behat_general::i_click_on', array('.' . $f . ' button', 'css_element'));
        $this->execute('behat_general::i_click_on', array('//label[contains(text(), "' . $element . '")]', 'xpath_element'));
        $this->execute('behat_general::i_click_on', array('.' . $f . ' button', 'css_element'));
    }

    /**
     * Adds the specified element to the given filter.  The button is clicked twice
     * to make the selector disappear after making a choice.
     *
     * @When /^I set "(?P<type>(?:[^"]|\\")*)" words filter to "(?P<words>\d+)"$/
     * @throws ElementNotFoundException
     * @param  string  $type   {min|max}
     * @param  string  $words  number of words to set filter to
     *
     */
    public function i_set_words_filter_to($type, $words) {

        $suffix = ($type == 'min') ? 'minwordcount' : 'maxwordcount';
        $exception = new ElementNotFoundException($this->getSession(), '"' . $suffix . '" ');
        $node = $this->find("xpath", "//input[contains(@name, '" . $suffix . "')]", $exception);
        $node->setValue(intval($words));
    }

    /**
     * Verifies that the title of the graph is set correctly.
     *
     * @Then /^graph title should be "(?P<text>(?:[^"]|\\")*)"$/
     * @param  string  $text    the text to find
     * @return object  behat "Then" step corresponding to note to check
     *
     */
    public function graph_title_should_be($text) {

        $element = "//*[local-name()='text' and contains(text(), '" . $text . "')]";
        $this->execute('behat_general::wait_until_exists', array($element, 'xpath_element'));
        $this->execute('behat_general::should_be_visible', array($element, 'xpath_element'));
    }

    /**
     * Tests to see if the grouped bars are present.
     *
     * @Then /^the following grouped bars exist:$/
     * @throws ExpectationException
     * @param  TableNode $data  table data containing labels and total events
     *
     */
    public function the_following_grouped_bars_exist(TableNode $data) {

        $this->ensure_element_exists("//*[local-name()='g' and contains(@class, 'groupedbar')]", "xpath_element");

        foreach ($data->getHash() as $d) {
            $d3data = $this->getSession()->getDriver()->evaluateScript('return getGroupedbarD3data("' . $d['label'] . '")');
            if (empty($d3data)) {
                throw new ExpectationException('No data found for label: ' . $d['label'], $this->getSession());
            }
            $labels = array('Interactions', 'Views', 'Average Views', 'Average Interactions');
            foreach ($labels as $l) {
                if (isset($d[$l])) {
                    foreach ($d3data['values'] as $bar) {
                        if ($l == $bar['name'] && $d[$l] != $bar['value']) {
                            throw new ExpectationException('Incorrect ' . $l . ' for: ' . $d['label'] . ', ' . $bar['value'] .
                                ' instead of ' . $d[$l], $this->getSession());
                        }
                    }
                }
            }
        }
    }

    /**
     * Ensures that the specified svg "text" tag with a given string exists and is visible.
     * svg tags are handled a little differently, since they use a different namespace.
     *
     * Note: this should only be used if there are multiple zero-valued lines.
     *
     * @Then /^I should see "(?P<zero>\d+)" zero and "(?P<nonzero>\d+)" nonzero lines$/
     * @throws ExpectationException
     * @param  string  $zero    the number of lines with all zeros
     * @param  string  $nonzero the number of lines with a non-zero amount of events
     *
     */
    public function i_should_see_zero_and_nonzero_lines($zero, $nonzero) {

        $nodes = $this->find_all("xpath", "//*[local-name()='g' and contains(@class, 'series')]//*[local-name()='path']");
        $vals = array();
        foreach ($nodes as $node) {
            $vals[] = $node->getAttribute('d');
        }
        $sorted = array_count_values($vals);
        // Sort High->Low so that zero value lines go at end.
        asort($sorted);
        if ($zero != array_pop($sorted)) {
            throw new ExpectationException('incorrect number of lines set to zero', $this->getSession());
        }
        if ($nonzero != array_sum($sorted)) {
            throw new ExpectationException('incorrect number of lines set to non-zero', $this->getSession());
        }
    }

    /**
     * Tests to see if the correct number of events exists on a line.
     *
     * @Then /^the following series lines exist:$/
     * @throws ExpectationException
     * @param  TableNode $data  table data containing labels and total events
     *
     */
    public function the_following_series_lines_exist(TableNode $data) {

        $this->ensure_element_exists("//*[local-name()='g' and contains(@class, 'series')]", "xpath_element");

        foreach ($data->getHash() as $d) {
            $d3data = $this->getSession()->getDriver()->evaluateScript('return getSeriesD3data("' . $d['label'] . '")');
            if (empty($d3data)) {
                throw new ExpectationException('No data found for label: ' . $d['label'], $this->getSession());
            }
            $sum = $this->sum_series_events($d['label'], $d3data);
            if ($sum != $d['events']) {
                throw new ExpectationException('Incorrect events for: ' . $d['label'] . ', ' . $sum . ' instead of '
                    . $d['events'], $this->getSession());
            }
        }
    }

    /**
     * Find total sum of events for a d3 data line.
     *
     * @param  string  $label   the label indicating the line
     * @param  object  $d3data  the __data__ variable from javascript
     * @return int  the total number of events on the line
     */
    private function sum_series_events($label, $d3data) {

        $sum = 0;
        if (is_array($d3data)) {
            foreach ($d3data as $d) {
                if ($d['label'] == $label) {
                    $sum += $d['count'];
                }
            }
        }
        return $sum;
    }

    /**
     * Tests to see if the correct number of bins were evaluated.
     *
     * @Then /^"(?P<name>(?:[^"]|\\")*)" series has "(?P<bins>\d+)" bins$/
     * @throws ExpectationException
     * @param  string  $name  the label for the line
     * @param  int     $bins  the total number of bins (expected)
     *
     */
    public function series_has_bins($name, $bins) {

        // This ensures the nodes exist on the page prior to retrieving the data.
        $this->find_all("xpath", "//*[local-name()='g' and contains(@class, 'series')]");
        $data = $this->getSession()->getDriver()->evaluateScript("return $('.series path').prop('__data__')");
        $actual = count($data['values']);

        if ($bins != $actual) {
            throw new ExpectationException('Incorrect number of bins: ' . $name . ', ' . $actual . ' instead of ' . $bins,
                $this->getSession());
        }
    }

    /**
     * Tests to see if the nodes are present on the grades scatter chart.
     *
     * @Then /^the following nodes exist:$/
     * @throws ExpectationException
     * @param  TableNode $data  table data containing names, grade, actions
     *
     */
    public function the_following_nodes_exist(TableNode $data) {

        $this->ensure_element_exists("//*[local-name()='g' and contains(@class, 'node')]", "xpath_element");

        foreach ($data->getHash() as $d) {
            $d3data = $this->getSession()->getDriver()->evaluateScript('return getScatterD3data("' . $d['name'] . '")');
            if (empty($d3data)) {
                throw new ExpectationException('No data found for name: ' . $d['name'], $this->getSession());
            }
            if (isset($d3data['x']) && (intval($d['Actions']) !== $d3data['x'])) {
                throw new ExpectationException('Incorrect actions for: ' . $d['name'] . ', ' . $d3data['x'] .
                    ' instead of ' . $d['Actions'], $this->getSession());
            }
            if (isset($d3data['y']) && (intval($d['Grade']) !== $d3data['y'])) {
                throw new ExpectationException('Incorrect actions for: ' . $d['name'] . ', ' . $d3data['y'] .
                    ' instead of ' . $d['Grade'], $this->getSession());
            }
        }
    }

    /**
     * Adds the desired analytics graph to the page.
     *
     * @When /^I add a "(?P<graphname>(?:[^"]|\\")*)" graph$/
     * @param  string  $graphname  the name of the graph to add
     * @return array   Array of behat steps to complete adding graph to page
     *
     */
    public function i_add_a_graph($graphname) {

        $this->execute('behat_general::i_click_on', array('.d3plus', 'css_element'));
        $this->execute('behat_general::i_click_on', array($graphname, 'radio'));
        $this->execute('behat_general::i_click_on', array('.jschooser .submitbutton', 'css_element'));
    }

    /**
     * Download an excel file that's generate as part of a form submit.  The size of
     * the file is compared to the given parameters.
     *
     * A good bit of this function is taken from download_file_from_link() in
     * /lib/tests/behat/behat_general.php.  That function requires a clickable link,
     * so I've changed it up a bit here to accept a URL instead.
     *
     * @Then /^the export on "(?P<graphname>(?:[^"]|\\")*)" is between "(?P<minbytes>\d+)" and "(?P<maxbytes>\d+)" bytes$/
     * @throws ExpectationException
     * @param  string  $graph     the class of the graph
     * @param  number  $minbytes  the minimum expected file size in bytes.
     * @param  number  $maxbytes  the maximum expected file size in bytes.
     *
     */
    public function the_export_on_is_between_and_bytes($graph, $minbytes, $maxbytes) {

        $submitargs = array();
        $nodes = $this->find_all("xpath", "//div[contains(@class, '" . $graph . "')]//form/input");
        foreach ($nodes as $node) {
            if ($node->hasAttribute('type')) {
                if ($node->getAttribute('type') == 'hidden') {
                    $name = $node->getAttribute('name');
                    $value = $node->getAttribute('value');
                    $submitargs[$name] = $value;
                }
            }
        }

        $url = new moodle_url('/report/analytics/ajax_request.php', $submitargs);
        $session = $this->getSession()->getCookie('MoodleSession');
        $result = download_file_content($url->out(false), array('Cookie' => 'MoodleSession=' . $session));
        $actualsize = (int)strlen($result);
        if ($actualsize < $minbytes || $actualsize > $maxbytes) {
            throw new ExpectationException('Downloaded data was ' . $actualsize . ' bytes, expecting between ' . $minbytes .
                ' and ' . $maxbytes, $this->getSession());
        }
    }

    /**
     * Clicks on the desired bar.
     *
     * @When /^I click on "(?P<barid>(?:[^"]|\\")*)" bar$/
     * @throws ElementNotFoundException
     * @param  string  $legendlabel  the name of the legend entry to click
     *
     */
    public function i_click_on_bar($barid) {

        $exception = new ElementNotFoundException($this->getSession(), 'Legend label ' . $barid . ' ');
        $node = $this->find("xpath", "//*[local-name()='g' and contains(@class, 'groupedbar') and
            contains(@id, '" . $barid . "')]", $exception);
        $node->click();
    }

    /**
     * Clicks on the desired legend label.
     *
     * @When /^I click on "(?P<legendlabel>(?:[^"]|\\")*)" legend entry$/
     * @throws ElementNotFoundException
     * @param  string  $legendlabel  the name of the legend entry to click
     *
     */
    public function i_click_on_legend_entry($legendlabel) {

        $exception = new ElementNotFoundException($this->getSession(), 'Legend label ' . $legendlabel . ' ');
        $node = $this->find("xpath", "//*[local-name()='g' and contains(@class, 'legend')]//*[local-name()='text' and
            contains(text(), '" . $legendlabel . "')]", $exception);
        $node->click();
    }

    /**
     * Verifies the values of the two dates on the date selector.  The javascript
     * timezone is not changed for tests.  Grab the date manually from javascript
     * initially to ensure that the "to" date is valid.
     *
     * @Then /^date picker on "(?P<graph>(?:[^"]|\\")*)" should match "(?P<offset>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param  string  $graph   the class of the graph
     * @param  string  $offset  the offset value between the two dates
     *
     */
    public function date_picker_on_should_match($graph, $offset) {

        $jscode = "return (function() { var jsdate = new Date();" .
            "return jsdate.getFullYear() + '-' + parseInt(jsdate.getMonth() + 1) + '-' + jsdate.getDate();" .
             "})();";
        $jsdate = $this->getSession()->evaluateScript($jscode);
        $past = DateTime::createFromFormat('Y-m-d', $jsdate);
        if (strstr($offset, 'months') !== false) {
            $diff = date_diff(new \DateTime('now'), new \DateTime('now ' . $offset));
            $offset = $diff->format('%R%a days');
        }
        $past->modify($offset);
        $this->execute('behat_report_analytics::date_on_should_be', array('from', $graph, $past->format('Y-m-d')));
        $this->execute('behat_report_analytics::date_on_should_be', array('to', $graph, $jsdate));
    }

    /**
     * Verifies the existence of a word cloud with a minimum number of words.
     * A minimum is used since the word cloud plugin does not guarantee that
     * all words are placed.
     *
     * @Then /^the word cloud should contain at least "(?P<count>\d+)" words$/
     * @throws ElementNotFoundException, ExpectationException
     * @param  int  $count  the number of words in the word cloud
     *
     */
    public function the_word_cloud_should_contain_at_least_words($count) {

        $exception = new ElementNotFoundException($this->getSession(), 'Word Cloud ');
        $nodes = $this->find_all("xpath", "//*[local-name()='text']", $exception);
        if (count($nodes) < $count) {
            throw new ExpectationException('Wordcloud contained only ' . count($nodes) . ' words', $this->getSession());
        }
    }

    /**
     * Check the png export to see if it is the correct size.  The export information
     * is stored in base64 form in the href attribute of an anchor tag.
     *
     * There seems to be some variability between my vagrant box and travis CI in terms
     * of the base64 representation of the png files.  I've opted here to only include
     * a minimum number of bytes to compensate.
     *
     * @Then /^the png export should be at least "(?P<minbytes>\d+)" bytes$/
     * @throws ElementNotFoundException, ExpectationException
     * @param  number  $minbytes  the minimum expected file size in bytes.
     *
     */
    public function the_png_export_should_be_at_least_bytes($minbytes) {

        $exception = new ElementNotFoundException($this->getSession(), 'png export ');
        $node = $this->find("xpath", "//a[contains(@class, 'pngexport')]", $exception);
        $href = $node->getAttribute('href');
        $parts = explode('data:image/png;base64,', $href);
        if (count($parts) != 2) {
            throw $exception;
        }
        $bytes = strlen($parts[1]);
        if ($bytes < $minbytes) {
            throw new ExpectationException('png export was ' . $bytes . ' bytes', $this->getSession());
        }
    }

}
