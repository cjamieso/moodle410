@report @report_analytics
Feature: Toggle graph layout
  In order to toggle the graph layout
  As an instructor
  I need to add two graphs and switch to my desired layout

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
    And the following "activities" exist:
      | activity | name | intro | content | course | idnumber |
      | page | Test Page 1 | Required description | Empty content page | C1 | page1 |
      | page | Test Page 2 | Required description | Empty content page | C1 | page2 |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 2"
    And I log out

  @javascript
  Scenario: Toggle layout on analytics graphs
    When I log in as "teacher1"
    And I change window size to "large"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Content engagement" graph
    And I press apply filter on "activitychart"
    When I add a "Engagement over time" graph
    And I press apply filter on "activitytimelinechart"
    Then ".activitychart.col-md-12" "css_element" should be visible
    And ".activitytimelinechart.col-md-12" "css_element" should be visible
    And ".activitychart.col-md-6" "css_element" should not exist
    And ".activitytimelinechart.col-md-6" "css_element" should not exist
    When I click on ".layoutmenu .chart-grid" "css_element"
    Then ".activitychart.col-md-6" "css_element" should be visible
    And ".activitytimelinechart.col-md-6" "css_element" should be visible
    And ".activitychart.col-md-12" "css_element" should not exist
    And ".activitytimelinechart.col-md-12" "css_element" should not exist
    When I click on ".layoutmenu .chart-list" "css_element"
    Then ".activitychart.col-md-12" "css_element" should be visible
    And ".activitytimelinechart.col-md-12" "css_element" should be visible
    And ".activitychart.col-md-6" "css_element" should not exist
    And ".activitytimelinechart.col-md-6" "css_element" should not exist
