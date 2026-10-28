@local @local_paradiso
Feature: Connect Paradiso
  In order to add Paradiso activities to courses
  As an admin
  I need to point Moodle at our Paradiso workspace and register its tools

  Background:
    Given I log in as "admin"

  Scenario: The connect page asks for the workspace address first
    When I navigate to "Plugins > Local plugins > Paradiso integration > Connect Paradiso" in site administration
    Then I should see "Set your Paradiso workspace address in the plugin settings first."

  Scenario: The connect page shows the registration form once the workspace is set
    Given the following config values are set as admin:
      | workspaceurl | https://acme.paradiso.example | local_paradiso |
    When I navigate to "Plugins > Local plugins > Paradiso integration > Connect Paradiso" in site administration
    Then I should see "No Paradiso tools are registered from this workspace yet."
    And I should see "Registration link"

  Scenario: The registration link must use https
    Given the following config values are set as admin:
      | workspaceurl | https://acme.paradiso.example | local_paradiso |
    And I navigate to "Plugins > Local plugins > Paradiso integration > Connect Paradiso" in site administration
    When I set the field "Registration link" to "http://acme.paradiso.example/public/lti/register?token=x"
    And I press "Register"
    Then I should see "The Registration link must be a valid URL that starts with https."
