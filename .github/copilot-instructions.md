* Always use script setup with typescript in vue files
* Always end lines with semi-colon in any Javascript file (including Vue)
* Always run commands on the app with the mtav command (i.e. remember dev runs on docker)
* If you need general context, refer to documentation/ai/README.md
* Never build npm, and assume I already have a dev server running
* Every file created by Copilot OR significantly modified (10%+ lines changed) MUST include a comment at the top: "Copilot - Pending review" (using appropriate syntax for the file type: // for JS/TS/Vue, # for Python/Shell, /* */ for CSS, <!-- --> for HTML/Markdown, etc.)
* Never start, stop, or manage the development server - assume it's already running and accessible
* When generating code, always follow the existing code style and conventions used in the project (see similar files for reference)
* Any user-facing text in the frontend should be in English, with a translation in lang/es_UY.json for Spanish (Uruguay) (in alphabetical order)
* The lang/en.json file should stay as '{}' (empty) to reflect that the keys are the English text already
* In the frontend, use `_('text to translate')`, with the useTranslations composable's _() function
* Any user-facing text used in the backend (error messages, logs, etc) should be in English, using coded keys, with translations in lang/<locale>/<domain>.json. For example, a validation error message should be "validation.<key>" and the translation should go in lang/es_UY/validation.json and lang/en/validation.json
* Vue core functions are auto-imported, so do not import them manually (i.e. ref, computed, defineProps, etc)
* Inertia's Link component is auto-imported as 'Link', so do not import it manually
* Typescript entity/resource definitions in types/index.d.ts are auto-imported, so do not import them manually
* NEVER convert models to their resources. This happens AUTOMATICALLY when sent to the frontend. Do not use JsonResource::make, JsonResource::collection, $model->toResource(), or ANY OTHER explicit conversion. It will be converted and you should always assume the FE receives the corresponding JsonResource representation of each model, be it the root model or its related moels.
* In Vue template blocks do NOT use `props.` to access props, just use the prop name directly
* PHP Controllers do NOT validate data directly; they always use Form Request classes for validation
* Do not add new components to componetns/ui/. Those are third-party, not mine!
* Never use fully qualified class names (FQNs) in code - always import classes and use the short class name. This applies to ALL classes including root namespace classes like Exception, Closure, Throwable, etc. Use `use Exception;` then `Exception`, not `\Exception`
* In Cypress tests, do NOT use artificial delays like cy.wait(). Pages either load all data immediately OR use deferred loading with the "animate-pulse" CSS class. Wait for .animate-pulse to disappear instead of using arbitrary delays - the class is guaranteed to only be present while deferred data is loading
* **CRITICAL: Testing with universe.sql fixture** - ALWAYS use the pre-populated test data from tests/Fixtures/universe.sql instead of creating new records with factories. The fixture is automatically loaded and provides Projects (#1-5), Families (#1-26), Admins (#1-19), Members (#100-149), etc. Only create NEW records when testing the creation itself (e.g., testing that a new user is created). Using the fixture is 20-30x faster than factories. Read universe.sql to know what data is available before writing tests.