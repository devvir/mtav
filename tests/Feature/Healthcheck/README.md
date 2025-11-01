# Health Checks Test Group

The tests in this group focus on early warnings, asserting whether pages:

- can be loaded by an actor that should not have access
- cannot be loaded by the intended actors
- can or cannot be loaded in a context that should allow/deny access respectively

**IMPORTANT**
This Test Group does NOT validate the content of the pages it checks: it's supposed to be quick and simple. Other tests dive into more detailed assertions.

## Usage
To run the whole group: `mtav test --pest --group Feature.Healthcheck`
