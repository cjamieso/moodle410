@block @block_skills_group @eclass-blocks-skills_group
Feature: Create skills_group settings
  In order to create the skills_group settings
  As a admin
  I need to go to the settings page and select a feedback and grouping

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
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Initialize skills_group settings
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | prefeedbackid | Feedback 1 |
      | groupingid | Grouping 1 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | maxsize | 6 |
      | threshold | 1 |
      | datecheck | 0 |
    And I press "Save changes"
    Then I should see "Edit skills group settings"

  @javascript
  Scenario: Initaliaze settings without a feedback
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | prefeedbackid | None |
      | groupingid | Grouping 1 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | maxsize | 6 |
      | datecheck | 0 |
    And I press "Save changes"
    Then I should see "Edit skills group settings"

