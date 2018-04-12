Feature: Discriminator support
  In order to support polymorphism
  As a client software developer
  I need single api endpoint for creating objects inheriting from common abstract class.

#  @createSchema
#  Scenario: Get a collection
#    When I send a "GET" request to "/code_repositories"
#    And print last JSON response

  @createSchema
  Scenario: Create item
    When I add "accept" header equal to "application/ld+json"
    And I add "content-type" header equal to "application/ld+json"
    And I send a POST request to "/code_repositories" with body:
    """
    {
      "type": "github",
      "branch": "master",
      "stargazer": "komik966"
    }
    """
    Then print last JSON response
