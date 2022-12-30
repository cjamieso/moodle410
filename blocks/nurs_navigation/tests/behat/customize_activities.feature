@block @block_nurs_navigation
Feature: Customize Activities
  In order to customize activities
  As a instructor
  I need to flag an activity as a quiz, assignment, or quest

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
    And the following "activities" exist:
      | activity | name | intro | course | idnumber |
      | quiz | Quiz 1 | description | C1 | quiz1 |
      | quiz | Quiz 2 | description | C1 | quiz2 |
      | assign | Assignment 1 | description | C1 | assign1 |
      | assign | Assignment 2 | description | C1 | assign2 |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Course Sections" block

  Scenario: Customize activities as an instructor
    When I click on "Edit activities" "link" in the "Course Sections" "block"
    And I set the following fields to these values:
      | Quiz 1 | Quiz |
      | Quiz 2 | Quest |
      | Assignment 1 | Quest |
      | Assignment 2 | Assignment |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Exams" "link" in the "Course Sections" "block"
    Then I should see "Quiz 1" in the "#page-content" "css_element"
    And I should not see "Quiz 2" in the "#page-content" "css_element"
    And I am on "Course 1" course homepage
    And I click on "Assignments" "link" in the "Course Sections" "block"
    Then I should not see "Assignment 1" in the "#page-content" "css_element"
    And I should see "Assignment 2" in the "#page-content" "css_element"
    And I am on "Course 1" course homepage
    And I click on "Quests" "link" in the "Course Sections" "block"
    Then I should see "Quiz 2" in the "#page-content" "css_element"
    And I should see "Assignment 1" in the "#page-content" "css_element"

  @javascript
  Scenario: Override disabling of activities
    When I configure the "Course Sections" block
    And I open the autocomplete suggestions list
    And I click on "Exams" item in the autocomplete list
    And I click on "None" item in the autocomplete list
    And I press "Save changes"
    Then I should see "Exams" in the "Course Sections" "block"

  @javascript
  Scenario: Customize activities text
    When I configure the "Course Sections" block
    And I set the following fields to these values:
      | Exams | Quizzes |
      | Quests | Que? |
    And I press "Save changes"
    Then I should see "Quizzes" in the "Course Sections" "block"
    And I should not see "Exams" in the "Course Sections" "block"
    And I should see "Que?" in the "Course Sections" "block"
    And I should not see "Quests" in the "Course Sections" "block"
