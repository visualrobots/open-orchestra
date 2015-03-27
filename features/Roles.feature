Feature: Roles

  Scenario: Add role and delete it
    Given I am authenticated as "nicolas"
    When I click on the element with css selector ".fa-desktop"
    And I click on the element with css selector "#nav-role"
    And I wait for element "$('.bh-button-add').length > 0"
    And I click on the element with css selector ".bh-button-add"
    Then I should wait until i see "Descriptions"
    And I should be on "/admin/#role/add"
    When I fill in "role_name" with "test role name"
    And I fill in "role_descriptions_0_value" with "test role description english"
    And I press "role_submit"
    Then I should wait until i see "The role has been successfully created"
    When I click on the element with css selector ".back-to-list"
    And I wait for element "$('.next').length > 0"
    And I click on the element with css selector ".next"
    And I click on the element with css selector ".next"
    Then I should see "test role description english"
    And I should be on "/admin/#role/list"
    When I click on the last element ".btn-danger"
    Then I should wait until i see "Delete this element"
    When I press "bot2-Msg1"
    Then I should not see "test role description english"
    When I reload the page
    Then I should not see "test role description english"
