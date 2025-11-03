<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

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
 * @method Collection collect($key = null)
 * @method string|null ip()
 * @method User|null user()
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
    //
}
