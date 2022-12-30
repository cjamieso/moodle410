@block @block_nurs_navigation
Feature: View Section Icons
  In order to view section icons
  As a student
  I need to access the cousre

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | cjamieso@ualberta.ca |
      | student1 | Test | Student | cjamieso@gmx.ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | admin    | C1 | editingteacher |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Course Sections" block

  Scenario: View exams and assignments as a student
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Topic 1" in the "Course Sections" "block"
    Then I should not see "Topic 5" in the "Course Sections" "block"
    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I configure the "Course Sections" block
    And I set the field "Show all sections" to "1"
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Topic 1" in the "Course Sections" "block"
    Then I should see "Topic 5" in the "Course Sections" "block"
