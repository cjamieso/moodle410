@qtype @qtype_calculatedmedical
Feature: View rounding types available to calcualted medical question
  In order to view rounding types
  As a teacher
  I want to create/edit questions from the question bank

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | weeks |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  # Verify that injection of new rounding type is included.
  @javascript
  Scenario: Set rounding to exact rounding with maximum
    When  I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Questions" node in "Course administration > Question bank"
    And I press "Create a new question ..."
    And I set the field "item_qtype_calculatedmedical" to "1"
    And I press "Add"
    Then I should see "Adding a medical calculated question"
    When I set the field "Format" to "exact rounding with maximum"
    And I set the following fields to these values:
      | Question name | Test |
      | Question text |<p>Order: Heparin 10 units/kg/hour IV.<br>Supply: 25,000 units of heparin/250 mL D5W.<br>Weight: {adultkg} kg.</p><p>What would you set the pump at in mL/hour?</p> |
      | Choice 1 | {={adultkg}*10/25000*250} mL per hour |
      | Grade | 100% |
      | id_correctanswerformat_0  | exact rounding with maximum |
      | id_correctanswerlength_0 | 2 |
      | Choice 2 | {={adultkg}*10/25000*250/2} mL per hour |
      | id_correctanswerformat_1  | exact rounding with maximum |
      | id_correctanswerlength_1 | 2 |
      | Choice 3 | {={adultkg}*10/25000*250*5} mL per hour |
      | id_correctanswerformat_2  | exact rounding with maximum |
      | id_correctanswerlength_2 | 2 |
      | Choice 4 | {={adultkg}*10/25000*250*10} mL per hour |
      | id_correctanswerformat_3  | exact rounding with maximum |
      | id_correctanswerlength_3 | 2 |
    Then I should see "exact rounding with maximum"
    When I press "id_submitbutton"
    Then I should see "Choose wildcards dataset properties"
