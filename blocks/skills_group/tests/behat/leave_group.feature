@block @block_skills_group @eclass-blocks-skills_group
Feature: Leave a group
  In order to leave a group
  As a student
  I need to go leave from the group editing page

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
      | student1 | Test | Student | student1@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
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
  Scenario: Leave a group
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "Team Awesome"
    And I set the following fields to these values:
      | leavegroup | 1 |
    When I click on "#id_submitbutton" "css_element"
    Then I should see "Join existing group"