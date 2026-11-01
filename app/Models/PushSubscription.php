<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A browser or device where someone allowed notifications: where its push service takes
 * them (`endpoint`), and its keys
 */
class PushSubscription extends Model
{
    /** The browsers' push services: an address anywhere else would have the server calling it */
    public const SERVICES = [
        'fcm.googleapis.com',
        'android.googleapis.com',
        'updates.push.services.mozilla.com',
        'web.push.apple.com',
        '*.push.apple.com',
        '*.notify.windows.com',
    ];

    protected $fillable = ['endpoint', 'public_key', 'auth_token'];

    public static function isServiceAddress(string $endpoint): bool
    {
        return Str::is(self::SERVICES, strtolower((string) parse_url($endpoint, PHP_URL_HOST)));
    }

    /** Keys a notification can be encrypted with: a point of the P-256 curve, and a 16-byte secret */
    public static function areKeys(string $publicKey, string $authToken): bool
    {
        $decode = fn (string $key) => base64_decode(strtr($key, '-_', '+/'), true);
        $point = $decode($publicKey);

        return is_string($point) && strlen($point) === 65 && $point[0] === "\x04"
            && strlen((string) $decode($authToken)) === 16;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
