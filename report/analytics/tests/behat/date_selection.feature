@report @report_analytics
Feature: Use date selection options on analytics graphs
  In order to use the date selection options
  As an instructor
  I need to go to add a graph, enter a date, then apply the filters

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course Wi13| C1 | 0 |
      | Course Sp14| C2 | 0 |
      | Course Su15| C3 | 0 |
      | Course Fa16| C4 | 0 |
      | Course Su17 Wi13 Fa20| C5 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | cjamieso@ualberta.ca |
      | student1 | Test | Student1 | cjamieso@gmx.ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
      | teacher1 | C3 | editingteacher |
      | teacher1 | C4 | editingteacher |
      | teacher1 | C5 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Re-use dates entered by previous analytics graphs as an instructor
    When I log in as "teacher1"
    And I am on "Course Wi13" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I add "2016-06-23" to the "from" date
    And I add "2016-06-25" to the "to" date
    And I press apply filter on "activitychart"
    When I add a "Engagement over time" graph
    Then "from" date on "activitytimelinechart" should be "2016-06-23"
    And "to" date on "activitytimelinechart" should be "2016-06-25"
    When I click on ".activitytimelinechart .advancedtoggle" "css_element"
    And I set the field with xpath "//input[contains(@class, 'binslidervalue')]" to "20"
    And I press apply filter on "activitytimelinechart"
    And I add a "Forum engagement over time" graph
    Then "from" date on "forumtimelinechart" should be "2016-06-23"
    And "to" date on "forumtimelinechart" should be "2016-06-25"
    Then the field with xpath "//div[contains(@class, 'forumtimelinechart')]//input[contains(@class, 'binslidervalue')]" matches value "20"
    When I press apply filter on "forumtimelinechart"
    And I add "2016-06-22" to the "from" date
    And I add "2016-06-26" to the "to" date
    And I press apply filter on "activitychart"
    And I add a "Forum engagement" graph
    Then "from" date on "forumchart" should be "2016-06-22"
    And "to" date on "forumchart" should be "2016-06-26"
    And I should not see "ERROR: Invalid date specification.  Use calendar to select date."
    And I add a "Forum engagement over time" graph

  @javascript
  Scenario: Use date selector to choose relative dates as an instructor
    When I log in as "teacher1"
    And I am on "Course Wi13" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I click on ".dateselector i" "css_element"
    And I click on "//div[contains(@class, 'dateselector')]//span[text() = 'Last week']" "xpath_element"
    Then date picker on "activitychart" should match "-7 days"
    And I click on "//div[contains(@class, 'dateselector')]//span[text() = 'Last 13 weeks']" "xpath_element"
    Then date picker on "activitychart" should match "-13 weeks"
    And I click on "//div[contains(@class, 'dateselector')]//span[text() = 'Last 4 months']" "xpath_element"
    Then date picker on "activitychart" should match "-4 months"

  @javascript
  Scenario: Auto term detection
    When I log in as "teacher1"
    And I am on "Course Wi13" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    Then "from" date on "activitychart" should be "2013-01-01"
    And "to" date on "activitychart" should be "2013-04-30"
    And I am on "Course Sp14" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    Then "from" date on "activitychart" should be "2014-05-01"
    And "to" date on "activitychart" should be "2014-06-30"
    And I am on "Course Su15" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    Then "from" date on "activitychart" should be "2015-07-01"
    And "to" date on "activitychart" should be "2015-08-31"
    And I am on "Course Fa16" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    Then "from" date on "activitychart" should be "2016-09-01"
    And "to" date on "activitychart" should be "2016-12-31"
    And I am on "Course Su17 Wi13 Fa20" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    Then "from" date on "activitychart" should be "2013-01-01"
    And "to" date on "activitychart" should be "2020-12-31"
