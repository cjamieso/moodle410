@block @block_nurs_navigation
Feature: View Activities
  In order to view exams, assignments, and quests
  As a student
  I need to click the activity link

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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "Course Sections" block

  @javascript
  Scenario: View exams and assignments as a student
    When I add a "Quiz" to section "1" and I fill the form with:
      | Name | Quiz 3 |
      | Description | description |
      | Availability | Hide from students |
    And I add a "Quiz" to section "1"
    And I set the following fields to these values:
      | Name | Quiz 4 |
      | Description | description |
    And I expand all fieldsets
    And I press "Add restriction..."
    And I click on "Date" "button" in the "Add restriction..." "dialogue"
    And I set the field "direction" to "until"
    And I set the field "x[year]" to "2013"
    And I set the field "x[month]" to "March"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Exams" in the "Course Sections" "block"
    And I should see "Assignments" in the "Course Sections" "block"
    And I click on "Exams" "link" in the "Course Sections" "block"
    Then I should see "Quiz 1" in the "#page-content" "css_element"
    And I should see "Quiz 2" in the "#page-content" "css_element"
    And I should not see "Quiz 3" in the "#page-content" "css_element"
    And I should see "Quiz 4" in the "#region-main .dimmed_text" "css_element"
    And I click on "Quiz 2" "link" in the "#page-content" "css_element"
    Then I should see "Grading method: Highest grade"
    When I am on "Course 1" course homepage
    And I click on "Assignments" "link" in the "Course Sections" "block"
    Then I should see "Assignment 1" in the "#page-content" "css_element"
    And I should see "Assignment 2" in the "#page-content" "css_element"

  @javascript
  Scenario: Exams link removed
    When I configure the "Course Sections" block
    And I open the autocomplete suggestions list
    And I click on "Exams" item in the autocomplete list
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Exams" in the "Course Sections" "block"

  @javascript
  Scenario: Assignments link removed
    When I configure the "Course Sections" block
    And I open the autocomplete suggestions list
    And I click on "Assignments" item in the autocomplete list
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Assignments" in the "Course Sections" "block"

  @javascript
  Scenario: Quests link removed
    When I configure the "Course Sections" block
    And I open the autocomplete suggestions list
    And I click on "Quests" item in the autocomplete list
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Quests" in the "Course Sections" "block"
