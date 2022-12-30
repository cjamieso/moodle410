@report @report_analytics
Feature: View analytics as a student
  In order to view student analytics
  As an student
  I need to go to the course page and view the student analytics report

  # The testing here is not as extensive as the instructor analytics (where all
  # filters are tested).  Some basic tests are performed here, since the code is
  # shared.  We do try each type of graph to ensure they can all be added.
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
      | page | Test Page 3 | Required description | Empty content page | C1 | page3 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum type | Standard forum for general use |
      | Description | Test forum description |
    And I log out
    And I am on "Course 1" course homepage
    And I log in as "student1"
    And I add a new discussion to "Test forum name" forum with:
      | Subject | Forum post subject |
      | Message | This is the body |
    And I follow "Forum post subject"
    And I am on "Course 1" course homepage
    And I follow "Test Page 1"
    And I am on "Course 1" course homepage
    And I follow "Test Page 2"
    And I follow "Profile" in the user menu
    And I follow "Analytics (Beta)"
    And I set the following fields to these values:
      | coursefilter | Course 1 |

  @javascript
  Scenario: View content engagement on student analytics reports
    When I add a "Content engagement" graph
    And I press apply filter on "activitychart"
    Then the following grouped bars exist:
      | label | Interactions | Views |
      | All pages | 0 | 2 |
    And graph title should be "Displaying results for: Test Student1"

  @javascript
  Scenario: View engagement over time on student analytics reports
    And I add a "Engagement over time" graph
    And I press apply filter on "activitytimelinechart"
    Then the following series lines exist:
      | label | events |
      | All pages | 2 |
    And graph title should be "Displaying results for: Test Student1"

  @javascript
  Scenario: View forum engagement on student analytics reports
    When I add a "Forum engagement" graph
    And I press apply filter on "forumchart"
    Then the following grouped bars exist:
      | label | Discussion viewed | Post created | Discussion created |
      | Test forum name | 1 | 0 | 1 |

  @javascript
  Scenario: View forumn engagement over time on student analytics reports
    When I add a "Forum engagement over time" graph
    And I press apply filter on "forumtimelinechart"
    Then the following series lines exist:
      | label | events |
      | Test forum name | 1 |

  @javascript
  Scenario: View forum posts on student analytics reports
    When I add a "Forum posts by user(s)" graph
    And I press apply filter on "userpostschart"
    Then I should see "This is the body"

  @javascript
  Scenario: View grades vs. actions on student analytics reports
    When I add a "Grades vs. Actions" graph
    And I press apply filter on "gradechart"
    Then the following nodes exist:
      | name | Grade | Actions |
      | Test Student1 | 0 | 11 |

