@report @report_analytics
Feature: Filter activities data as an instructor
  In order to filter activities
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
  Scenario: Basic filtering on content engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    When I toggle "Test Page 1" on the "activity" filter
    And I press apply filter on "activitychart"
    Then graph title should be "Displaying results for all students"
    And the following grouped bars exist:
      | label | Interactions | Views |
      | Test Page 1 | 0 | 1 |
    When I toggle "Test Page 1" on the "activity" filter
    And I click on "Students" "button"
    And I toggle "Test Student1" on the "student" filter
    And I press apply filter on "activitychart"
    Then graph title should be "Displaying results for selected users"
    And the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 2 |
    When I toggle "Test Student1" on the "student" filter
    And I toggle "Test Student2" on the "student" filter
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 0 |
    When I toggle "Test Student2" on the "student" filter
    And I add "tomorrow" to the "from" date
    And I add "tomorrow" to the "to" date
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 0 |
    When I add "yesterday" to the "from" date
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 2 |
    When I click on ".undo i" "css_element"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 0 |
    When I click on ".redo i" "css_element"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 2 |
    When I click on ".closebutton i" "css_element"
    Then I should not see "Apply filter"

  @javascript
  Scenario: Filter students by manual grade item
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test Page 1"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    # Scale, text grade items are tested in more detail in phpunit tests.
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | Manual item 1 |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "35" to the user "Test Student1" for the grade item "Manual item 1"
    And I give the grade "25" to the user "Test Student2" for the grade item "Manual item 1"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I press apply filter on "activitychart"
    And I click on "Grades" "button"
    And I set the following fields to these values:
      | gradefilter | Manual item 1 |
      | gradeoperatorfilter | = |
      | gradevalue | 35 |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 2 |
    When I set the following fields to these values:
      | gradeoperatorfilter | > |
      | gradevalue | 20 |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 3 |
    When I set the following fields to these values:
      | gradeoperatorfilter | < |
      | gradevalue | 30 |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 1 |

  @javascript
  Scenario: View activity averages and unique views on content engagement chart
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student3 | Test | Student3 | test3@ualberta.ca |
      | student4 | Test | Student4 | test4@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | student3 | C1 | student |
      | student4 | C1 | student |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | Manual item 1 |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "35" to the user "Test Student1" for the grade item "Manual item 1"
    And I give the grade "25" to the user "Test Student2" for the grade item "Manual item 1"
    And I give the grade "15" to the user "Test Student3" for the grade item "Manual item 1"
    And I give the grade "5" to the user "Test Student4" for the grade item "Manual item 1"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    When I toggle "All pages" on the "activity" filter
    And I click on "Students" "button"
    And I toggle "Test Student1" on the "student" filter
    And I click on ".advancedtoggle" "css_element"
    And I set the following fields to these values:
      | averagefilter | All students |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views | Average Interactions | Average Views |
      | All pages | 0 | 2 | 0 | 0.5 |
    And I set the following fields to these values:
      | averagefilter | Top 15% of class |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views | Average Interactions | Average Views |
      | All pages | 0 | 2 | 0 | 2 |
    And I set the following fields to these values:
      | averagefilter | Bottom 15% of class |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views | Average Interactions | Average Views |
      | All pages | 0 | 2 | 0 | 0 |
    When I toggle "Test Student1" on the "student" filter
    And I click on "input.uniquecheck" "css_element"
    And I set the following fields to these values:
      | averagefilter | None |
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 1 |

  @javascript
  Scenario: Use graph interactivity on content engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I press apply filter on "activitychart"
    # And I click on "//*[local-name()='image' and contains(@class, 'Views')]" "xpath_element"
    # Then I should see "Views include accessing an activity"
    # When I click on "body > .moodle-dialogue-base .yui3-panel .closebutton" "css_element"
    # And I click on "//*[local-name()='image' and contains(@class, 'Interactions')]" "xpath_element"
    # Then I should see "Examples of interactions include:"
    # When I click on "body > .moodle-dialogue-base .yui3-panel .closebutton" "css_element"
    And I click on "bar2" bar
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | Test Page 1 | 0 | 1 |
      | Test Page 2 | 0 | 1 |

  @javascript
  Scenario: Advanced export features from content engagement chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I press apply filter on "activitychart"
    Then the export on "activitychart" is between "3350" and "3450" bytes
    When I click on ".pngexport i" "css_element"
    Then the png export should be at least "30000" bytes
