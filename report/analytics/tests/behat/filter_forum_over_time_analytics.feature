@report @report_analytics
Feature: Filter forums over time data as an instructor
  In order to filter forums over time data
  As an instructor
  I need to go to the analytics report page, add the graph, and input a filter

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | cjamieso@ualberta.ca |
      | student1 | Test | Student1 | cjamieso@gmx.ualberta.ca |
      | student2 | Test | Student2 | craig.jamieson@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | name | intro | content | course | idnumber |
      | page | Test Page 1 | Required description | Empty content page | C1 | page1 |
      | page | Test Page 2 | Required description | Empty content page | C1 | page2 |
      | page | Test Page 3 | Required description | Empty content page | C1 | page3 |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum type | Standard forum for general use |
      | Description | Test forum description |
    And I log out
    And I am on "Course 1" course homepage
    And I log in as "student1"
    And I add a new discussion to "Test forum name" forum with:
      | Subject | Forum post subject |
      | Message | This is the body |
    And I follow "Forum post subject"
    And I log out

  @javascript
  Scenario: Basic filtering on time based forum engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Forum engagement over time" graph
    And I click on "Students" "button"
    And I toggle "Test Student1" on the "student" filter
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 1 |
    When I toggle "Test forum name" on the "activity" filter
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 1 |
    When I toggle "Test forum name" on the "activity" filter
    When I toggle "Test Student1" on the "student" filter
    And I toggle "Test Student2" on the "student" filter
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 0 |
    When I toggle "Test Student2" on the "student" filter
    And I toggle "Test Student1" on the "student" filter
    And I add "tomorrow" to the "from" date
    And I add "tomorrow" to the "to" date
    And I click on "Apply filter" "button"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 0 |
    When I add "yesterday" to the "from" date
    And I click on "Apply filter" "button"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 1 |
    When I click on ".closebutton i" "css_element"
    Then I should not see "Apply filter"

  @javascript
  Scenario: Advanced filtering on time based forum engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Forum engagement over time" graph
    And I click on ".advancedtoggle" "css_element"
    And I click on "Students" "button"
    When I set the following fields to these values:
      | actionfilter | Discussion viewed |
    And I toggle "Test Student1" on the "student" filter
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 1 |
    When I set the following fields to these values:
      | actionfilter | Post created |
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 0 |
    When I set the following fields to these values:
      | actionfilter | Discussion created |
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 1 |

  @javascript
  Scenario: Export time based forum engagement chart data to excel
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Forum engagement over time" graph
    And I press apply filter on "forumtimelinechart"
    Then the export on "forumtimelinechart" is between "3350" and "3450" bytes
