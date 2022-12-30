@block @block_skills_group @eclass-blocks-skills_group
Feature: Finalize group choice
  In order to finalize my group choice
  As a student
  I need to go be in a group and fill out the consent

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
      | Course 2 | C2 | 0 |
    And the following "groupings" exist:
      | name       | course  | idnumber  |
      | Grouping 1 | C1 | GROUPING1 |
      | Grouping 2 | C2 | GROUPING2 |
    And the following "groups" exist:
      | name | course | idnumber |
      | Team Awesome | C1 | G1 |
      | Team Awesome | C2 | G2 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | teacher1@ualberta.ca |
      | student1 | Test | Student | student1@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | teacher1 | C2 | editingteacher |
      | student1 | C2 | student |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student1 | G2 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GROUPING1 | G1 |
      | GROUPING2 | G2 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | groupingid | Grouping 1 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | maxsize | 6 |
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | groupingid | Grouping 2 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | maxsize | 6 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Finalize group choice
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Lock my group choice" "link"
    Then I should see "Team Awesome"
    And I set the following fields to these values:
      | lockchoice | 1 |
    When I click on "#id_submitbutton" "css_element"
    And I click on "Lock my group choice" "link"
    Then the following fields match these values:
      | lockchoice | 1 |
    When I am on "Course 2" course homepage
    And I click on "Lock my group choice" "link"
    And I set the following fields to these values:
      | lockchoice | 0 |
    When I click on "#id_submitbutton" "css_element"
    And I click on "Lock my group choice" "link"
    Then the following fields match these values:
      | lockchoice | 0 |
