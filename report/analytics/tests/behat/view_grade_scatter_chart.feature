@report @report_analytics
Feature: View a grade scatter chart as an instructor
  In order to view a grade scatter chart
  As an instructor
  I need to go to the analytics report page and add the chart

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
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test Page 1"
    And I log out
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
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Basic filtering on grade chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Grades vs. Actions" graph
    And I press apply filter on "gradechart"
    Then graph title should be "Displaying results for all students"
    And the following nodes exist:
      | name | Grade | Actions |
      | Test Student1 | 35 | 4 |
      | Test Student2 | 25 | 2 |
    When I toggle "Test Student1" on the "student" filter
    And I press apply filter on "gradechart"
    Then graph title should be "Displaying results for selected users"
    And the following nodes exist:
      | name | Grade | Actions |
      | Test Student1 | 35 | 4 |
    When I click on ".undo i" "css_element"
    And the following nodes exist:
      | name | Grade | Actions |
      | Test Student1 | 35 | 4 |
      | Test Student2 | 25 | 2 |
    When I click on ".redo i" "css_element"
    And the following nodes exist:
      | name | Grade | Actions |
      | Test Student1 | 35 | 4 |
    When I click on ".closebutton i" "css_element"
    Then I should not see "Apply filter"

  @javascript
  Scenario: Advanced export features from grade chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Grades vs. Actions" graph
    And I press apply filter on "gradechart"
    Then the export on "gradechart" is between "3200" and "3300" bytes
    When I click on ".pngexport i" "css_element"
    Then the png export should be at least "20000" bytes
