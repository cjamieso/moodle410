@block @block_skills_group @eclass-blocks-skills_group
Feature: Use instructor created groups
  In order to use instructor created groups
  As a student
  I need to go join a group created by the instructor

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "groupings" exist:
      | name       | course  | idnumber  |
      | Grouping 1 | C1 | GROUPING1 |
    And the following "groups" exist:
      | name | course | idnumber |
      | Team 01 | C1 | T1 |
      | Team 02 | C1 | T2 |
      | Team 03 | C1 | T3 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GROUPING1 | T1 |
      | GROUPING1 | T2 |
      | GROUPING1 | T3 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | teacher1@ualberta.ca |
      | student1 | Test | Student | student1@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | prefeedbackid | None |
      | groupingid | Grouping 1 |
      | instructorgroups | 1 |
      | allownaming | 0 |
      | maxsize | 6 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Join an instructor created group
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Create/Edit a group"
    When I click on "Join existing group" "link"
    Then I should see "Team 01"
    And I should see "Team 02"
    And I should see "Team 03"
    When I click on "//div[@name='Team 01']/i" "xpath_element"
    Then I should see "Successfully joined group." in the ".modal-body" "css_element"
    When I am on "Course 1" course homepage
    Then I should see "Edit group"

  @javascript
  Scenario: Paging for join group selector
    Given the following "groups" exist:
      | name | course | idnumber |
      | Team 04 | C1 | T4 |
      | Team 05 | C1 | T5 |
      | Team 06 | C1 | T6 |
      | Team 07 | C1 | T7 |
      | Team 08 | C1 | T8 |
      | Team 09 | C1 | T9 |
      | Team 10 | C1 | T10 |
      | Team 11 | C1 | T11 |
      | Team 12 | C1 | T12 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GROUPING1 | T4 |
      | GROUPING1 | T5 |
      | GROUPING1 | T6 |
      | GROUPING1 | T7 |
      | GROUPING1 | T8 |
      | GROUPING1 | T9 |
      | GROUPING1 | T10 |
      | GROUPING1 | T11 |
      | GROUPING1 | T12 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "Join existing group" "link"
    Then I should see "Team 10"
    And I should not see "Team 11"
    And I should not see "Team 12"
    When I click on "//a[contains(., 'Next')]" "xpath_element"
    Then I should not see "Team 10"
    And I should see "Team 11"
    And I should see "Team 12"
