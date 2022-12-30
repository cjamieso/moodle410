@block @block_skills_group @eclass-blocks-skills_group
Feature: Graph students' results
  In order to view my students' results graphically
  As a instructor
  I need to select a question and graph the question

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "groupings" exist:
      | name       | course  | idnumber  |
      | Grouping 1 | C1 | GROUPING1 |
    And the following "activities" exist:
      | activity | name | intro | course | section | idnumber | anonymous |
      | feedback | Feedback 1 | Test feedback description | C1 | 1 | feedback1 | 2 |
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
    And I am on the "Feedback 1" "feedback activity" page logged in as teacher1
    And I click on "Edit questions" "link" in the "region-main" "region"
    When I add a "Multiple choice" question to the feedback with:
      | Question         | Skill 1 |
      | Multiple choice type | Multiple choice - single answer |
      | Multiple choice values | 0\n1\n2\n3  |
    When I add a "Multiple choice" question to the feedback with:
      | Question         | Skill 2 |
      | Multiple choice type | Multiple choice - single answer |
      | Multiple choice values | 0\n1\n2\n3  |
    And I am on "Course 1" course homepage with editing mode on
    And I duplicate "Feedback 1" activity editing the new copy with:
      | Name | Feedback 2 |
    And I am on "Course 1" course homepage
    And I add the "Group Sign-up" block
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | prefeedbackid | Feedback 1 |
      | postfeedbackid | Feedback 2 |
      | groupingid | Grouping 1 |
      | instructorgroups | 0 |
      | allownaming | 1 |
      | maxsize | 6 |
    And I press "Save changes"
    And I log out
    And I am on the "Feedback 1" "feedback activity" page logged in as student1
    And I follow "Answer the questions"
    And I answer feedback with "1"
    And I press "Submit your answers"
    And I log out
    And I am on the "Feedback 1" "feedback activity" page logged in as student2
    And I follow "Answer the questions"
    And I answer feedback with "2"
    And I press "Submit your answers"
    And I log out

  @javascript
  Scenario: Graph students' results
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Graph/Export feedback results" "link"
    When I toggle "Skill 1" on the "item" graph filter
    And I click on ".d3button" "css_element"
    Then the following bars exist:
      | label | Pre-course | Post-course |
      | 0 | 0 | 0 |
      | 1 | 1 | 0 |
      | 2 | 1 | 0 |
      | 3 | 0 | 0 |
    When I toggle "Skill 2" on the "item" graph filter
    And I click on ".d3button" "css_element"
    Then the following bars exist:
      | label | Pre-course | Post-course |
      | 0 | 0 | 0 |
      | 1 | 2 | 0 |
      | 2 | 2 | 0 |
      | 3 | 0 | 0 |


