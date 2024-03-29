@format @format_collblct @javascript
Feature: Toggle highlighted section
  Highlighted sections are always open on page load.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email              |
      | dennis   | Dennis    | collblct  | dennis@collblct.com |
      | daisy    | Daisy     | collblct  | daisy@collblct.com |
    And the following "courses" exist:
      | fullname | shortname | format  | numsections |
      | CollTop  | CT        | collblct | 3           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | dennis   | CT     | editingteacher |
      | daisy    | CT     | student        |
    And the following config values are set as admin:
      | config                | value | plugin         |
      | defaultuserpreference | 0     | format_collblct |
    And I am on the "CT" "Course" page logged in as "dennis"

  Scenario: Highlighted section open when loading the page
    When I turn editing mode on
    And I turn section "2" highlighting on
    And I turn editing mode off
    Then "#toggledsection-1" "css_element" should not be visible
    And "#toggledsection-2" "css_element" should be visible
    And "#toggledsection-3" "css_element" should not be visible
    And I log out
    And I am on the CT Course page logged in as daisy
    And "#toggledsection-1" "css_element" should not be visible
    And "#toggledsection-2" "css_element" should be visible
    And "#toggledsection-3" "css_element" should not be visible

  Scenario: Highlighted section open when reloading the page
    When I turn editing mode on
    And I turn section "2" highlighting on
    And I turn editing mode off
    And I reload the page
    Then "#toggledsection-1" "css_element" should not be visible
    And "#toggledsection-2" "css_element" should be visible
    And "#toggledsection-3" "css_element" should not be visible
    And I log out
    And I am on the CT Course page logged in as daisy
    And I reload the page
    And "#toggledsection-1" "css_element" should not be visible
    And "#toggledsection-2" "css_element" should be visible
    And "#toggledsection-3" "css_element" should not be visible
