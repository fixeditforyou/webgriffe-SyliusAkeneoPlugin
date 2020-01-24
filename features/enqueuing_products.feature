@enqueuing_products
Feature: Enqueuing products
  In order to import my products from Akeneo
  As a Store Owner
  I want to add them to the Akeneo PIM queue

  Scenario: There are no products modified since a given date
    When I run enqueue command with since date "2020-01-20 01:00:00"
    Then the command should have run successfully
    And there should be no product in the Akeneo queue

  Scenario: Enqueuing products modified since a given date
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a product "product-3" updated at "2020-01-22 08:15:08" on Akeneo
    When I run enqueue command with since date "2020-01-20 01:00:00"
    Then the command should have run successfully
    And the product "product-1" should not be in the Akeneo queue
    And the product "product-2" should be in the Akeneo queue
    And the product "product-3" should be in the Akeneo queue

  Scenario: The command cannot be run without since parameter
    When I run enqueue command with no since date
    Then the command should have thrown exception with message containing 'One of "--since" and "--since-file" paramaters must be specified'
    And there should be no product in the Akeneo queue

  Scenario: Run the command with bad since date
    When I run enqueue command with since date "bad date"
    Then the command should have thrown exception with message containing 'The "since" argument must be a valid date'
    And there should be no product in the Akeneo queue

  Scenario: There are no products modified since datetime read in file
    Given there is a file with name "last-date" and content "2020-01-20 01:00:00"
    When I run enqueue command with since file "last-date"
    Then the command should have run successfully
    And there should be no product in the Akeneo queue
    And there is a file with name "last-date" that contains a datetime

  Scenario: Enqueuing products modified since datetime read in file
    Given there is a product "product-1" updated at "2020-01-10 22:23:13" on Akeneo
    And there is a product "product-2" updated at "2020-01-21 09:54:12" on Akeneo
    And there is a file with name "last-date" and content "2020-01-20 01:00:00"
    When I run enqueue command with since file "last-date"
    Then the command should have run successfully
    And the product "product-1" should not be in the Akeneo queue
    And the product "product-2" should be in the Akeneo queue
    And there is a file with name "last-date" that contains a datetime

  Scenario: Run the command with not existent since file
    When I run enqueue command with since file "last-date"
    Then the command should have thrown exception with message containing 'does not exists'
    And there should be no product in the Akeneo queue
    And there is no file with name "last-date"
