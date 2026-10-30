<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * What superadmins set for the whole platform, by name (each with its default here)
 */
class PlatformSetting extends Model
{
    use RecordsActivity;

    /** The least a lottery run takes, as people see it, in seconds (the solving is quicker) */
    public const LOTTERY_SECONDS = 'lottery_seconds';

    public const DEFAULTS = [
        self::LOTTERY_SECONDS => 20,
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];

    public static function value(string $key): mixed
    {
        return static::find($key)?->value ?? self::DEFAULTS[$key] ?? null;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
