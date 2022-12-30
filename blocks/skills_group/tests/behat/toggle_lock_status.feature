@block @block_skills_group @eclass-blocks-skills_group
Feature: Toggle a student's group lock
  In order to toggle a student's group lock
  As an instructor
  I need to go to the admin page where locks can be viewed/toggled

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "groupings" exist:
      | name       | course  | idnumber  |
      | Grouping 1 | C1 | GROUPING1 |
    And the following "groups" exist:
      | name | course | idnumber |
      | Team Awesome | C1 | G1 |
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | anonymous |
      | feedback | Feedback 1 | Test feedback description | C1 | feedback1 | 2 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | teacher1@ualberta.ca |
      | student1 | Test | Student1 | student1@ualberta.ca |
      | student2 | Test | Student2 | student2@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GROUPING1 | G1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | prefeedbackid | Feedback 1 |
      | groupingid | Grouping 1 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | maxsize | 6 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Toggle lock status
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Lock my group choice" "link"
    Then I should see "Team Awesome"
    And I set the following fields to these values:
      | lockchoice | 1 |
    When I click on "#id_submitbutton" "css_element"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View/edit student lock status" "link"
    Then "//td[text()='Test Student1']/..//i[contains(concat(' ', @class, ' '), ' fa-lock ')]" "xpath_element" should be visible
    And "//td[text()='Test Student2']/..//i[contains(concat(' ', @class, ' '), ' fa-unlock ')]" "xpath_element" should be visible
    When I click on "//td[text()='Test Student1']/..//i[contains(concat(' ', @class, ' '), ' fa-lock ')]" "xpath_element"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Lock my group choice" "link"
    Then the following fields match these values:
      | lockchoice | 0 |
