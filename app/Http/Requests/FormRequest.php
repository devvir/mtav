<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Services\Form\Lib\Spec;

/**
 * Base FormRequest with proper IDE support.
 *
 * @method array only(array|mixed $keys)
 * @method array except(array|mixed $keys)
 * @method array validated($key = null, $default = null)
 * @method array all($keys = null)
 * @method mixed input($key = null, $default = null)
 * @method bool has($key)
 * @method bool hasAny(array|string $keys)
 * @method bool filled($key)
 * @method bool isNotFilled($key)
 * @method bool missing($key)
 * @method string|null string($key, $default = null)
 * @method int|null integer($key, $default = null)
 * @method float|null float($key, $default = null)
 * @method bool|null boolean($key, $default = null)
 * @method array|null date($key, $format = null, $tz = null)
 * @method UploadedFile|null file($key)
 * @method bool hasFile($key)
 * @method mixed route($param = null, $default = null)
 * @method Session session($param = null, $default = null)
 * @method Collection collect($key = null)
 * @method string|null ip()
 * @method User|null user($guard = null)
 * @method string|null userAgent()
 * @method string method()
 * @method string path()
 * @method string url()
 * @method string fullUrl()
 * @method bool isMethod($method)
 * @method bool ajax()
 * @method bool wantsJson()
 * @method bool expectsJson()
 * @method $this merge(array $input)
 *
 * @mixin \Illuminate\Http\Request
 */
abstract class FormRequest extends BaseFormRequest
{
    /**
     * Provide custom, human-friendly attribute names for validation messages.
     *
     * @return array<string,string>
     */
    public function attributes(): array
    {
        foreach (array_keys($this->all()) as $field) {
            $attributes[$field] = $this->labelFor($field);
        }

        return $attributes ?? [];
    }

    /**
     * Return the translated, human-friendly label for a given field name.
     */
    protected function labelFor(string $field): string
    {
        // Handle dotted/array keys (e.g. "items.*.user_id") by using the
        // last segment as the base field name for label inference.
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $field = end($parts) ?: $field;
        }

        // Strip any trailing asterisks or numeric indices if present.
        $field = preg_replace('/\[.*\]$/', '', $field);
        $field = preg_replace('/\*$/', '', $field);

        return __(Spec::labelFromField($field));
    }
}
