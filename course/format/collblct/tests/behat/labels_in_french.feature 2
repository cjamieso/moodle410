@format @format_collblct
Feature: View collapsed labels in other languages
  In order to view collapsed labels in French
  As an user
  I need to switch the course language to French and view the course page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1 | collblct | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Test | Teacher | teacher1@ualberta.ca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | name | intro | introformat | content | course | section | idnumber |
      | label | Test Label | <p>bunch of text</p> | 1 | Empty content page | C1 | 1 | label1 |
      | page | Test Page 1 | Required description | 1 | Empty content page | C1 | 1 | page1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I click on "Open all" "button"
    And I indent right "Test Page 1" activity
    And I log out

  @javascript
  Scenario: View collapsed labels in French
    Given I log in as "admin"
    And I navigate to "Language > Language packs" in site administration
    When I set the field "Available language packs" to "fr"
    And I press "Install selected language pack(s)"
    Then I should see "Language pack 'fr' was successfully installed"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Force language | fr |
    And I press "Save and display"
    And I click on "#toggles-all-opened" "css_element"
    Then "#acc1 h7" "css_element" should be visible
