@local @local_paradiso
Feature: Connect Paradiso
  In order to add Paradiso activities to courses
  As an admin
  I need to connect Moodle to our Paradiso workspace and register its tools

  Background:
    Given I log in as "admin"

  Scenario: A new site offers Connect with Paradiso
    When I navigate to "Plugins > Local plugins > Paradiso integration > Connect Paradiso" in site administration
    Then I should see "No Paradiso tools are registered from this workspace yet."
    And "Connect with Paradiso" "button" should exist
    And I should see "create a free account or log in"

  Scenario: Registering by hand needs the tool address first
    When I navigate to "Plugins > Local plugins > Paradiso integration > Connect Paradiso" in site administration
    And I click on "Connect with a registration link instead" "text"
    Then I should see "Set the Paradiso tool address in the plugin settings first, or use Connect with Paradiso above."

  Scenario: The registration link must use https
    Given the following config values are set as admin:
      | workspaceurl | https://acme.paradiso.example | local_paradiso |
    And I navigate to "Plugins > Local plugins > Paradiso integration > Connect Paradiso" in site administration
    And I click on "Connect with a registration link instead" "text"
    When I set the field "Registration link" to "http://acme.paradiso.example/public/lti/register?token=x"
    And I press "Register"
    Then I should see "The Registration link must be a valid URL that starts with https."

  Scenario: A reply from Paradiso without a matching request is refused
    When I visit "/local/paradiso/index.php?action=connected&state=forged"
    Then I should see "That connection request has expired or was already used."
