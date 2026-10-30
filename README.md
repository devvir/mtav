# MTAV

The web app where housing cooperatives organize their projects, from the first meeting
to the lottery that gives each family its home: families and their people, the units
and the site's plan, events, shared files, a forum, and a lottery that first protects
the worst-off family, then makes everyone as satisfied as possible, with a record anyone
in the project can check.

Built for Uruguay's housing cooperatives (FUCVAM's, FECOVI's and others) as a degree project
at FING, Universidad de la República; in Spanish, English, French, Italian and Portuguese.

## Running it

Docker (with Compose) and Bash are all it needs:

```sh
git clone https://github.com/devvir/mtav && cd mtav
./mtav up
```

Then http://localhost:8000, with the demo data (password `password`), and the mail it
sends at http://localhost:8025. `./mtav help` lists every command; `./mtav pest` and
`./mtav e2e` run the tests.

## Made of

Laravel 13 (PHP 8.5) on MariaDB, React 19 with Inertia 3 and Mantine 9, Reverb for live
updates, GLPK for the lottery. Released as one image on FrankenPHP, deployed blue/green.

## Documentation

[documentation/](documentation/README.md): architecture, the lottery, notifications,
data and backups, delivery, development and testing; and the thesis.
