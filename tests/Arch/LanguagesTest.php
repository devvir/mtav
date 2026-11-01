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

it('shows in every guide only pictures it has', function () {
    foreach (glob(resource_path('views/guides/*/guide.*.html')) as $guide) {
        preg_match_all('/<img src="([^"]+)"/', file_get_contents($guide), $found);
        foreach ($found[1] as $src) {
            expect(public_path(ltrim($src, '/')))->toBeFile(basename(dirname($guide)) . '/' . basename($guide));
        }
    }
});

/** What vendor views say through __(): their words live with ours, nothing here names them */
const VENDOR_WORDS = [
    'Whoops!',
    'Regards,',
    'All rights reserved.',
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:",
];

/** The app's own code, comments and tests apart */
function appCode(): string
{
    $files = collect(File::allFiles(resource_path('js')))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/tests/'));

    return preg_replace(['~/\*.*?\*/~s', '~^\s*//.*$~m'], '', $files->map->getContents()->implode("\n"));
}

/** The phrases written out in _('…'), _n('…', '…') and _nodes('…') */
function phrasesInCode(): array
{
    $text = '([\'"`])((?:\\\\.|(?!\1).)*)\1';
    preg_match_all('/(?<![\w$.])_(?:n|nodes)?\(\s*' . $text . '(?:\s*,\s*' . str_replace('\1', '\3', $text) . ')?/s', appCode(), $found);

    return array_values(array_unique(array_map('stripslashes', array_filter([...$found[2], ...$found[4]], 'strlen'))));
}

it('has in every language every phrase the app says', function () {
    $phrases = phrasesInCode();
    expect(count($phrases))->toBeGreaterThan(500);

    foreach (array_diff(array_keys(config('app.locales')), ['en']) as $locale) {
        $words = json_decode(file_get_contents(lang_path("{$locale}.json")), true);

        expect(array_values(array_filter($phrases, fn ($phrase) => ! isset($words[$phrase]))))->toBe([], $locale);
    }
});

it('keeps in its languages no phrase nothing says', function () {
    $code = stripslashes(appCode());
    foreach (['app', 'config', 'routes', 'resources/views'] as $folder) {
        $code .= collect(File::allFiles(base_path($folder)))->map->getContents()->implode("\n");
    }

    foreach (array_diff(array_keys(config('app.locales')), ['en']) as $locale) {
        $words = json_decode(file_get_contents(lang_path("{$locale}.json")), true);
        $unused = array_filter(array_keys($words), fn ($word) => ! in_array($word, VENDOR_WORDS, true) && ! str_contains($code, $word));

        expect(array_values($unused))->toBe([], $locale);
    }
});
