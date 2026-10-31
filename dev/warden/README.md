# The warden

One page, on a developer's machine, watching MTAV from above: this machine, the local stacks, the
sites, the server and what each environment runs, pipelines, the repository, the Claude accounts'
usage and the local app's figures.

```sh
./mtav warden        # http://localhost:9190
```

It sits on top of the app rather than in it: it needs this machine's `docker`, `gh` and `ssh`, and
it is left out of every image, so no deployment can run it. It listens on this machine alone, only
reads, and reads nothing with nobody looking. Each source (`sources.mjs`) has its own pace and fails
on its own. Claude's usage comes from the readings the accounts' folder already holds: it never
touches a login.

To add something to watch: a source in `sources.mjs`, a view in `page.html`.
