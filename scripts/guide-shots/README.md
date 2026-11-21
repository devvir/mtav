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

The e2e environment has everything (PHP, Node, the browsers) and a database of its own, so the
dev data stays untouched:

```sh
./mtav compose e2e up --detach --wait
./mtav compose e2e exec -T e2e sh -c 'composer install && pnpm install && pnpm run build'
./mtav compose e2e exec -T e2e php artisan migrate:fresh --seed
./mtav compose e2e exec -d -e APP_URL=http://127.0.0.1:8010 e2e php artisan serve --host=127.0.0.1 --port=8010
for l in es_UY en fr it pt_BR; do
  ./mtav compose e2e exec -T e2e php artisan tinker --execute="App\Models\User::query()->update(['locale' => '$l'])"
  ./mtav compose e2e exec -T -e LOCALE=$l -e BASE=http://127.0.0.1:8010 e2e node scripts/guide-shots/shots.mjs
done
./mtav compose e2e down
```
