# Form Requests

## Custom Base FormRequest

The project uses a custom `App\Http\Requests\FormRequest` base class that extends Laravel's default `Illuminate\Foundation\Http\FormRequest`.

### Purpose: IDE Intellisense Support

The custom base class exists solely to provide better IDE autocomplete and error detection. VS Code and other IDEs have issues recognizing many of Laravel's request methods and properties, highlighting them as errors even though they're well-documented Laravel functionality.

### How It Works

1. **Base FormRequest** (`app/Http/Requests/FormRequest.php`)
   - Contains docblocks with all common request methods and properties
   - Eliminates IDE warnings for standard Laravel request functionality
   - All custom form requests extend this base class

2. **Individual Form Requests** (e.g., `ProfileUpdateRequest.php`)
   - Extend the custom base `FormRequest`
   - Add docblocks for request-specific inputs
   - Enables type-safe property access like `$request->email` without IDE errors

### Example

```php
/**
 * @property string $email
 * @property string $firstname
 * @property string|null $lastname
 */
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'firstname' => ['required', 'string'],
            'lastname' => ['nullable', 'string'],
        ];
    }
}
```

Now in controllers, `$request->email` provides full autocomplete and type checking without IDE warnings.

### Benefits

- ✅ Clean IDE experience with no false error highlighting
- ✅ Full autocomplete for request properties and methods
- ✅ Type safety through docblock annotations
- ✅ No runtime behavior changes - purely for developer experience
