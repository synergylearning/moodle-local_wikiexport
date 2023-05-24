@local @local_wikiexport @javascript
Feature: Wikis can be exported as PDF or epub

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity | name   | idnumber | course |
      | wiki     | Wiki 1 | WIKI01   | C1     |
    And the following "users" exist:
      | username |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I am on the "Wiki 1" "wiki activity" page logged in as "student1"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | <p>This is the introduction - find out more about [[Cats]], [[Dogs]] and [[Fish]]</p> |
    And I press "Save"
    And I follow "Cats"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | <p>This is information all about cats</p> |
    And I press "Save"
    And I am on the "Wiki 1" "wiki activity" page
    And I follow "Dogs"
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | <p>Here is some information about dogs</p> |
    And I press "Save"

  Scenario: A student can export the wiki as PDF and epub
    When I navigate to "Export as PDF" in current page administration
    And I wait "1" seconds
    And I navigate to "Export as epub" in current page administration
    And I wait "1" seconds
    # All we can check is that there are no errors generated.
    Then I should see "Wiki 1"
