Feature:: Behat integration
  In order to improve database usage in behat tests
  developers need to be able to use DamaDoctrineTestBundle with Behat

  Scenario: Changing DB state
    Given there are 0 rows
    When I insert a new row
    Then there is 1 row

  Scenario: Change db state within rolled back transaction
    Given there are 0 rows
    When I begin a transaction
    And I insert a new row
    Then there is 1 row
    When I rollback the transaction
    Then there are 0 rows

  Scenario: Change db state within committed transaction
    Given there are 0 rows
    When I begin a transaction
    And I insert a new row
    And I commit the transaction
    Then there is 1 row

  Scenario: Change db state with savepoint
    Given there are 0 rows
    When I create a savepoint named "foo"
    And I insert a new row
    Then there is 1 row
    When I rollback the savepoint named "foo"
    Then there are 0 rows
