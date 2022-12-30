@block @block_skills_group @eclass-blocks-skills_group
Feature: Edit a group
  In order to edit a group's members
  As a student
  I need to go add/remove members on the edit page

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
      | student3 | Test | Student3 | student3@ualberta.ca |
      | student4 | Test | Student4 | student4@ualberta.ca |
      | student5 | Test | Student5 | student5@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
      | student5 | C1 | student |
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
      | allowadding | 1 |
      | maxsize | 4 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Add members to group
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    # This tries empty submit (valid) -> adds only user to group.
    And I press "Save changes"
    Then "student1" should be in group "Team Awesome"
    When I open the autocomplete suggestions list
    And I click on "Test Student2" item in the autocomplete list
    And I click on "Test Student3" item in the autocomplete list
    And I press "Save changes"
    And I wait until the page is ready
    Then "student1" should be in group "Team Awesome"
    And "student2" should be in group "Team Awesome"
    And "student3" should be in group "Team Awesome"

  @javascript
  Scenario: Add too many members to group
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
      | student3 | G1 |
      | student4 | G1 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    When I open the autocomplete suggestions list
    And I click on "Test Student5" item in the autocomplete list
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "ERROR: Too many members in group"
    And "student5" should not be in group "Team Awesome"

  @javascript
  Scenario: Add/removing not permitted
    When the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
      | student3 | G1 |
      | student4 | G1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Edit skills group settings" "link"
    And I set the following fields to these values:
      | allowadding | 0 |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should not see "Group Members"

  @javascript
  Scenario: Remove members from group
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
      | student3 | G1 |
      | student4 | G1 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    And I click on "//span[@role='option' and contains(., 'Test Student2')]" "xpath_element"
    And I press "Save changes"
    And I wait until the page is ready
    And I click on "//span[@role='option' and contains(., 'Test Student3')]" "xpath_element"
    And I press "Save changes"
    And I wait until the page is ready
    And "student2" should not be in group "Team Awesome"
    And "student3" should not be in group "Team Awesome"

  @javascript
  Scenario: See locked students
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Lock my group choice" "link"
    Then I should see "Team Awesome"
    When I set the following fields to these values:
      | lockchoice | 1 |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Create/Edit a group" "link"
    Then I should see "Locked members:"
    And I should see "Test Student2" in the "//div[@id='id_error_locked']/../div[@class='form-control-static']" "xpath_element"
