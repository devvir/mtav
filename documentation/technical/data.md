# Data

What MTAV keeps, who sees it, what is recorded about what happens, and how it is copied
and given back. The public privacy page (`/privacy`, `resources/views/legal/<locale>/`)
says the same to the people concerned; when one changes, the other does.

## What is kept

- **Accounts** (`users`): name, email, password (hashed), and if the person gives them,
  phone, a few words, a picture, an ID number. Language and appearance preferences.
- **Projects**: families, memberships (who is in which family, who is an admin), unit
  types, units, the plan, invitations (only a hash of each link's token), events and who
  goes, shared files, forum topics and posts, chat conversations and messages, lottery runs and their records.
- **On the device**: the session and form cookies, appearance cookies, the pages kept for
  working offline (forgotten when someone else signs in there), which tours were seen.

## Who sees what

- People in a project see each other's name and picture. Email and phone, only if the
  person shares them; the project's admins see them always, and the ID number when given.
- Everything in a project (files included, served privately) is seen only by its people.
- Rankings stay with their families: the lottery's record shows each family's place,
  never another family's ranking.
- Superadmins see what the platform needs: projects, accounts, the activity and errors.

## The activity log

Every change and every way in is written to `activity_log`, for superadmins (console,
Activity): what was created, changed or deleted (projects, families, unit types, units,
memberships, invitations, events, files, forum topics and posts, lottery runs, platform
settings, accounts), by whom, on which project, from where, with what changed from what
to what; and signing in, out, failing to, resetting a password. Never what people read.

- Models opt in with `RecordsActivity`; `ActivityLog::record()` writes the rest.
- A person's own details are noted by field only (that their phone changed, not to
  what); their roles with values. Secrets (passwords, tokens) never.
- Names are kept as they were, so an entry still says who did it after their account
  is gone.
- Writing it never fails what it records.

## The error log

Free and kept by the app itself (`ErrorLog`, `error_log`): each error the app reports,
once per cause (its class, file and line), counted each time it comes back, with the
address and the person when there was one. Superadmins see them in the console (Errors)
and mark them solved; the deployment's owner is emailed the first time a new one
appears. On wherever the app runs for people, off while testing (`config/errors.php`).
A hosted service (Sentry, GlitchTip) can be added beside it later.

## Giving data back

- **Exports**: a project's admins download its people, families and units as
  spreadsheets (CSV, from Settings → Project), headings in their language.
- **The lottery's record**: anyone in the project reads it; admins download it whole.
- **Deleting an account**: the person does it from their profile, typing their email.
  They leave every project; what they gave (files, events, posts, lottery records) stays,
  without their name. A project's only admin must name another first; the deployment's
  owner cannot.
- **Deleting a project**: archived when it has a history (superadmins restore it, or
  delete it for good), else deleted at once.

## Backups

Two copies, by two means:

- **Before every deploy**, the environment's database is dumped on the host
  (`/mtav/<environment>/backups/`, the last 20 kept).
- **Every night**, a machine of the owner's pulls the production database and its
  uploaded files (`deploy/backup/pull`, from cron there): the host's gate streams them
  (`backup <environment> database|files`) to a key that can do nothing else. Each copy is
  checked before it is kept; 30 days are kept, and always the newest few.

Setting the nightly copy up, once:

1. `ssh-keygen -t ed25519 -f ~/.ssh/mtav-backup` on the machine that keeps the copies.
2. On the host, run the setup again with that key added:
   `sudo bash setup dev=dev.pub staging=staging.pub production=production.pub backups=mtav-backup.pub`.
3. `crontab -e` there: `30 3 * * * /path/to/mtav/deploy/backup/pull production /backups/mtav`.

Restoring a database copy (on the host, a person's decision):

```sh
cd /mtav/<environment>
gunzip -c <copy>.sql.gz | docker compose -p mtav-<environment> exec -T db \
  sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
```

and the files: `docker run --rm -i -v mtav-<environment>_files:/files alpine:3.22 tar xzf - -C /files < <copy>.tar.gz`.
