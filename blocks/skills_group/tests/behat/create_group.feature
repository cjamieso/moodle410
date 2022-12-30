@block @block_skills_group @eclass-blocks-skills_group
Feature: Create a group
  In order to create group
  As a admin
  I need to go to the my course page and create a group

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "groupings" exist:
      | name       | course  | idnumber  |
      | Grouping 1 | C1 | GROUPING1 |
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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | prefeedbackid | None |
      | groupingid | Grouping 1 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | allowadding | 1 |
      | maxsize | 6 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Create a group
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "None"
    Given I set the following fields to these values:
      | creategroupcheck | 1 |
      | creategroup | Test Group |
    When I click on "#id_submitbutton" "css_element"
    Then I should see "Add Users to Group"
    And the field "allowjoincheck" matches value ""
    And the field "note" matches value ""

  @javascript
  Scenario: Create a group with blank name
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "None"
    Given I set the following fields to these values:
      | creategroupcheck | 1 |
    When I click on "#id_submitbutton" "css_element"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "Team 01"

  @javascript
  Scenario: Allow others to join a group
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "None"
    Given I set the following fields to these values:
      | creategroupcheck | 1 |
      | creategroup | Test Group |
      | allowjoincheck | 1 |
    When I click on "#id_submitbutton" "css_element"
    Then I should see "Add Users to Group"
    And the field "allowjoincheck" matches value "1"

  @javascript
  Scenario: Set a note
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "None"
    Given I set the following fields to these values:
      | creategroupcheck | 1 |
      | creategroup | Test Group |
      | note | Group 1, Project 1 |
    When I click on "#id_submitbutton" "css_element"
    Then I should see "Add Users to Group"
    And the field "note" matches value "Group 1, Project 1"
