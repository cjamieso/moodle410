@report @report_analytics
Feature: Filter students by criteria as an instructor
  In order to filter students by criteria
  As an instructor
  I need to go to the analytics report page, add the graph, and select some criteria

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
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 2"
    And I log out

  @javascript
  Scenario: Find users by criteria
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Student list by criteria" graph
    And I click on "Actions" "button"
    And I set the following fields to these values:
      | activityfilter | All pages |
      | actionfilter | Views |
      | actionoperatorfilter | > |
      | actionvalue | 100 |
    And I click on ".addcriterion i" "css_element"
    And I press apply filter on "completionsearchchart"
    Then I should see "No users matched the selected criteria"
    When I click on "img.removecriterion" "css_element"
    And I press apply filter on "completionsearchchart"
    And I set the following fields to these values:
      | activityfilter | All pages |
      | actionfilter | Views |
      | actionoperatorfilter | > |
      | actionvalue | 0 |
    And I click on ".addcriterion i" "css_element"
    And I press apply filter on "completionsearchchart"
    Then I should see "Test Student1" in the ".d3chart" "css_element"
    And I should see "Users matching criteria"
    And I should not see "Test Student2" in the ".d3chart" "css_element"
    When I click on "img.removecriterion" "css_element"
    And I click on "Grades" "button"
    And I set the following fields to these values:
      | gradefilter | Manual item 1 |
      | gradeoperatorfilter | < |
      | gradevalue | 30 |
    And I click on ".addcriterion i" "css_element"
    And I press apply filter on "completionsearchchart"
    Then I should see "Test Student2" in the ".d3chart" "css_element"
    And I should not see "Test Student1" in the ".d3chart" "css_element"
    When I click on "img.removecriterion" "css_element"
    And I click on "Actions" "button"
    And I set the following fields to these values:
      | activityfilter | All pages |
      | actionfilter | Views |
      | actionoperatorfilter | > |
      | actionvalue | 0 |
    And I click on ".addcriterion i" "css_element"
    And I click on "Grades" "button"
    And I set the following fields to these values:
      | gradefilter | Manual item 1 |
      | gradeoperatorfilter | > |
      | gradevalue | 30 |
    And I click on ".addcriterion i" "css_element"
    And I press apply filter on "completionsearchchart"
    Then I should see "Test Student1" in the ".d3chart" "css_element"
    And I should not see "Test Student2" in the ".d3chart" "css_element"
    When I click on ".undo i" "css_element"
    Then I should see "Test Student2" in the ".d3chart" "css_element"
    And I should not see "Test Student1" in the ".d3chart" "css_element"
    When I click on ".redo i" "css_element"
    Then I should see "Test Student1" in the ".d3chart" "css_element"
    And I should not see "Test Student2" in the ".d3chart" "css_element"

  @javascript
  Scenario: Advanced export features from completion search chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Student list by criteria" graph
    And I click on "Actions" "button"
    And I set the following fields to these values:
      | activityfilter | All pages |
      | actionfilter | Views |
      | actionoperatorfilter | > |
      | actionvalue | 0 |
    And I click on ".addcriterion i" "css_element"
    And I press apply filter on "completionsearchchart"
    Then the export on "completionsearchchart" is between "50" and "70" bytes
