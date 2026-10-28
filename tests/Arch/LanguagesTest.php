<?php

/**
 * Every language people may pick (config app.locales) is whole: its words for the app
 * (lang/<locale>.json), for the server (lang/<locale>/*.php, the same keys as English, each
 * with the same :blanks) and its guides.
 */


function langKeys(array $lines, string $prefix = ''): array
{
    $keys = [];
    foreach ($lines as $key => $value) {
        is_array($value)
            ? $keys += langKeys($value, "{$prefix}{$key}.")
            : $keys["{$prefix}{$key}"] = $value;
    }

    return $keys;
}

function blanksOf(string $text): array
{
    preg_match_all('/:[a-z_]+/', $text, $found);
    sort($found[0]);

    return $found[0];
}

it('offers only languages it has the words for', function () {
    foreach (array_keys(config('app.locales')) as $locale) {
        if ($locale !== 'en') {
            expect(lang_path("{$locale}.json"))->toBeFile();
        }
        foreach (['FAQ.admin.json', 'FAQ.member.json', 'guide.admin.html', 'guide.member.html', 'guide.superadmin.html'] as $guide) {
            expect(resource_path("views/guides/{$locale}/{$guide}"))->toBeFile();
        }
    }
});

it('says on the server everything English says, in every language', function () {
    foreach (array_diff(array_keys(config('app.locales')), ['en']) as $locale) {
        foreach (glob(lang_path('en/*.php')) as $file) {
            $name = basename($file);
            $english = langKeys(require $file);
            $translated = langKeys(require lang_path("{$locale}/{$name}"));

            expect(array_keys($translated))->toEqual(array_keys($english), "{$locale}/{$name}");
            foreach ($english as $key => $text) {
                expect(blanksOf($translated[$key]))->toEqual(blanksOf($text), "{$locale}/{$name}: {$key}");
            }
        }
    }
});
