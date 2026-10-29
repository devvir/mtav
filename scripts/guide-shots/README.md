# Pictures of the manuals

The illustrated manuals (`resources/views/guides/<locale>/guide.<role>.html`) show the real
app: `shots.mjs` takes each picture from an app seeded with the demo data, cut to what
matters and marked (an outline and its number, as the manuals' text refers to them).

Once per language, the demo accounts set to it first:

```sh
php artisan migrate:fresh --seed     # the demo data, on an app of its own
php artisan tinker --execute="App\Models\User::query()->update(['locale' => 'es_UY'])"
BASE=http://127.0.0.1:8010 LOCALE=es_UY node scripts/guide-shots/shots.mjs
```

The pictures land in `public/images/guide/<locale>/`; a test checks that every picture a
guide names is there. Take them again after considerable UI changes, not every small one.
