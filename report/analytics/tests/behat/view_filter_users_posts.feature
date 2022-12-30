@report @report_analytics
Feature: Display a user's forum posts as an instructor
  In order to display a user's posts
  As an instructor
  I need to go to the analytics report page, add the widget, and input a filter

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | cjamieso@ualberta.ca |
      | student1 | Test | Student1 | cjamieso@gmx.ualberta.ca |
      | student2 | Test | Student2 | craig.jamieson@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum type | Standard forum for general use |
      | Description | Test forum description |
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test2 forum name |
      | Forum type | Standard forum for general use |
      | Description | Test2 forum description |
    And I log out
    And I am on "Course 1" course homepage
    And I log in as "student1"
    And I add a new discussion to "Test forum name" forum with:
      | Subject | Forum post subject |
      | Message | Posting slowly as six word message |
    And I add a new discussion to "Test forum name" forum with:
      | Subject | Forum post subject 2 |
      | Message | Writing something four words |
    And I log out

  @javascript
  Scenario: Basic filtering on user posts chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Forum posts by user(s)" graph
    And I press apply filter on "userpostschart"
    Then I should see "Displaying results for all students"
    And I should see "Posting slowly as six word message"
    And I should see "Writing something four words"
    And I should see "Test Student1" in the ".d3chart" "css_element"
    When I click on "Students" "button"
    And I toggle "Test Student2" on the "student" filter
    And I press apply filter on "userpostschart"
    Then I should see "Test Student2" in the ".d3chart" "css_element"
    And I should see "No posts found"
    When I toggle "Test Student2" on the "student" filter
    And I toggle "Test Student1" on the "student" filter
    And I toggle "Test2 forum name" on the "activity" filter
    And I press apply filter on "userpostschart"
    Then I should see "No posts found"
    When I toggle "Test2 forum name" on the "activity" filter
    And I add "tomorrow" to the "from" date
    And I add "tomorrow" to the "to" date
    And I press apply filter on "userpostschart"
    Then I should see "Total posts: 0"
    When I add "yesterday" to the "from" date
    And I press apply filter on "userpostschart"
    Then I should see "Posting slowly as six word message"
    And I should see "Writing something four words"
    And I should see "Test Student1" in the ".d3chart" "css_element"
    When I click on ".undo i" "css_element"
    Then I should see "Total posts: 0"
    When I click on ".redo i" "css_element"
    Then I should see "Posting slowly as six word message"

  @javascript
  Scenario: Filter by word count from user posts chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Forum posts by user(s)" graph
    And I click on "Students" "button"
    And I toggle "Test Student1" on the "student" filter
    And I click on ".advancedtoggle" "css_element"
    And I set "max" words filter to "5"
    And I press apply filter on "userpostschart"
    Then I should see "Writing something four words"
    Then I should not see "Posting slowly as six word message"

  @javascript
  Scenario: Advanced export features from user posts chart
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Analytics (Beta)" in current page administration
    And I add a "Forum posts by user(s)" graph
    And I click on "Students" "button"
    And I toggle "Test Student1" on the "student" filter
    And I press apply filter on "userpostschart"
    Then the export on "userposts" is between "3300" and "3400" bytes
    When I click on ".wordcloud i" "css_element"
    Then the word cloud should contain at least "6" words
    When I click on ".pngexport i" "css_element"
    Then the png export should be at least "20000" bytes

  @javascript
  Scenario: Export list of posts as a student
    When I log in as "student1"
    And I follow "Profile" in the user menu
    And I follow "Analytics (Beta)"
    And I set the following fields to these values:
      | coursefilter | Course 1 |
    And I add a "Forum posts by user(s)" graph
    And I press apply filter on "userpostschart"
    Then the export on "userposts" is between "3300" and "3400" bytes
