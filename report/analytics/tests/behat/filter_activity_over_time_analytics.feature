@report @report_analytics
Feature: Filter activity over time data as an instructor
  In order to filter activity over time data
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
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 2"
    And I log out

  @javascript
  Scenario: Basic filtering on time based engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Engagement over time" graph
    When I toggle "All pages" on the "activity" filter
    And I press apply filter on "activitytimelinechart"
    Then the following series lines exist:
      | label | events |
      | All pages | 2 |
    And graph title should be "Displaying results for all students"
    When I toggle "All pages" on the "activity" filter
    And I toggle "All quizzes" on the "activity" filter
    And I press apply filter on "activitytimelinechart"
    Then the following series lines exist:
      | label | events |
      | All quizzes | 0 |
    When I toggle "All quizzes" on the "activity" filter
    And I click on "Students" "button"
    And I toggle "Test Student1" on the "student" filter
    And I press apply filter on "activitytimelinechart"
    Then I should see "8" zero and "1" nonzero lines
    And graph title should be "Displaying results for selected users"
    And the following series lines exist:
      | label | events |
      | All pages | 2 |
    When I toggle "Test Student1" on the "student" filter
    And I toggle "Test Student2" on the "student" filter
    And I press apply filter on "activitytimelinechart"
    Then I should see "9" zero and "0" nonzero lines
    And the following series lines exist:
      | label | events |
      | All pages | 0 |
    When I toggle "Test Student2" on the "student" filter
    And I toggle "Test Student1" on the "student" filter
    And I add "tomorrow" to the "from" date
    And I add "tomorrow" to the "to" date
    And I click on "Apply filter" "button"
    Then I should see "9" zero and "0" nonzero lines
    And the following series lines exist:
      | label | events |
      | All pages | 0 |
    When I add "yesterday" to the "from" date
    And I press apply filter on "activitytimelinechart"
    Then I should see "8" zero and "1" nonzero lines
    And the following series lines exist:
      | label | events |
      | All pages | 2 |
    When I click on ".undo i" "css_element"
    Then the following series lines exist:
      | label | events |
      | All pages | 0 |
    When I click on ".redo i" "css_element"
    Then the following series lines exist:
      | label | events |
      | All pages | 2 |
    When I click on ".closebutton i" "css_element"
    Then I should not see "Apply filter"

  @javascript
  Scenario: Advanced filtering on time based engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Engagement over time" graph
    And I click on "Students" "button"
    And I click on ".advancedtoggle" "css_element"
    When I set the following fields to these values:
      | actionfilter | All Actions |
    And I toggle "Test Student1" on the "student" filter
    And I press apply filter on "activitytimelinechart"
    Then I should see "8" zero and "1" nonzero lines
    And the following series lines exist:
      | label | events |
      | All pages | 2 |
    When I set the following fields to these values:
      | actionfilter | Views |
    And I press apply filter on "activitytimelinechart"
    Then I should see "8" zero and "1" nonzero lines
    And the following series lines exist:
      | label | events |
      | All pages | 2 |
    When I set the following fields to these values:
      | actionfilter | Interactions |
    And I press apply filter on "activitytimelinechart"
    Then I should see "9" zero and "0" nonzero lines
    And the following series lines exist:
      | label | events |
      | All pages | 0 |
    When I set the following fields to these values:
      | actionfilter | All Actions |
    And I add "yesterday" to the "from" date
    And I add "tomorrow" to the "to" date
    And I set the field with xpath "//input[contains(@class, 'binslidervalue')]" to "12"
    And I press apply filter on "activitytimelinechart"
    Then "All pages" series has "12" bins
    When I set the field with xpath "//input[contains(@class, 'binslidervalue')]" to "128"
    And I press apply filter on "activitytimelinechart"
    Then "All pages" series has "128" bins
    When I set the field with xpath "//input[contains(@class, 'binslidervalue')]" to "12"
    And I click on "input.uniquecheck" "css_element"
    And I press apply filter on "activitytimelinechart"
    Then the following series lines exist:
      | label | events |
      | All pages | 1 |

  @javascript
  Scenario: Use graph interactivity on time based engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Engagement over time" graph
    And I press apply filter on "activitytimelinechart"
    And I click on "All pages" legend entry
    And the following series lines exist:
      | label | events |
      | Test Page 1 | 1 |
      | Test Page 2 | 1 |

  @javascript
  Scenario: Advanced export features from time based engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Engagement over time" graph
    And I press apply filter on "activitytimelinechart"
    Then the export on "activitytimelinechart" is between "3750" and "3850" bytes
    When I click on ".pngexport i" "css_element"
    Then the png export should be at least "45000" bytes
