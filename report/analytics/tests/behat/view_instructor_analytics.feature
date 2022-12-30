@report @report_analytics
Feature: View analytics as an instructor
  In order to view analytics
  As an instructor
  I need to go to the analytics report page and add an analytics graph

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | cjamieso@ualberta.ca |
      | student1 | Test | Student1 | cjamieso@gmx.ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: View analytics report charts as an instructor
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I press apply filter on "activitychart"
    Then I should see "Activities" in the ".activitychart .filterheader" "css_element"
    When I add a "Engagement over time" graph
    And I press apply filter on "activitytimelinechart"
    Then I should see "Activities" in the ".activitytimelinechart .filterheader" "css_element"
    When I add a "Forum engagement" graph
    And I press apply filter on "forumchart"
    Then I should see "Activities" in the ".forumchart .filterheader" "css_element"
    When I add a "Forum engagement over time" graph
    And I press apply filter on "forumtimelinechart"
    Then I should see "Activities" in the ".forumtimelinechart .filterheader" "css_element"
    When I add a "Forum posts by user(s)" graph
    And I press apply filter on "userpostschart"
    Then I should see "Activities" in the ".userpostschart .filterheader" "css_element"
    When I add a "Grades vs. Actions" graph
    And I press apply filter on "gradechart"
    Then I should see "Students" in the ".gradechart .filterheader" "css_element"

  @javascript
  Scenario: Cannot view analytics as a student
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Analytics"
