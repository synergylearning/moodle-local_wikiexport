@local @local_wikiexport @javascript
Feature: Export wiki
  As a teacher
  I need to export a wiki

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | course  | idnumber | activity  |name    | firstpagetitle | wikimode      |
      | C1      | wiki1    | wiki      | wiki 1 | First page     | collaborative |
      | C1      | wiki2    | wiki      | wiki 2 | First page     | collaborative |
    And I log in as "admin"
    And I am on the "wiki1" "Activity" page
    # Create first page.
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | [[page 1]] [[page 2]] [[page 3]] [[page 4]]|
      | Tags        | simulation|
    And I press "Save"
    # Create page 1.
    And I follow "page 1"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 1 content    |
      | Tags        | simulation, zoom  |
    And I press "Save"
    # Create page 2.
    And I follow "wiki 1"
    And I follow "page 2"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 2 content    |
      | Tags        | exam, observation |
    And I press "Save"
    # Create page 3.
    And I follow "wiki 1"
    And I follow "page 3"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 3 content   |
      | Tags        | validation, exam |
    And I press "Save"
    # Create page 4.
    And I follow "wiki 1"
    And I follow "page 4"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page 4 content |
      | Tags        | zoom           |
    And I press "Save"
    And I log out

  @javascript
  Scenario: Export wiki pages with tags
    Given I am logged in as "teacher1"
    And I am on the "wiki1" "Activity" page
    And "export-as-pdf" "link" should be visible
    When I click on "export-as-pdf" "link"
    Then the field "simulation" matches value "1"
    And the field "zoom" matches value "1"
    And the field "exam" matches value "1"
    And the field "observation" matches value "1"
    And the field "validation" matches value "1"
    And I click on "Select all/none" "link"
    And the following fields match these values:
      | simulation  | 0 |
      | zoom        | 0 |
      | exam        | 0 |
      | observation | 0 |
      | validation  | 0 |
    And following "Export" should download between "100000" and "300000" bytes
    And the "Export" "button" should be enabled
    And following "Export" should download between "100000" and "300000" bytes

  @javascript
  Scenario: Export wiki pages without tags
    Given I am logged in as "teacher1"
    And I am on the "wiki2" "Activity" page
    # Create first page.
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | page test|
    And I press "Save"
    And following "export-as-pdf" should download between "100000" and "300000" bytes
