## CHANGELOG

## Unreleased

### Breaking Changes: Variadic Arguments ([PR #89](https://github.com/pieceofcake2/cakephp/pull/89))

- **`func_get_args()` replaced with variadic parameters**: The methods that took a variable number of arguments declare them as `...$args`, so that PHP and static analysis see them: `Shell::dispatchShell()`, `AuthComponent::allow()` / `deny()`, `SecurityComponent::require*()`, `Controller::setAction()` / `validate()` / `validateErrors()`, `ContainableBehavior::contain()`, `DboSource::query()`, `Model::isUnique()` / `query()`, `CakeRequest::data()` / `param()` / `input()` / `allowMethod()` / `onlyAllow()`, `Router::parseExtensions()`, `CakeTestCase::loadFixtures()`, `Hash::merge()`, `Sanitize::stripTags()`, `Set::merge()`, `HtmlHelper::css()` / `useTag()`. The calls do not change. A class that overrides one of these methods with the former signature fails with a fatal error: see UPGRADE

### Enhancements: Plugins in the CakePHP 5 Layout ([PR #88](https://github.com/pieceofcake2/cakephp/pull/88))

- **Plugin directories**: Plugins may use the directories of the CakePHP 5 layout, as the application already does: `src/` for the namespaced classes (`src/Controller/`, `src/Model/`, ..., loaded by Composer), `templates/` for the views, `resources/locales/` for the translations, `vendor/` for the vendor files and `config/` for `bootstrap.php`, `routes.php`, configuration files and schemas. `App::path()` and `I18n` search them, when they exist, before the directories of the CakePHP 2 layout (`Controller/`, `View/`, `Locale/`, `Vendor/`), which are still searched. `App::uses()` and `App::load()` keep loading global classes from the CakePHP 2 directories
- **CakePlugin::configPath()**: A new method returns the `config/` directory of a plugin, or `Config/` when the plugin has no `config/`; `CakePlugin::bootstrap()`, `CakePlugin::routes()`, `PhpReader`, `IniReader` and `CakeSchema` use it

### Enhancements: Templates in the CakePHP 5 Style ([PR #94](https://github.com/pieceofcake2/cakephp/pull/94))

- **`.php` templates**: In `templates/` directories (of the application, of plugins, of themes), templates, layouts and elements may use the `.php` extension of CakePHP 5. In each directory a `.php` template comes before the `.ctp` template of the same name, and the order of the directories is unchanged, so themes and plugins still override the templates of the application. The `View/` directories of the CakePHP 2 layout, which also hold the view and helper classes, are not searched for `.php` templates
- **CakePHP 5 directory names**: In `templates/` directories, `layout/`, `element/`, `email/` and `Error/` are searched before `Layouts/`, `Elements/`, `Emails/` and `Errors/`. Email templates may be in `templates/email/html/` and `templates/email/text/`, and their layouts in `templates/layout/email/html/` and `templates/layout/email/text/`

### Enhancements: Classes in a Namespace (`App::className()`, `App.namespace`) ([PR #91](https://github.com/pieceofcake2/cakephp/pull/91))

- **App::className()**: A new method, backported from CakePHP 3 and later, returns the class name for a name used in the configuration, or `null`. A name with a backslash is a fully qualified class name; the suffix is added only when the name does not end with it, so `GreetingComponent::class` and `'Acme\Controller\Component\Greeting'` both return `Acme\Controller\Component\GreetingComponent`. Other names are looked up in the namespace of the plugin or of `App.namespace` (`<Namespace>\<Type>\<Name><Suffix>`), then as the global class declared with `App::uses()`, as before
- **Fully qualified class names**: Cache engines, email transports, `AuthComponent` `authenticate`/`authorize` objects, password hashers, view classes (`Controller::$viewClass`, `CakeEmail::viewRender()`, `RequestHandlerComponent::viewClassMap()`), components, helpers, behaviors, shell tasks, shell helpers and the `PaginatorHelper` `ajax` helper use `App::className()`. They accept `Foo::class`, where the suffix was added a second time before (`MemoryEngine::class` looked for `MemoryEngineEngine`)
- **App.namespace**: When `Configure::write('App.namespace', 'App')` is set, controllers (`Dispatcher`, `ControllerTestCase::generate()`), shells (`ShellDispatcher`), models (`ClassRegistry::init()`, `$uses`, `CakeTestCase::getMockForModel()`) and the classes above are also found by their short name in that namespace, e.g. `App\Controller\PostsController` for `/posts`, before the global classes. A controller in the namespace merges `$components` and `$helpers` with the `AppController` of the namespace
- **The other class settings**: Log engines, the exception renderer (`Exception.renderer`, also in `IntegrationTestTrait`), datasources, session handlers, route classes (`'routeClass'`), dispatcher filters, `Acl.classname`, the `engine` of `TextHelper`, `NumberHelper` and `TimeHelper`, the readers of `HtmlHelper::loadConfig()`, the localized classes of `Validation` (`NlValidation`), the response class and authentication methods of `HttpSocket`, test reporters, the models of fixture imports, `CakeSchema` and the `ExtractTask`, and the shells of `CommandTask` also use `App::className()`, as on the `feature/phpstan-level5` branch. Their global names and their fully qualified names work as before, and they are also found in `App.namespace` and in the namespace of a plugin
- **Plugin AppController and AppModel**: A plugin controller merges the vars of the `AppController` of the namespace of its plugin (`MyPlugin\Controller\MyPluginAppController`), `ClassRegistry::init()` uses the `AppModel` of that namespace for a plugin model without a class, and a model in `App.namespace` merges `$actsAs` and `$findMethods` with `App\Model\AppModel`
- **Names without the namespace**: `Controller::$name`, `Model::$name` and `Shell::$name` are derived from the class name without its namespace, e.g. `Posts` for `App\Controller\PostsController`
- **Namespaced fixtures**: Fixtures are also loaded from `Cake\Test\Fixture` (`core.*`), `<App.namespace>\Test\Fixture` (`app.*`) and `<Plugin>\Test\Fixture` (`plugin.*`), before the global classes; the name and the table come from the class name without the namespace
- **App::import() and App::load()**: `App::import()` also finds namespaced classes, `App::load()` includes each file once, and an application with `AppController`, `AppModel`, `AppHelper` or `AppShell` only in `App.namespace` gets the global name for it, so that core classes such as `CakeErrorController` that extend it work
- **AppController in any namespace**: A controller merges the vars of a parent class named `AppController` in any namespace, also without `App.namespace`
- **ClassRegistry::init(Foo::class)**: A fully qualified class name without an alias gets the short class name as its alias, e.g. `Post` for `ClassRegistry::init(Post::class)`, as a short name does; before, the alias contained the backslashes
- **ClassRegistry keys**: `ClassRegistry::getObject()`, `isKeySet()` and `removeObject()` also find an object stored with the short name of its class by the fully qualified class name, e.g. `ClassRegistry::getObject(TranslateBehavior::class)` or `ClassRegistry::getObject(Post::class)` for the model with the alias `Post`, and otherwise the only object of exactly that class, e.g. a `Post` with the alias `Article`; with several objects of the class nothing is found
- **Class names as aliases**: Fully qualified class names as keys of `$components`, `$helpers`, `$actsAs`, Shell `$tasks`, `$uses` (Controller and Shell) and the model associations, or given to `load()` of the collections and `loadModel()`, get the short class name without the namespace and the suffix as alias and property name, e.g. `public $components = [GreetingComponent::class]` gives `$this->Greeting`, `$uses = [Post::class]` gives `$this->Post` and `$modelClass` `Post`, and `$belongsTo = [Author::class]` gives the association `Author` with `className` `Author::class`, the same as the short name with `className`. A class name key with its own `className` also gets the short alias and keeps that `className`; before, the alias contained the backslashes. `enable()`, `disable()`, `unload()`, `loaded()`, `enabled()` and `set()` of the collections and `bindModel()`/`unbindModel()` also take the class name
- **Merged class names**: When the vars of a parent class are merged (`$components`, `$helpers`, `$actsAs`, Shell `$tasks`, also from a plugin AppController and the AppModel), a class name key is replaced by its alias with the class name in `'className'`, so `'Greeting' => [...]` in the AppController and `GreetingComponent::class => [...]` in a controller give one entry `Greeting` with the merged settings, as two short names do. The new `ObjectCollection::normalizeClassNames()` does this
- **Join models by class name**: `'with' => PostsTag::class` of a hasAndBelongsToMany association works like `'with' => 'PostsTag'` when reading, saving, validating and deleting (`joinModel()`, `__isset()`, `_saveMulti()`, `_deleteLinks()`), and in `CakeSchema::read()`; the scaffold names the controller of an association with a class name as `className` by the short name
- **Missing classes**: The errors for a missing component, helper, behavior, task or controller (`ControllerTestCase::generate()`) add the suffix to a fully qualified class name only when it does not end with it, e.g. `Acme\Controller\Component\GreetingComponent` instead of `...GreetingComponentComponent`
- **Cached views**: `Controller::$controllerClass` and `View::$controllerClass` hold the class name of the controller, and the views cached by `CacheHelper` create a namespaced controller by that name
- **Fixes for namespaced classes**: Dispatcher filters with settings, `CakeSchema::read()` for plugin models, `ClassRegistry::init()` for a stored alias, the plugin and name of namespaced models in the rendered view, and the plugin AppController of a request whose plugin is not loaded (no `MissingPluginException`)

### Enhancements: The Framework in the `Cake` Namespace ([PR #92](https://github.com/pieceofcake2/cakephp/pull/92))

- **Namespaced classes**: The classes of the framework are in the `Cake` namespace, following their directories: `Cake\Controller\Controller`, `Cake\Network\CakeRequest`, `Cake\Utility\Hash`, ... Applications and plugins can `use` them, e.g. `use Cake\Controller\Component;`
- **`src/` and PSR-4**: As on the `feature/phpstan-level5` branch, the classes are in `src/` (`src/Controller/Controller.php`) instead of `src/Cake/`, and Composer loads them with `"psr-4": {"Cake\\": "src/"}` instead of a classmap. The files that declared several classes are split into one file per class (`src/Error/NotFoundException.php`, `src/Controller/Component/Acl/PhpAco.php`, `src/TestSuite/ControllerTestDispatcher.php`, ...). `CAKE` is `src/`. `src/Cake/bootstrap.php` and `src/Cake/TestSuite/Fixture/CakeFixtureInjector.php` stay at their former paths for the applications and plugins that name them
- **Global names**: The global names of CakePHP 2 keep working as aliases of the same classes (`class_alias()`). Each class file declares its alias when the class is loaded, and `src/aliases.php`, loaded by Composer, loads the namespaced class when an application uses the global name first. `Controller` and `Cake\Controller\Controller` are the same class: `extends`, `instanceof`, type declarations, `catch` blocks and `App::uses()` work with either name
- **Class names in output**: `get_class()`, `::class` and the messages, logs, debug output and error pages that show them use the namespaced names, e.g. `[Cake\Error\NotFoundException]` in the error log
- **Overriding core classes**: A global class registered with `App::uses()` no longer replaces a core class, as the core uses the namespaced names. Override a core class with a copy in `src/Cake/` (e.g. `src/Cake/Routing/Dispatcher.php`) loaded by the classmap of the application, and exclude the core file with `exclude-from-classmap`
- **phpcs**: `SlevomatCodingStandard.Namespaces.UnusedUses` counts the class names in annotations, as the core imports the classes that its docblocks name

### Enhancements: Fixture Class Names ([PR #108](https://github.com/pieceofcake2/cakephp/pull/108))

- **Fixtures by class name**: `$fixtures`, `loadFixtures()` and `CakeFixtureManager::loadSingle()` accept the fully qualified class name of a fixture, e.g. `public $fixtures = [ArticleFixture::class]` and `$this->loadFixtures(ArticleFixture::class)`, as the other classes in the configuration do. The `Fixture` suffix is added only when the name does not end with it. The names `core.article`, `app.article` and `Article` work as before

### Deprecations: `App::uses()` ([PR #98](https://github.com/pieceofcake2/cakephp/pull/98))

- **App::uses()**: Deprecated. Composer loads the classes of the core, of `pieceofcake2/app` and of the plugins installed with it, and the core finds the classes named in the configuration without it. The first call in a process logs a warning with the file and line of the call, and triggers `E_USER_DEPRECATED` when `debug` is above 0. The call still works
- **Core**: The core no longer calls `App::uses()`. `AppController`, `AppModel`, `AppHelper` and `AppShell` of the application are found in their packages without it, and the classes that the core loads from an application or a plugin are found with `App::className()`. The code that `CacheHelper` writes into cached views and the code that the error pages suggest no longer contain it

### Bug Fixes: PHP Deprecations ([PR #96](https://github.com/pieceofcake2/cakephp/pull/96))

- **Null passed to built-in functions**: Many methods passed a value that can be `null` (e.g. `env()` of a missing variable, an unset option, the plugin of `pluginSplit()`) to a string function of PHP, which is deprecated since PHP 8.1. They cast it to a string (or an int for the decimals of `CakeNumber`), so the result is the same as before. This covers among others `CakeSession`, `CakeRequest`, `CakeResponse`, `CakeEmail`, `HttpSocket`, `Security`, `Configure::consume()`, `Xml::build()`, `FormHelper` date and radio inputs, `CakeText::cleanInsert()`, `Hash` / `Set` matching, `File`, `pluginSplit()`, `env()`, `clearCache()` and the missing class exceptions of the object collections
- **False converted to an array**: `I18n` (on a cache miss of a domain) and `CakeRoute` (with named parameters before `Router::connectNamed()`) no longer add keys to `false`, deprecated since PHP 8.1
- **Xml**: `Xml::build()` no longer calls `libxml_disable_entity_loader()`, deprecated since PHP 8.0. External entities stay disabled: libxml 2.9 and later, which PHP 8 requires, does not load them without `LIBXML_NOENT`, which `Xml` does not pass
- **Xml `loadEntities` option removed**: The option of `Xml::build()` only decided whether to call `libxml_disable_entity_loader()`, so it no longer has any effect and is removed. Passing it is ignored
- **Security**: `Security::hash()` with `sha256` always uses `hash()` instead of `mhash()`, deprecated since PHP 8.1. The digest is the same
- **SessionHandlerAdapter**: The methods of `SessionHandlerInterface` have `#[\ReturnTypeWillChange]`, so PHP 8.1 and later no longer report their missing return types
- **CakeTestFixture**: `CakeTestFixture` declares `$import`, so setting it on a fixture that does not declare it no longer creates a dynamic property, deprecated since PHP 8.2
- **Null as an array key**: `I18n`, `Helper::setEntity()`, `FormHelper`, `RequestHandlerComponent::respondAs()`, `CakeResponse::getMimeType()`, `CakeSession::check()`, `Configure::consume()`, `ClassRegistry::getObject()`, `ConsoleOptionParser::parse()` / `help()` and `Set::extract()` check that a name or key is given before they use it as an array key, instead of using `null`, deprecated since PHP 8.5. A missing name finds nothing, as before. `I18n` looks up the translations without a context under the empty context, where they are stored
- **Reflection**: `Debugger::export()` calls `ReflectionProperty::setAccessible()` only before PHP 8.1, where it has an effect. It is deprecated since PHP 8.5
- **No request input or URL**: `CakeRequest::input()` with a decoding callback returns `null` without calling it when the request has no body, or when `_readInput()` gives `null`, e.g. in a subclass or a mock, instead of passing the empty input on. `RequestHandlerComponent` sets the same empty data as before. A callback of your own that handled an empty body now gets no call. `CacheHelper` writes no cache file when `here()` gives no URL, instead of passing `null` to the string functions of PHP
- **Mysql**: The `Mysql` datasource uses the `Pdo\Mysql::ATTR_*` constants instead of `PDO::MYSQL_ATTR_*`, deprecated since PHP 8.5. The core requires `symfony/polyfill-php84`, which defines `Pdo\Mysql` with the same values before PHP 8.4
- **RedisEngine**: `RedisEngine::delete()` deletes the key with `Redis::del()` instead of `Redis::delete()`, which phpredis deprecates
- **Security**: `Security::encrypt()` encrypts `null` as the empty string, instead of passing it to `strlen()` (deprecated since PHP 8.1)
- **Sqlserver**: `Sqlserver::limit()` checks that the offset is a string before `ctype_digit()`, `renderStatement()` checks that a limit is given before it looks for `TOP` or `FETCH`, and `create()` checks that the table has an identity column before it looks for its value, instead of passing `null` or other types to them (deprecated since PHP 8.1)

### Bug Fixes: Null Direction in `DboSource::order()` ([PR #101](https://github.com/pieceofcake2/cakephp/pull/101))

- **DboSource::order()**: A field without a direction (e.g. `order('Post.title', null)`) no longer passes `null` to `trim()`, deprecated since PHP 8.1. The SQL is unchanged
- **Tests**: The test of the directions passes the errors other than `E_USER_DEPRECATED` to PHPUnit, so it reports deprecations again

### Bug Fixes: Console ([PR #104](https://github.com/pieceofcake2/cakephp/pull/104))

- **ShellDispatcher**: An unknown shell gives `MissingShellException`, not `MissingPluginException`
- **AclShell**: The parser and help no longer set a dynamic property (the unsupported `default` of the action arguments is removed)
- **AclShell**: `nodeExists aco <number>` uses `foreign_key`, not the missing `object_id` column
- **AclShell**: A `Model.foreign_key` reference that is not found is reported with "Could not find node using reference" instead of an uncaught exception
- **ProgressShellHelper**: `draw()` passes an integer to `str_repeat()`, as a float is deprecated since PHP 8.1
- **TestShell**: A file in `src/` of a plugin maps to its test case in `tests/TestCase/`
- **TestShell**: The "cannot be run via this shell" message names the given file
- **ApiShell**: The input `l` and other text that is not a number no longer fail on PHP 8.3 and later
- **ApiShell**: Each lookup reads the classes of its own path, not the cached list of the first one
- **ApiShell**: A class file that cannot be included is reported without a `ReflectionException`
- **ApiShell**: `api help className` and `api help type` show their help
- **ApiShell**: `api helper Html` finds `HtmlHelper`; the type suffix is added before the lookup
- **ConsoleShell**: `unbind` with an unknown model prints the "Please verify" message
- **ConsoleShell**: `bind` and `unbind` use the stripped names
- **File**: Creating a file whose directory cannot be created no longer passes `null` to `pathinfo()` and `is_dir()`

### Bug Fixes: Found by the Tests ([PR #107](https://github.com/pieceofcake2/cakephp/pull/107))

- **Test runner page**: The HTML reporter of `test.php` escapes the request values that it writes into the page (case, plugin, filter and the links), which could run a script in the page (XSS). The page header has its doctype, `<html>`, `<head>`, charset and title again
- **Test reporters**: Skipped and incomplete tests are no longer painted as passes. A failure shows the exception that the test threw instead of the wrapper of PHPUnit, with its class, file, line and trace, and the text reporter shows the line of the test that failed. The case list of the text reporter links plugin and core cases with `&plugin=` and `&core=true` and encoded names, and says so when there are no cases. The summaries count only the finished tests as complete and show the skipped and incomplete tests: see UPGRADE
- **Test runner**: A `core.*` fixture is looked up only in the fixture path of the core. A test case of a plugin that is not loaded raises `MissingPluginException` instead of looking for the file under `/`
- **JsHelper engines**: `escape()` writes a character outside the Basic Multilingual Plane as a UTF-16 surrogate pair, other C0 control characters as `\u00XX` instead of dropping them, and a question mark for bytes that are not valid UTF-8 (a stray continuation byte, a cut-off or overlong sequence, the lead bytes `0xF8` to `0xFF`). `value()` writes a float with a dot whatever the locale. `object()` replaces invalid UTF-8 with U+FFFD instead of returning only the prefix and postfix. `object()` and `value()` throw `CakeException` for `INF`, `NAN` and other data that `json_encode()` cannot encode. `redirect()` escapes the URL in its JavaScript string. The query string of the `data` of `request()` writes nested arrays as `key[sub]=value` instead of `Array`, leaves a null value out and writes `false` as `0`, as `Router` does; the values are still not URL-encoded. Empty callbacks are left out instead of giving `error:`
- **Scaffold**: The `list` action is removed. It was the former name of `index`, kept as an alias when the views were renamed in CakePHP 1.2, but there was no `list` view, so it failed with `MissingViewException`. A request to `list` now gives `MissingActionException`, as other actions that are not scaffolded do. `create` and `update` stay
- **CakeSocket**: A fractional `timeout` is passed to `stream_set_timeout()` as seconds and microseconds. Through a proxy, the `CONNECT` request sends the target as `Host`, and a status other than 2xx throws `SocketException`. `reset()` closes the connection when it resets the connection state. The connection warnings are cleared at each `connect()`
- **CakeSession**: `clear()` empties the session data before it renews the session, so that the data is not carried over to the new id. In debug mode, the save path of `Session.ini` `session.save_path` is created
- **DataSource**: `insertQueryData()` accepts an integer `0` as the id, and throws `NotImplementedException` when the datasource has no `value()` method
- **Model**: `TreeBehavior::reorder()` stops on a tree that is not valid. `DboSource::create()` and `Sqlserver::create()` without fields insert the data of the model. `getColumnTypes()` returns `[]` for a model without a schema. `Sqlserver::lastAffected()` returns the rows changed by an `UPDATE` or a `DELETE` instead of 0
- **CakeSchema**: `write()` without tables writes the tables that `read()` finds
- **Folder**: `realpath()` resolves a relative path with `..` from the current directory, so `cd('../sibling')` works
- **Debugger**: `excerpt()` highlights the given line in the middle of the lines it returns: see UPGRADE
- **Translations of "0"**: `__()` and the other translation functions translate the message `'0'` instead of returning `null`, and a translation whose `msgstr` is `"0"` is used

### Tests: Coverage ([PR #106](https://github.com/pieceofcake2/cakephp/pull/106))

- **More tests**: Tests for the test suite (reporters, runner, fixture manager), the Js helpers, the scaffold, the network and session classes, the datasources, the models and behaviors, `CakeSchema` and the utilities. The bugs that they found are fixed in PR #107

### Tests ([PR #96](https://github.com/pieceofcake2/cakephp/pull/96))

- **Deprecations reported**: The tests report deprecations and fail on them. `ConfigureTest::testBootstrap()` restores the error level after `Configure::bootstrap()`, which had hidden deprecations from the following tests, and the tests no longer create dynamic properties, or use `utf8_decode()`, `new DateTime(null)`, `str_getcsv()` without the escape character or `session.referer_check`. The `DATABASE_CONFIG` of the tests allows dynamic properties, which `ConnectionManager::create()` adds. The test helpers call `setAccessible()` of reflection only before PHP 8.1. `ApplicationControllerTest` tests the redirect without cookies on every version, and the session ID in the URL, which needs `session.use_trans_sid` deprecated since PHP 8.4, only before PHP 8.4. The tests that use mcrypt check that it reports the deprecation of its functions, and `MemcacheEngineTest` replaces `Memcache::set()` with a small subclass, as a mock would copy the implicitly nullable parameters of the extension (deprecated since PHP 8.4)

### Tests: Error Handler of the Test Bootstrap ([PR #103](https://github.com/pieceofcake2/cakephp/pull/103))

- **Application tests follow their phpunit.xml**: The test bootstrap no longer turns every deprecation into an exception in the tests of applications

### Tests: Namespaced Tests and `AllLegacyTests` ([PR #95](https://github.com/pieceofcake2/cakephp/pull/95))

- **Namespaced tests**: `tests/` holds the namespaced tests of `feature/phpstan-level5` (`Cake\Test\TestCase\...`), with a test application in the CakePHP 5 layout (`tests/test_app/`, `TestApp\`). The classes of the application are real classes of the test application, not aliases, and the tests that need it set `App.namespace` in `setUp()`
- **`AllLegacyTests`**: A second test suite, run with `vendor/bin/phpunit --testsuite AllLegacyTests`, checks that an application in the CakePHP 2 layout (`tests/test_legacy_app/`) and plugins in the legacy layout keep working with the 2.x notation and without `App.namespace`, and that a namespaced plugin works in it and a legacy plugin in a namespaced application
- **Test plugins**: The test plugins are named `Test<Name>Plugin`, and `Test<Name>LegacyPlugin` in the legacy layout

## v2.12.11 (2026-10-19)

### Security Fixes

- **CakeRequest**: The request URL loses all its leading slashes and backslashes, not only the first slash, so `CakeRequest::$url` never starts with `/` or `\` and `CakeRequest::$here` / `here()` never start with `//` or `/\`. A request to `//example.com/posts` was routed as `/example.com/posts`, but `here` was the protocol-relative URL `//example.com/posts`: `FormHelper::create()` without a `url` printed `<form action="//example.com/posts">`, so the form was submitted to another host, and `$this->redirect($this->request->here(false))` redirected there. With a base path, `here(false)` of `/base//example.com/posts` had the same problem. `here()` also collapses the leading slashes of a `$here` changed by the application. Routing is unchanged ([GHSA-wh43-gc93-wh4c](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-wh43-gc93-wh4c), [PR #90](https://github.com/pieceofcake2/cakephp/pull/90))

### Enhancements: HTML5 Script, Link and Style Tags ([PR #86](https://github.com/pieceofcake2/cakephp/pull/86))

- **HtmlHelper `html5` setting**: `HtmlHelper` outputs the tags of HTML5 by default: `script()`, `css()` and the `<style>` of `css()` with `rel` `import` have no `type` attribute, and `scriptBlock()` and `scriptStart()` neither add `type="text/javascript"` nor wrap the script in CDATA. Set `'html5' => false` in the settings of the helper for the former output: `public $helpers = ['Html' => ['html5' => false]];`. The `safe` option of `scriptBlock()`, `scriptStart()` and the methods of `JsHelper` still adds the CDATA when it is `true`; its default follows the setting
- **Test pages**: The test runner and the code coverage report of the web test runner use the tags of HTML5

### Tests ([PR #87](https://github.com/pieceofcake2/cakephp/pull/87))

- **Independent of the skeleton defaults**: `ApplicationControllerTest::testRedirect()` clears `Session.cacheLimiter`, so the `'nocache'` default of pieceofcake2/app v2.5.3 no longer makes it fail

## v2.12.10 (2026-10-19)

### Enhancements: `raw()` ([PR #84](https://github.com/pieceofcake2/cakephp/pull/84))

- **raw()**: A new global function, the opposite of `h()`: it returns a value as it is, as a string, to print HTML without escaping on purpose, e.g. `<?= raw($bodyHtml) ?>`. It marks the places that print unescaped HTML, so that they can be found and reviewed; pieceofcake2/security-sast lists them. It takes a string, a number, a boolean, `null` (an empty string) or an object with `__toString()`, and throws `InvalidArgumentException` for an array. As the other global functions, it is defined only when no function of that name exists

## v2.12.9 (2026-10-17)

No changes to the framework code: this release only updates the tests.

### Tests ([PR #80](https://github.com/pieceofcake2/cakephp/pull/80))

- **Debug level 2**: `phpunit.xml.dist` sets `APP_DEBUG=2`. The core tests run with pieceofcake2/app as `ROOT` and expect debug level 2, the default of the original CakePHP 2 `core.php`; pieceofcake2/app reads the level from `APP_DEBUG` and uses 0 when it is not set

## v2.12.8 (2026-10-16)

No changes to the framework code: this release only updates the tests, the development dependencies and the documentation.

### Tests ([PR #78](https://github.com/pieceofcake2/cakephp/pull/78))

- **Independent of the skeleton defaults**: The core tests run with pieceofcake2/app as `ROOT`. `CookieComponentTest` and `IntegrationTestTraitTest` now clear `Cookie.type` and `Cookie.key`, and `ControllerTest::testFlash()` renders the core flash layout, so the `'aes'` cookie default and the flash layout of pieceofcake2/app v2.5.0 no longer make them fail. A failing `CookieComponentTest` had also left `debug` at `0` for the following tests

### Development Dependencies

- `pieceofcake2/phpstan-cakephp2` `^1.0` ([PR #76](https://github.com/pieceofcake2/cakephp/pull/76)) and `pieceofcake2/app` `^2.5` ([PR #78](https://github.com/pieceofcake2/cakephp/pull/78))

### Documentation

- **UPGRADE.md**: The v2.12.7 steps keep `COOKIE_KEY` in `config/.env.local` ([PR #77](https://github.com/pieceofcake2/cakephp/pull/77))
- **AGENTS.md**: Rules for AI agents that work on this repository ([PR #75](https://github.com/pieceofcake2/cakephp/pull/75))

## v2.12.7 (2026-10-16)

### Enhancements: DSN Configuration (`DATABASE_URL`, `MAILER_DSN`) ([PR #69](https://github.com/pieceofcake2/cakephp/pull/69))

- **ConnectionManager**: A datasource configuration in `DATABASE_CONFIG` (or passed to `ConnectionManager::create()`) may set its connection with a DSN in the `'dsn'` key, e.g. `'dsn' => env('DATABASE_URL')` with `mysql://user:secret@localhost:3306/app?encoding=utf8mb4` (the CakePHP 3.x and later format). The schemes `mysql`, `postgres` / `postgresql` / `pgsql`, `sqlite` and `sqlserver` / `mssql` / `sqlsrv` select the datasource; user, password, host, port and path become `login`, `password`, `host`, `port` and `database`; query parameters become other keys (`encoding`, `schema`, `prefix`, `persistent`, `flags[...]`, ...)
- **CakeEmail**: An `EmailConfig` profile (or an array passed to `config()`) may set its transport with a DSN in the Symfony Mailer `MAILER_DSN` format in the `'dsn'` key, e.g. `'dsn' => env('MAILER_DSN')`. `smtp://user:pass@host:587` uses the `Smtp` transport (with `tls` on port 587 or `?tls=1`), `smtps://user:pass@host` connects to `ssl://host` on port 465, `sendmail://default` and `native://default` use the `Mail` transport and `null://null` uses the `Debug` transport
- **Precedence**: For both, the keys parsed from the DSN replace the same keys of the array and the other keys are kept, as in CakePHP 3.x and later. An empty `'dsn'` (`null`, `''` or `false`) is ignored, so the array keys apply when the environment variable is not set. The `'dsn'` key is removed from the resulting configuration. Errors name the configuration and never contain the user name or password
- **DSN class maps**: `ConnectionManager` and `CakeEmail` have `setDsnClassMap()` and `getDsnClassMap()`, as in CakePHP 3.x and later, so plugins can add schemes or replace built-in ones (e.g. in their `Config/bootstrap.php`). A value is a class name in plugin dot syntax (`'mydb' => 'MyPlugin.Database/MyDb'`), whose DSN parts become the usual keys of a datasource or transport, or a callable `function (array $parts, array $config): array` that returns the configuration keys, so a plugin can map Symfony style DSNs such as `ses+api://KEY:SECRET@default?region=eu-west-1` to its own options. `null` removes a scheme
- **parseDsn()**: New public static methods `ConnectionManager::parseDsn()` and `CakeEmail::parseDsn()` that turn a DSN into configuration keys
- **CakeDsn**: New utility class with the DSN parser shared by both. User info, host and path are URL-decoded

## v2.12.6 (2026-10-15)

> [!NOTE]
> Code that triggered an `E_USER_ERROR` (deprecated as of PHP 8.4) now throws an exception with the same message, and `ConsoleLog` decides colored output by its own stream. See [UPGRADE.md](UPGRADE.md#v2126).

> [!IMPORTANT]
> The `'cipher'` encryption (`Security::cipher()`, the default `CookieComponent` type) is deprecated and now logs a warning once per process. New Configure values `Cookie.type` and `Cookie.key` set the default `CookieComponent` type and key; set them to `'aes'` and a random key of at least 32 bytes. See [UPGRADE.md](UPGRADE.md#cipher-encryption-is-deprecated-pr-62) and the security advisory [GHSA-65v5-h65v-8pgg](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-65v5-h65v-8pgg).

### PHPStan Level 5 (docblocks only) ([PR #56](https://github.com/pieceofcake2/cakephp/pull/56))

- **PHPStan**: `src/Cake` (including `TestSuite`) now passes PHPStan level 5 with `pieceofcake2/phpstan-cakephp2` ^0.2.4, without a baseline. CI runs PHPStan on every pull request
- **No signature changes**: Only docblocks were corrected (`@param`, `@return`, `@var`, `@property`, `@method`, `@param-out`, `@phpstan-impure`). No native parameter, return or property types were added, and no public or protected method or property signature changed, so subclasses in applications and plugins keep working. The only declaration changes are the properties listed below, which were previously created dynamically
- **Internal clean-ups without behavior change**: `extract()` calls were replaced by explicit variables, variables that were only set on some code paths are initialised, `return voidMethod()` statements were split, always-true/false conditions were removed, and internal functions receive values of the documented type (e.g. `ini_set()` values as strings)

### Bug Fixes ([PR #56](https://github.com/pieceofcake2/cakephp/pull/56))

- **Dynamic properties**: Declare `ConsoleOptionParser::$_tokens`, `HelpFormatter::$_parser`, `Scaffold::$redirect` / `$modelClass` / `$modelKey` / `$ScaffoldModel` / `$scaffoldActions`, `MemcachedEngine::$_compiledGroupNames`, `CakeFixtureManager::$_processed`, `CakeTestFixture::$Schema`, `CakeTestSuiteCommand::$_params` and `ControllerTestDispatcher::$response`, which were created dynamically (deprecated as of PHP 8.2). `CakeFixtureInjector` no longer adds `$fixtureManager` to plain PHPUnit test cases, and `Sqlserver::_execute()` throws `PDOExceptionWithQueryString` instead of adding `$queryString` to a `PDOException`
- **Null arguments**: `ConsoleLog`, `CakeText::toList()` and `Shell::shortPath()` no longer pass `null` to internal functions (deprecated as of PHP 8.1)
- **HttpSocketResponse**: Trailer headers after a chunked body no longer cause a fatal error (call to the undefined `_buildHeader()`); they are merged into the response headers
- **App**: `App::import()` with several search paths no longer stops looking after the first path (it could include a directory instead of the file)
- **ExtractTask**: A literal count argument of `__dcn()` / `__dxcn()` no longer overwrites the parser position, which skipped the following calls
- **PaginatorComponent**: `mergeOptions()` ignores the request parameters for an unknown `paramType` instead of throwing a `TypeError`
- **FlashComponent**: The constructor calls `Component::__construct()`, so `$settings` is set
- **PrivateActionException**: The previous exception passed to the constructor is kept
- **AclNode**: The "Model class not found" exception of `node()` has a formatted message
- **CakeNumber**: `fromReadableSize()` treats a non-numeric value with a unit (e.g. `'XMB'`) as invalid (returns `$default` or throws) instead of throwing a `TypeError`
- **Validation**: The deep `luhn()` check returns `false` for values with non-digit characters instead of throwing a `TypeError`, and accepts integers
- **Mysql** / **Sqlserver**: `listSources()` and `Mysql::listDetailedSources()` return an empty list when the query fails instead of calling `closeCursor()` on `false`
- **TestSuite**: `assertTags()` prints the `$fullDebug` output before the assertion fails; `ConsoleOutputStub::write()` returns the number of bytes written like `ConsoleOutput::write()`; `CakeHtmlReporter` / `CakeTextReporter` code coverage output works with php-code-coverage 9

### Console Without an Application core.php ([PR #59](https://github.com/pieceofcake2/cakephp/pull/59))

- **ShellDispatcher**: When the application has no `Config/core.php`, the console no longer stops with the fatal error "Can't find application core file". `_bootstrap()` again tells `Cake/bootstrap.php` to skip `Configure::bootstrap()` in that case and loads the default `core.php`, as it was meant to, so the console starts and commands that do not need the application settings can run. Applications with a `Config/core.php` are not affected

### Bug Fixes ([PR #58](https://github.com/pieceofcake2/cakephp/pull/58))

- **E_USER_ERROR**: `Configure::bootstrap()` throws `ConfigureException` when the application `core.php` or `bootstrap.php` is missing, and `TranslateBehavior` throws `CakeException` when its datasource is not connected or the translation association is already bound to the model, instead of triggering `E_USER_ERROR` (deprecated as of PHP 8.4). The messages are unchanged
- **ShellDispatcher**: A `ConfigureException` from bootstrapping the application (missing `core.php` or `bootstrap.php`) is printed as `Error: ...` with exit code 1 instead of an uncaught exception or a stack trace
- **bin/cake**: A missing Composer autoloader is printed to STDERR with exit code 1 instead of triggering `E_USER_ERROR`
- **ConsoleLog**: Colored or plain output is decided by the configured `stream` instead of STDIN, so logging to a file or pipe from a terminal no longer writes ANSI escape codes. Plain output is still forced on Windows consoles without ANSI support, and an explicit `outputAs` setting is kept
- **ConsoleOutput**: Uses `stream_isatty()` to detect a terminal, so streams that cannot be checked (e.g. `php://memory`, `php://temp`) get plain output without a `posix_isatty()` warning
- **Tests**: `AllTestSuiteTest` includes the `TestSuite/Fixture` and `TestSuite/Stub` tests, which never ran before; `CakeFixtureManagerTest` passes when run on its own

### Enhancements: CookieComponent `Cookie.key` and `Cookie.type` ([PR #62](https://github.com/pieceofcake2/cakephp/pull/62))

- **CookieComponent**: Without a `'key'` setting, the key defaults to Configure `Cookie.key` when it is a non-empty string, otherwise to `Security.salt` as before. Without a `'type'` setting, the type defaults to Configure `Cookie.type` when it is set (validated by `type()`, so an invalid value triggers the usual `E_USER_WARNING` and falls back to `'cipher'`), otherwise to `'cipher'` as before. An explicit `'key'` / `'type'` setting, `$this->Cookie->key = ...` or `$this->Cookie->type(...)` always wins. Nothing changes for applications that do not set `Cookie.key` or `Cookie.type`
- **IntegrationTestTrait**: `cookieEncrypted()` / `assertCookieEncrypted()` use the same default key (`Cookie.key`, else `Security.salt`)

### Deprecation: 'cipher' encryption ([PR #62](https://github.com/pieceofcake2/cakephp/pull/62))

- **Security::cipher()** / **CookieComponent `'cipher'` type**: Deprecated. It is not secure with any `Security.cipherSeed`. The first call in a process writes a `warning` to the log, also with debug off, and with debug on also triggers `E_USER_DEPRECATED`. The message names the source (`CookieComponent (type 'cipher')` or `Security::cipher() (called from Class::method())`) and the fix: set Configure 'Cookie.type' to 'aes' and 'Cookie.key' to a random value of at least 32 bytes, with Security.useOpenSsl enabled. The `cipher()` output, cookie values and return values are unchanged, and nothing throws
- **Security.cipherSeed range**: `mt_srand()` keeps 32 bits, so `Security::cipher()` uses the seed as given only when it is a digits-only number from 1 to 4294967295. Other values are reduced: empty, `0`, non-numeric values, values of 27 or more digits and most 26-digit values (including the former default `76859309657453542496749683645`) become seed 0. The first `Security::cipher()` call with such a seed logs a `warning` once per process; the seed value is never logged
- **Debugger::checkSecurityKeys()**: With debug on (called by `AuthComponent`), also triggers an `E_USER_NOTICE` when `Security.cipherSeed` is set but outside 1 to 4294967295. The "Please change the value of 'Security.cipherSeed'" notice now fires for both the former default and the new default `'2659758734'`
- **Core config**: The fallback `config/core.php` (used when the application has no `core.php`) sets `Security.cipherSeed` to `'2659758734'` (the same default as the application skeleton) and enables `Security.useOpenSsl`
- **Security::isCipherSeedInRange()**: New internal helper for the range check

### Enhancements: Validation::utf8() (backport from CakePHP 5) ([PR #64](https://github.com/pieceofcake2/cakephp/pull/64))

- **Validation::utf8()**: New validation rule, a backport of `Cake\Validation\Validation::utf8()` from CakePHP 5 (added in CakePHP 3.1). Returns `true` for a string that is valid UTF-8 and has no characters above the Basic Multilingual Plane (4-byte characters such as emoji), which MySQL's `utf8` (`utf8mb3`) columns cannot store. With `['extended' => true]`, characters above the BMP are allowed (for `utf8mb4`). Non-string values (including arrays, integers and `null`) and invalid UTF-8 byte sequences (e.g. truncated sequences, overlong encodings and encoded surrogates) are rejected in both modes. Usable in model validation as `'rule' => 'utf8'` or `'rule' => ['utf8', ['extended' => true]]`

### Bug Fixes ([PR #63](https://github.com/pieceofcake2/cakephp/pull/63))

- **Folder**: `Folder::isAbsolute()` / `isRegisteredStreamWrapper()` recognize registered stream wrapper schemes that contain digits, `+`, `-` or `.` (for example `s3://`), so `Folder` and `File` no longer treat such paths as relative

### Debug Error Pages in the CakePHP 5 Style ([PR #60](https://github.com/pieceofcake2/cakephp/pull/60))

- **Error pages**: In debug mode (`debug` > 0) the core error templates (`missing_controller`, `missing_action`, `missing_view`, `missing_layout`, `missing_component`, `missing_helper`, `missing_behavior`, `missing_plugin`, `missing_table`, `missing_connection`, `missing_database`, `missing_datasource`, `missing_datasource_config`, `pdo_error`, `private_action`, `scaffold_error`, `fatal_error`, `error400`, `error500`) are rendered in the new `dev_error` layout ported from CakePHP 5: a header with the error title and exception class, the suggested fix with the real file path of the application layout (`src/Controller/...` or `app/Controller/...`; the suggested class has no `App::uses()` in the modern layout, where the Composer classmap autoloads the classes, except for plugin classes), and a stack trace of the exception and its previous exceptions with code excerpts, collapsible frames, arguments and `(edit)` links that open the file in your editor. The layout has inline CSS and JavaScript and uses no helpers
- **Unchanged**: Production mode (`debug` = 0), error templates provided by the application, plugin or theme, AJAX requests and the console. `Layouts/dev_error.ctp` in the application or theme overrides the core layout, and the `exception_stack_trace` element is kept for application templates
- **ExceptionRenderer**: When an error page fails to render, the fallback still renders the template with the `error` layout, but lets the core templates switch to `dev_error` in debug mode
- **Debugger**: New `Debugger.editor` configure value (default `phpstorm`; also `atom`, `emacs`, `macvim`, `sublime`, `textmate`, `vscode`, `vscodium`) and `Debugger.editorBasePath`, plus `Debugger::editorUrl()`, `Debugger::setEditor()` and `Debugger::addEditor()` as in CakePHP 5, used by the `(edit)` links of the debug error pages
- **Debugger**: `Debugger::excerpt()` no longer shows `<?php` at the start of every line on PHP 8.3+
- **fatal_error.ctp**: The Xdebug function stack is only printed when Xdebug runs in `develop` mode; in other modes Xdebug 3 triggered a warning that broke the page

## v2.12.5 (2026-10-15)

> [!IMPORTANT]
> This release fixes three security vulnerabilities. Upgrade as soon as possible. See [UPGRADE.md](UPGRADE.md#v2125):
> - Email header injection in `SmtpTransport` / `MailTransport` ([GHSA-4f9q-3c4x-52cv](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-4f9q-3c4x-52cv))
> - Path traversal in view, layout and element names ([GHSA-w2mh-x92p-5p95](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-w2mh-x92p-5p95))
> - Open redirect after login in `AuthComponent` ([GHSA-x4p9-p4cx-gxwx](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-x4p9-p4cx-gxwx))
>
> Invalid `ORDER BY` directions now trigger an `E_USER_DEPRECATED` error; the generated SQL is unchanged.

### Security Fixes

- **SmtpTransport** / **MailTransport**: Strip CR and LF from email header names and values (folded header lines are kept), so header values built from user input can no longer inject headers or start the body ([GHSA-4f9q-3c4x-52cv](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-4f9q-3c4x-52cv), the CakePHP 2 equivalent of CVE-2026-77634)
- **View**: View, layout and element file names containing `..` must resolve inside the view paths; files outside them are treated as missing ([GHSA-w2mh-x92p-5p95](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-w2mh-x92p-5p95), the CakePHP 2 equivalent of CVE-2026-48820)
- **AuthComponent**: `redirectUrl()` collapses protocol-relative URLs (`//host`, `/\host`, `\\host`) stored in `Auth.redirect` to a local path, so a crafted request URL can no longer redirect to another site after login ([GHSA-x4p9-p4cx-gxwx](https://github.com/pieceofcake2/cakephp/security/advisories/GHSA-x4p9-p4cx-gxwx))


### Deprecation: invalid ORDER BY directions ([PR #54](https://github.com/pieceofcake2/cakephp/pull/54))

- **DboSource::order()**: The sort direction is now checked. Valid directions are `ASC` or `DESC`, optionally followed by `NULLS FIRST` / `NULLS LAST`, matched case-insensitively and with any surrounding whitespace (`'asc'`, `' Desc '`, `'desc  nulls  last'`). This applies to array values (`['Post.title' => 'desc']`), the `$direction` argument and a direction written after the field in a string key (`'Post.title DESC'`)
- Any other direction (for example `"='test' DESC"`, `'ASC; ...'`, `'DESC LIMIT 1'`, `'NULLS FIRST'`, or a non-string value) triggers an `E_USER_DEPRECATED` error. The generated SQL is exactly the same as before; invalid directions will throw an `InvalidArgumentException` in a future version (see [UPGRADE.md](UPGRADE.md#v2125))
- Field / expression handling is unchanged, and `DboSource::expression()` objects are used verbatim without the check

## v2.12.4 (2026-10-14)

> [!NOTE]
> No behavior changes. This release adds the `IntegrationTestTrait` / `EmailTrait` backport for request-level controller tests (see [UPGRADE.md](UPGRADE.md#v2124)) and fixes docblock types and flaky tests.

### Integration Testing Backport ([PR #51](https://github.com/pieceofcake2/cakephp/pull/51))

- **IntegrationTestTrait**: New test trait, a backport of `Cake\TestSuite\IntegrationTestTrait` from CakePHP 5.x ([src/TestSuite/IntegrationTestTrait.php](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/IntegrationTestTrait.php)) adapted to CakePHP 2. Dispatches requests through the real `Dispatcher` and captures the `CakeResponse`; usable from any test case (no `ControllerTestCase` needed)
  - Requests: `get()`, `post()`, `put()`, `patch()`, `delete()`, `head()`, `options()` with string URLs, full URLs or routing arrays, array data or a raw body
  - Setup: `configRequest()`, `replaceRequest()`, `requestAsJson()`, `session()`, `cookie()`, `cookieEncrypted()` (`CookieComponent` format), `enableSecurityToken()` / `setUnlockedFields()` (valid `SecurityComponent` `_Token` fields and CSRF token), `enableCsrfToken()`, `enableRetainFlashMessages()`, `disableErrorHandlerMiddleware()` (throw exceptions instead of rendering the error page)
  - Assertions on the status, redirect, headers, content type, body, template, layout, session, flash messages, cookies and sent files (`assertResponseOk()`, `assertRedirect()`, `assertHeader()`, `assertResponseContains()`, `assertTemplate()`, `assertSession()`, `assertFlashMessage()`, `assertCookieEncrypted()`, `assertFileResponse()`, ...), and `viewVariable()`
  - `exit` after a sent response (`Controller::redirect()`, `AuthComponent`, `AssetDispatcher`, redirect routes) is replaced by `IntegrationTestStop`, thrown by the test response `IntegrationTestResponse`; `IntegrationTestDispatcher` dispatches without sending the response
- **Constraints**: PHPUnit constraints backported from [src/TestSuite/Constraint](https://github.com/cakephp/cakephp/tree/5.x/src/TestSuite/Constraint) (`Response/*`, `Session/*`, `View/*`, `Email/*`), named with a `Constraint` suffix (e.g. `StatusOkConstraint`, `FlashParamEqualsConstraint`, `MailSentToConstraint`)
- **EmailTrait** / **TestEmailTransport**: Backport of [src/TestSuite/EmailTrait.php](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/EmailTrait.php) and [src/TestSuite/TestEmailTransport.php](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/TestEmailTransport.php). Every `CakeEmail` sends with `TestEmailTransport` during the test, and `assertMailCount()`, `assertNoMailSent()`, `assertMailSentTo()`, `assertMailSentFrom()`, `assertMailSentWith()`, `assertMailContains()`, `assertMailContainsText()`, `assertMailContainsHtml()`, `assertMailSubjectContains()`, `assertMailContainsAttachment()` (and `...At()` variants) check the captured emails
- **CakeEmail**: New `CakeEmail::overrideTransport($name)` / `CakeEmail::transportOverride()` to make every instance use one transport (used by `TestEmailTransport::replaceAllTransports()`); no change unless called
- **CookieCryptTrait** (TestSuite): Encrypts and decrypts values in the `CookieComponent` format, backport of `Cake\Utility\CookieCryptTrait`

### Docblock Types ([PR #49](https://github.com/pieceofcake2/cakephp/pull/49))

- **ErrorHandler** / **ExceptionRenderer**: Document the `$exception` parameters of `handleException()`, `_getMessage()`, `_log()`, `ExceptionRenderer::__construct()` and `_getController()` as `Throwable`, matching what `set_exception_handler()` passes on PHP 8 (no behavior change)
- **View**: Document `$subDir` as `string|null` (its default is `null`)
- **AclComponent**: Document `$Aro` / `$Aco` as the ACL adapter's node objects (`Aro|PhpAro|null` / `Aco|PhpAco|null`) instead of `string`
- **Tests**: Fix flaky `testConfigurationConflict` in `MemcacheEngineTest` / `MemcachedEngineTest` (one-second expiry granularity), and `CakeTimeTest::testWasWithinLast` / `testTimeAgoInWords` (the clock can tick between the relative input and "now")

## v2.12.3 (2026-10-14)

> [!IMPORTANT]
> This release contains a security fix and two fixes that change behavior. See [UPGRADE.md](UPGRADE.md#v2123) before upgrading:
> - `CookieComponent` now honors the `'type'` setting in `$components`. Applications that already set it will write cookies with that type, and cookies written before the upgrade can no longer be read.
> - `cake server` now listens on port `8765` by default instead of `80`.

### Xml::build() SSRF Fix ([PR #33](https://github.com/pieceofcake2/cakephp/pull/33))

- **Xml**: `Xml::build()` no longer fetches `https://` URLs when `'readFile' => false` (operator precedence bug that allowed SSRF)

### CookieComponent `'type'` Setting ([PR #34](https://github.com/pieceofcake2/cakephp/pull/34))

- **CookieComponent**: Honor the `'type'` setting (e.g. `'Cookie' => ['type' => 'aes']`), which was previously ignored so cookies were always encrypted with the default `'cipher'` type
- **Behavior change**: Applications that already set `'type'` in `$components` now write cookies with that type, so cookies written before the upgrade (with `'cipher'`) can no longer be read. See UPGRADE.md

### CakeTestLoader Modern Layout ([PR #35](https://github.com/pieceofcake2/cakephp/pull/35))

- **CakeTestLoader**: List test cases from `tests/TestCase` (app and plugins), falling back to `Test/Case`, so `cake test app` without a test case and the web test runner no longer show an empty list for the modern layout

### Sqlserver Default PDO Flags ([PR #36](https://github.com/pieceofcake2/cakephp/pull/36))

- **Sqlserver**: Fix operator precedence in `connect()` so `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` is merged into the `'flags'` setting

### ServerShell Default Port ([PR #37](https://github.com/pieceofcake2/cakephp/pull/37))

- **ServerShell**: Change the default port of `cake server` from `80` (requires root) to `8765`, and show the default host and port in the help

### TestShell PHPUnit Version Message ([PR #38](https://github.com/pieceofcake2/cakephp/pull/38))

- **TestShell**: Ask for PHPUnit 9.6 (instead of v3.7) when PHPUnit cannot be loaded

### Documentation Fixes ([PR #39](https://github.com/pieceofcake2/cakephp/pull/39))

- **README.md**: Use `^2.12` in the Composer install example, and link to the Piece of Cake 2 book (https://pieceofcake2.github.io/docs/)
- **UPGRADE.md**: Show the SQL Server `schema` setting as a string per connection (the driver does not accept an array mapping)

## v2.12.2 (2026-10-14)

### PHP 8.6 Support

- **Session handlers**: Support `session.use_strict_mode`, which is enabled by default since PHP 8.6
  - `SessionHandlerAdapter` implements `SessionIdInterface` and `SessionUpdateTimestampHandlerInterface` (PHP 8.6 deprecates session handlers without `create_sid()`, `validateId()` and `updateTimestamp()`)
  - `CacheSession::destroy()` and `DatabaseSession::destroy()` succeed for sessions that were never written, as with PHP's files handler
- **CI**: Add PHP 8.6 to the test matrix as an allowed failure until it is released, and update GitHub Actions to the latest major versions

## v2.12.1 (2026-10-14)

### Bug Fixes

- **ControllerTestCase**: Fix the `@method` annotation of `testAction()` so that IDEs can resolve its parameters and return type

## v2.12.0 (2025-10-09)

### Composer Autoloading Migration ([PR #22](https://github.com/pieceofcake2/cakephp/pull/22))

- **Composer Classmap Autoloading**: Migrated from include-path to Composer classmap autoloading for core and application classes
  - Removed all top-level `App::uses()` declarations from framework core (798 occurrences removed)
  - Framework and application classes are now automatically loaded via Composer's classmap autoloader
  - **App Base Classes**: For backward compatibility, framework classes extending application base classes still use `App::uses()`:
    - `App::uses('AppController', 'Controller')` - for CakeErrorController
    - `App::uses('AppModel', 'Model')` - for I18nModel, AcoAction, Permission
    - `App::uses('AppShell', 'Console/Command')` - for all core shell commands
    - `App::uses('AppHelper', 'View/Helper')` - for all core helpers
    - **Note**: These `App::uses()` calls are unnecessary if your application's `composer.json` includes classmap autoloading configuration similar to [pieceofcake2/app](https://github.com/pieceofcake2/app)
  - **Plugin Classes**: Plugins must continue using `App::uses()` for their classes
    - Example: `App::uses('MyPluginHelper', 'MyPlugin.View/Helper')`
  - **Custom Autoloader Override**: If you need to override autoloading behavior, register your custom autoloader with prepend flag (see UPGRADE.md for details)

## ｖ2.11.0 (2025-10-09)

### Directory Structure Modernization ([PR #21](https://github.com/pieceofcake2/cakephp/pull/21))

- **Modern Directory Layout**: Restructured directory layout to modern standards
  - Moved `lib/Cake/` → `src/Cake/` (framework source code)
  - Moved `lib/Cake/Test/Case/` → `tests/TestCase/` (framework tests)
  - Moved `lib/Cake/Test/Fixture/` → `tests/Fixture/` (test fixtures)
  - Moved `lib/Cake/Console/cake` → `bin/cake` (console executable)
  - Moved `lib/Cake/Config/` → `config/` (framework configuration files at root level)
  - Moved `lib/Cake/VERSION.txt` → `VERSION.txt` (framework version file at root level)
  - Moved template files from `lib/Cake/View/` and `lib/Cake/Templates/` → `templates/` (framework default templates at root level)
  - **Modern Test Directory Support**: Applications and plugins can now use `tests/TestCase/` directory structure
    - Test suite automatically detects both `Test/Case/` (traditional) and `tests/TestCase/` (modern) directories
    - Fixture loader supports both `Test/Fixture/` and `tests/Fixture/` locations
    - Enables gradual migration to modern directory structure for application tests
  - Updated all path references in configuration files (phpcs.xml, phpstan.neon, phpunit.xml.dist, composer.json)
  - Updated all test suite and fixture loader paths to use new directory structure
  - Updated CI workflow and documentation paths
  - Removed obsolete composer scripts (cs-check, test)

- **Framework Configuration Separation**: Separated framework-level configuration from application code
  - Added `CORE_ROOT` constant to reference framework root directory
  - Updated all config file path references from `CAKE . 'Config'` to `CORE_ROOT . DS . 'config'`
  - Moved framework config files (config.php, routes.php, unicode casefolding tables) to root-level `config/` directory
  - Follows modern PHP project structure conventions where `config/` exists at project root level

- **Smart Console Executable**: Enhanced `bin/cake` with intelligent path detection
  - Automatically detects project root by locating `composer.json` from Composer's autoloader
  - When installed as dependency (`vendor/bin/cake`), intelligently determines the application directory:
    - Auto-detects modern `src/` or traditional `app/` directory structure
    - Falls back to `vendor/pieceofcake2/app` for plugin testing scenarios
  - No manual configuration needed
  - Backward compatible with existing installations
  - Simplifies console command execution across different project structures

### Developer Experience Improvements

- **PHPDoc Generic Type Annotations**: Added generic type annotations for better IDE support
  - Added `@template T` to `ClassRegistry::init()` with detailed array shape types for parameters
  - Added `@template T` to `Controller::loadModel()` with PHPStan assertion for dynamic properties
  - Improves type inference and autocompletion for model instantiation in modern IDEs and static analysis tools
  - Supports PHPStan and Psalm for better static analysis

### Legacy Upgrade Plugin Extraction ([PR #19](https://github.com/pieceofcake2/cakephp/pull/19))

- **Legacy Upgrade Functionality Separation**: Extracted legacy upgrade functionality to separate plugin ([pieceofcake2/upgrade](https://github.com/pieceofcake2/upgrade))
  - Removed `UpgradeShell` from core (located at `lib/Cake/Console/Command/UpgradeShell.php`)
  - Updated test files to remove references to `upgrade` command from shell lists
  - The Upgrade plugin is only relevant for upgrading legacy CakePHP 1.3 applications to 2.0, which is extremely rare in 2025
  - **Migration**: Install Upgrade plugin separately if needed: `composer require --dev pieceofcake2/upgrade`
  - **Note**: Most users will never need this plugin

## v2.10.24.8 (2025-10-06)

### Email Header Encoding Compatibility

- **mbstring Extension Fallback**: Added automatic fallback for email header encoding when `mbstring` extension is not loaded ([PR #18](https://github.com/pieceofcake2/cakephp/pull/18))
  - `CakeEmail::_encode()` now checks if `mbstring` extension is loaded using `extension_loaded('mbstring')`
  - Uses native `mb_encode_mimeheader()` when `mbstring` is available (recommended)
  - Automatically falls back to `Multibyte::mimeEncode()` when `mbstring` is not available
  - Removed `mb_encode_mimeheader()` polyfill from `bootstrap.php`
  - Updated `MailTransportTest` to use same fallback logic for test compatibility
  - **Important**: `mb_encode_mimeheader()` is **not available** in Symfony's mbstring polyfill
  - **Strongly recommended**: Install the `mbstring` extension for better compatibility and performance
  - **Note**: While Symfony polyfill provides most mbstring functions, `mb_encode_mimeheader()` requires the native extension

### Bake Plugin Extraction

- **Bake Functionality Separation**: Extracted all Bake-related code to separate plugin ([pieceofcake2/bake](https://github.com/pieceofcake2/bake)) ([PR #17](https://github.com/pieceofcake2/cakephp/pull/17))
  - Removed `BakeShell` and all Bake tasks: `BakeTask`, `ModelTask`, `ControllerTask`, `ViewTask`, `FixtureTask`, `TestTask`, `TemplateTask`, `ProjectTask`, `PluginTask`, `DbConfigTask`, `CommandTask`
  - Removed Bake templates from `lib/Cake/Console/Templates/` directory
  - Removed application skeleton templates from `lib/Cake/Console/Templates/skel/` directory
  - Moved default view templates to `lib/Cake/Templates/` for core framework usage
  - Removed `DbConfigTask` dependency from `AclShell` and `I18nShell` (now shows error message instead)
  - Removed all Bake-related test files and fixtures
  - Updated `.gitignore` and `codecov.yml` to remove Bake-specific entries
  - Allows independent development and versioning of Bake functionality
  - **Migration**: Install Bake plugin separately: `composer require pieceofcake2/bake:^2.0`
  - **Note**: Dispatcher files (`index.php`, `test.php`, `cake`) now available in Bake plugin at `plugins/Bake/Console/Templates/skel/`

## v2.10.24.7 (2025-10-06)

### Application Skeleton Extraction

- **App Skeleton Separation**: Extracted application skeleton to separate package ([pieceofcake2/app](https://github.com/pieceofcake2/app))
  - Removed `app/` directory from core repository
  - Removed `plugins/` directory from core repository (not required for framework testing)
  - Allows independent versioning and modernization of application structure
  - Updated `.gitignore` to remove `/app/Config/database.php` entry

### CakePHP 5.x Migration Support

- **Modern Directory Structure Support**: Added support for CakePHP 5.x-style directory layouts
  - All tests pass with CakePHP 5.x-style directory structure (verified with pieceofcake2/app)
  - Improved flexibility of `APP_DIR` and `WEBROOT_DIR` constants for custom directory layouts
  - Supports both traditional CakePHP 2.x and modern CakePHP 5.x-style directory layouts
  - Enables gradual migration path: modernize folder structure while on CakePHP 2.x, then focus solely on code changes when upgrading to CakePHP 5.x

### Internal Improvements

- **Organization Rename**: Renamed GitHub organization from `friendsofcake2` to `pieceofcake2`
  - Updated all repository references and URLs
  - Updated composer package namespace
  - Does not impact user applications

- **Vendor Directory Naming**: Standardized vendor directory name from `vendors/` to `vendor/` ([PR #16](https://github.com/pieceofcake2/cakephp/pull/16))
  - Changed default `VENDORS` constant to point to `vendor/` (standard Composer convention)
  - Updated all references in test bootstrap files and CI configuration
  - Updated `.gitignore`, `composer.json`, `phpcs.xml`, `phpstan.neon`, and `phpunit.xml.dist`
  - This change only affects CakePHP core testing infrastructure
  - Does not impact user applications

### Documentation

- **README Improvements**: Enhanced documentation for migration path clarity
  - Added visual comparison of traditional vs gradual migration approaches
  - Clarified 3-step migration process with benefits
  - Updated Project Goals to include gradual migration support

## v2.10.24.6 (2025-10-03)

### Bug Fixes

- **Dispatcher**: Fixed `$_SERVER['PHP_SELF']` to be set for all SAPI modes (Apache, nginx, etc.)
  - Previously only set when using PHP's built-in server
  - Ensures correct routing and URL generation across all web server configurations

- **Autoloader**: Prepended App autoloader to ensure priority over Composer autoloader
  - Changed `spl_autoload_register(['App', 'load'])` to `spl_autoload_register(['App', 'load'], true, true)`
  - Added throw flag (second parameter) to throw exception if registration fails
  - Added prepend flag (third parameter) to place App::load() at the front of the autoloader queue
  - Ensures CakePHP's class loading conventions (App::uses, etc.) work correctly

## v2.10.24.5 (2025-10-03)

### SSL/TLS Certificate Handling

- **CA Bundle Modernization**: Replaced bundled cacert.pem with composer/ca-bundle ([PR #15](https://github.com/pieceofcake2/cakephp/pull/15))
  - Added `composer/ca-bundle` ^1.5 as dependency
  - Replaced hardcoded `CAKE/Config/cacert.pem` with `CaBundle::getSystemCaRootBundlePath()`
  - Uses system CA certificate bundle when available (OpenSSL default cert dir/file)
  - Falls back to Mozilla CA bundle provided by composer/ca-bundle
  - Supports both `cafile` (single bundle file) and `capath` (certificate directory) configurations
  - Automatically updated and maintained by Composer ecosystem
  - Removed outdated `lib/Cake/Config/cacert.pem` file (last updated 2016)
  - Updated `FolderTest` to reflect removed cacert.pem file

### PHPUnit Integration

- **PHPUnit 9+ Migration**: Complete migration to PHPUnit 9+ ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Refactored `CakeTestLoader` to implement `TestSuiteLoader` interface with composition pattern
  - Fixed critical infinite recursion bug in `CakeTestLoader::reload()` method
  - Updated `CakeBaseReporter` from extending `PHPUnit_TextUI_ResultPrinter` to implementing `ResultPrinter` interface
  - Added missing interface methods: `write()`, `addWarning()`, `addRiskyTest()` to `CakeBaseReporter`
  - Added type declarations throughout TestSuite classes for PHP 8.0+ compatibility
  - Added `run()` method to `CakeTestSuiteCommand` with proper test file resolution
  - Updated all PHPUnit class references from `PHPUnit_Framework_*` to `PHPUnit\Framework\*` namespace
  - Removed legacy `CakeTestRunner` class (functionality moved to `CakeTestSuiteCommand`)
  - Updated test assertions to use `withConsecutive()` and callback-based output verification
  - Fixed `HtmlCoverageReportTest` to use `CakeHtmlReporter` instead of `CakeBaseReporter`
  - All tests now pass with PHPUnit 9.6+

### Code Modernization

- **Legacy PHP Version Checks Removal**: Removed compatibility code for PHP < 8.0 ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Removed PHP 5.3/5.4 version checks from `ErrorHandler`, `Debugger`, `CakeSocket`
  - Removed PHP 5.4 fallback logic in `CakeNumber::_numberFormat()`
  - Removed PHP version checks from `CakeTimeTest`
  - Simplified SSL/TLS configuration in `CakeSocket` (removed PHP 5.3.2/5.6.0 checks)
  - Removed obsolete Debugger workarounds for PHP < 5.3
  - Updated `strpos()` to `str_ends_with()` in `CakeTestLoader`
  - Changed `include` to `require_once` in bootstrap files for consistency
  - Fixed indentation in `basics.php` to standardize whitespace

### Performance Improvements

- **JSON Output Optimization**: Improved JSON rendering performance ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - `JsonView` now only applies `JSON_PRETTY_PRINT` when debug mode is enabled
  - Reduces JSON encoding overhead in production environments

### Autoloading Improvements

- **App::load() Enhancement**: Added class existence check ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - `App::load()` now returns early if class already exists
  - Prevents unnecessary file loading and improves performance
  - Replaced direct `ShellDispatcher` require with `App::load()` in test bootstrap
  - Added `class_exists()` guard in test bootstrap to prevent double definition errors

### Code Quality

- **Coding Standards**: Applied phpcs fixes and removed deprecated exclusions ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Removed `get_magic_quotes_gpc` exclusion from phpcs.xml (function removed in PHP 7.4)
  - Added trailing commas to `trigger_error()` calls for consistency
  - Added `CakeHtmlReporter` to unused variable exclusion list

### Documentation

- **GitHub URLs Update**: Updated all GitHub organization references ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))
  - Changed GitHub URLs from `cakephp` to `pieceofcake2` in `home.ctp` templates
  - Updated changelog, issues, wiki, and plugin repository links
  - Removed upstream `CONTRIBUTING.md` (fork has different contribution guidelines)

### Dispatcher Improvements

- **Simplified Dispatcher Entry Points**: Streamlined autoload handling in dispatcher files
  - Removed manual `CAKE_CORE_INCLUDE_PATH` detection from dispatcher files (set automatically in bootstrap.php)
  - Removed `include_path` manipulation logic (Composer autoload handles this)
  - Removed legacy `app/Vendor/cakephp/cakephp` path detection
  - Added clear error messages when Composer vendors directory or autoload.php is missing
  - Added clear error messages when CakePHP core is not installed via Composer
  - **Breaking Change**: Projects must use Composer for installation (non-Composer installation no longer supported)
  - **Migration**: If upgrading, copy updated dispatcher files from `vendors/pieceofcake2/cakephp/lib/Cake/Console/Templates/skel/` to your project:
    - `skel/webroot/index.php` → `app/webroot/index.php`
    - `skel/webroot/test.php` → `app/webroot/test.php`
    - `skel/Console/cake` → `app/Console/cake`

### PHP Support

- **PHP 8.5 Support**: Added PHP 8.5 to CI testing matrix ([PR #12](https://github.com/pieceofcake2/cakephp/pull/12))
  - All tests pass on PHP 8.5
  - Confirmed compatibility across all database types (MySQL, PostgreSQL, SQLite, SQL Server)
  - Fixed int cast warnings for values outside int range:
    - `Security::cipher()` - Suppress cipherSeed cast warning (maintains encrypted data compatibility)
    - `PaginatorComponent::paginate()` - Suppress page number cast warning (maintains pagination behavior)
    - `DboSource::limit()` - Suppress limit/offset sprintf warnings (maintains query generation)
    - `Postgres::limit()` - Suppress limit/offset sprintf warnings (maintains query generation)
    - `Sqlite::limit()` - Suppress limit/offset sprintf warnings (maintains query generation)
  - Added return type hints to database `limit()` methods: `?string`

### Dependencies

- **Mbstring Extension**: Made mbstring extension optional with Symfony polyfill fallback
  - Moved `ext-mbstring` from `require` to `suggest` in composer.json
  - Added `symfony/polyfill-mbstring` as required dependency
  - Replaced most `Multibyte` methods with direct `mb_*` function calls (stripos, stristr, strlen, strpos, strrchr, strrichr, strripos, strrpos, strstr, substrCount, substr)
  - Kept original implementations for `strtolower` and `strtoupper` due to compatibility differences with `mb_*` functions
  - Symfony polyfill provides automatic fallback when native mbstring extension is not available

### CI/CD Improvements

- **Qlty Coverage Integration**: Added Qlty code coverage reporting to CI workflow

### PHP 8.0+ Compatibility

- **Mail Function Compatibility**: Updated mail transport for PHP 8.0+ strict typing ([PR #10](https://github.com/pieceofcake2/cakephp/pull/10))
  - Changed `mail()` function parameter defaults from `null` to empty string
  - Added strict type declarations to `MailTransport::_mail()` method
  - Updated method signature: `_mail(string $to, string $subject, string $message, array|string $headers = [], string $params = ''): void`
  - Replaced `@` error suppression with `set_error_handler()` for better error handling
  - Updated `CakeEmail::send()` default parameter from `null` to empty string
  - Removed deprecated `safe_mode` checks (removed in PHP 7.2.0)
  - Removed `MailTransport.php` from phpcs exclusions (now coding standards compliant)

### Database Support

- **Database Charset Configuration**: Moved character set configuration from `SET NAMES` to DSN connection options ([PR #11](https://github.com/pieceofcake2/cakephp/pull/11))
  - **MySQL**: Charset now added directly to DSN (e.g., `mysql:...;charset=utf8`)
  - **PostgreSQL**: Client encoding added to DSN via options parameter (e.g., `pgsql:...;options='--client_encoding=UTF8'`)
  - **PostgreSQL**: `sslmode` parameter is now optional in DSN
  - `setEncoding()` methods still use `SET NAMES` for runtime changes
  - More efficient and reliable than executing `SET NAMES` after connection

- **SQL Server 2022 Support**: Added comprehensive SQL Server 2022 support for testing and development ([PR #9](https://github.com/pieceofcake2/cakephp/pull/9))
  - **Docker Infrastructure**:
    - Added SQL Server 2022 container to docker-compose.yml with automatic database initialization
    - Created custom entrypoint script for automatic database and schema creation (cakephp_test with schemas: dbo, test2, test3)
    - Configured health checks using mssql-tools18 for SQL Server 2022 compatibility
    - Added pdo_sqlsrv extension and Microsoft ODBC Driver installation to Docker web container
  - **SQL Server Driver Modernization**:
    - Implemented schema-based configuration system (replaces cross-database approach)
    - Added default schema support with 'dbo' fallback
    - Improved DSN connection string building with support for port and SSL/TLS options via `options` array
    - Fixed encoding configuration to properly map to PDO constants (e.g., 'utf8' → PDO::SQLSRV_ENCODING_UTF8)
    - Fixed macOS locale issues that caused "collate_byname failed to construct for UTF-8" errors
    - Enhanced NULL length handling for column descriptions
    - Fixed COUNT(DISTINCT field) to properly generate field aliases
    - Implemented XOR-based NOT operator for bit fields (replaces subtraction approach)
    - Improved IDENTITY_INSERT handling with primary key detection
    - Added PRIMARY KEY inline constraint to column definitions
    - Added explicit return type declaration to `describe()` method: `describe($model): array`
  - **CI/CD Integration**:
    - Added SQL Server to GitHub Actions CI matrix
    - Automated ODBC driver installation in CI workflow
    - Streamlined database initialization using Docker volumes
  - **Test Improvements**:
    - Removed obsolete SQL Server workarounds in TranslateBehaviorTest
    - Added SQL Server incompatibility skips for cache query tests
    - Fixed assertion order in CakeSchemaTest for better compatibility
    - Updated SqlserverTest to expect PRIMARY KEY in column definitions
  - **Configuration Changes**:
    - Schema configuration now uses array mapping: `'schema' => ['default' => 'dbo', 'test2' => 'test2', ...]`
    - Connection options (SSL/TLS) now configured via `options` array instead of inline DSN

## v2.10.24.4 (2025-09-25)

### Code Quality

- **PHP CodeSniffer Update**: Upgraded to CakePHP CodeSniffer 5.3 standards ([PR #8](https://github.com/pieceofcake2/cakephp/pull/8))
  - Updated `cakephp/cakephp-codesniffer` from 1.0.0 to 5.3
  - Created comprehensive `phpcs.xml` configuration for CakePHP 2.x compatibility
  - Applied automatic code formatting fixes across 683 files using phpcbf
  - Added exclusion rules for CakePHP 2.x specific patterns (App::uses, double underscore properties, etc.)
  - Fixed switch statement fall-through issues with explicit comments
  - Standardized array alignment using Generic.Formatting.MultipleStatementAlignment
  - Added proper documentation to SessionHandlerAdapter class
  - Configured appropriate exclusions for test files and deprecated functions

### Code Modernization

- **PHP 8 Syntax Modernization**: Complete codebase modernization to PHP 8 syntax ([PR #7](https://github.com/pieceofcake2/cakephp/pull/7))
  - Converted all `array()` syntax to short array syntax `[]`
  - Implemented PHP 8 native string functions (`str_contains()`, `str_starts_with()`, `str_ends_with()`)
  - Replaced `get_class()` with `::class` constant
  - Updated array destructuring from `list()` to `[]`
  - Applied null coalescing operators where appropriate
  - Replaced `dirname(__FILE__)` with `__DIR__` magic constant
  - Converted `dirname(__FILE__, n)` to `dirname(__DIR__, n)` for multi-level parent directories
  - Modernized 600+ files across lib/Cake, app, Test, TestSuite, and Templates directories

### Development Tools

- Added PHPStan for static analysis with CakePHP 2.x specific extension
- Added Rector for automated PHP syntax modernization
- Upgraded PHP CodeSniffer with CakePHP 5.3 standards
- Configured development tools to be excluded from version control

## v2.10.24.3 (2025-09-24)

### Security Fixes

- **CVE-2020-15400**: Fix CSRF token fixation vulnerability by implementing HMAC-signed tokens ([PR #5](https://github.com/pieceofcake2/cakephp/pull/5))
  - Tokens are now cryptographically signed with the application's Security.salt
  - Prevents attackers from fixating tokens through XSS or physical access
  - Maintains backward compatibility with existing legacy tokens

### Security Enhancements

- **CVE-2015-8379**: Added comprehensive test coverage for CSRF protection bypass prevention ([PR #6](https://github.com/pieceofcake2/cakephp/pull/6))
  - Tests for `_method` parameter override handling
  - Tests for custom/invalid HTTP methods requiring CSRF validation
  - Tests confirming safe methods (GET, HEAD, OPTIONS) are exempt
  - Validates that all non-safe methods require CSRF tokens

### Breaking Changes

- Remove Xcache cache engine support (Xcache is not compatible with PHP 7.0+) ([PR #4](https://github.com/pieceofcake2/cakephp/pull/4))
- Remove Wincache cache engine support (Wincache is not actively maintained for PHP 8.x) ([PR #4](https://github.com/pieceofcake2/cakephp/pull/4))
- Add new `getVersion()` method to MySQL and PostgreSQL drivers (returns string) ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add new `getServerType()` method to MySQL driver (returns string: 'MySQL', 'MariaDB', or 'Aurora MySQL') ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add new `utf8mb4Supported()` method to MySQL driver (returns bool) ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add new `integerDisplayWidthDeprecated()` method to MySQL driver (returns bool) ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

### PHPUnit Compatibility Fixes

- Fix PHPUnit deprecation warnings for `at()` method usage ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PHPUnit deprecation for `expectError()`, `expectWarning()`, `expectNotice()` methods ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PHPUnit data provider naming issue in ExceptionRendererTest ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PHPUnit risky tests by adding missing assertions ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix output buffering issues in tests ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Skip CookieComponent AES tests when mcrypt extension is not available ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))

### Test Isolation Improvements

- Fix test isolation issues by properly cleaning up global state ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2)):
  - BasicsTest: Clean up Config.language setting in tearDown
  - CakeRequestTest: Clean up HTTP_ACCEPT_LANGUAGE server variable
  - L10nTest: Add tearDown to clean up HTTP_ACCEPT_LANGUAGE
  - I18nTest: Improve state management and simplify clear() method
- Standardize tearDown method pattern across all test files to call parent::tearDown() at the end ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))

### PHP 8.0+ Compatibility

- Fix MysqlTest for PHP 8.0+ by removing version check and fixing float assertions ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PostgreSQL `preg_replace()` with null parameter ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix "Trying to access array offset on value of type bool" error in L10n.php ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix I18n and L10n locale handling issues ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))
- Fix PostgreSQL alterSchema null array offset error when field doesn't exist in schema ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

### CI/CD Improvements

- Add MySQL 8.0 support to GitHub Actions workflow ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Add Docker Compose configuration for local testing ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))
- Replace GitHub Actions services with Docker commands for databases ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

### Other Fixes

- Fix controller tests by setting `autoRender` property ([PR #2](https://github.com/pieceofcake2/cakephp/pull/2))

## v2.10.24.2

### 2025-09-19

- Fix tests for pgsql / sqlite on PHP 8.3, 8.4
- Tests pass for mysql / pgsql / sqlite on PHP 8.0, 8.1, 8.2, 8.3, 8.4
- Fix Sqlite `strtoupper` and `trim` with null.

## v2.10.24.1

### 2025-02-04

- Fixes for PHP 8.4: `session_set_save_handler` accepts object, removed `E_STRICT` reference.
- Removed github action with php code sniffer. It's quite painful to work with. Need to migrate to something newer, that will affect code base as little as possible.

### 2024-11-16

- Inflector fix: str_place with null.

### 2024-09-21

- Added wrapper for PDOException to avoid creating dynamic property `queryString`.

### 2024-07-24

- Csrf vulnerabity fix back ported from Cake PHP 3
- Explicit type hint definition of nullable parameters.

### 2024-06-05

- Removed usage of `strftime`, replaced with Intl extension.

### 2024-05-24

- Fix deprecation error in Model: `Automatic conversion of false to array is deprecated`

### 2024-05-22

- Fix deprecation error in I18n: `Automatic conversion of false to array is deprecated`

### 2024-02-02

- `str_len` deprecation warning fix in CakeResponse (passing null instead of `string`)

### 2024-01-19

- `strotime()` and `preg_split()` in CakeResponse deprecation warning fixes (passing null)

### 2024-01-11

- `preg_replace` deprecation warning fixes (passing null instead of `string`)

### 2023-12-22

- `preg_quote()` passing null fix

### 2023-12-19

- Muted dynamic property creation warnings in Controller.php
- Fix passing a null input to h function (PR kamilwylegala/cakephp2-php8#56)
- Fix Hash class callback callable pattern deprecated (PR kamilwylegala/cakephp2-php8#58)

### 2023-11-13

- Silence dynamic property creation warning in Model.php

### 2023-11-02

- Fixed: unitialized property in Debugger.php

### 2023-10-20

- Fallback to empty string from `env()` in basics.php and request handler.

### 2023-10-19

- Removed usage of deprecated `redis->getKeys()` in favor of `redis->keys()`.
- Added docker-compose setup to run tests locally.

### 2023-09-18

- Fix for `ShellDispatcher` where `null` was passed to `strpos` function.

### 2023-08-18

- Fixed PHP8 deprecation notices. Related mostly to passing null as a `$haystack` value.

### 2023-06-02

- Fixed PHP 8.2 deprecation notices in CakeEvent: `Creation of dynamic property ... is deprecated.`

### 2023-02-19

- Fixed PHP 8.1 MySQL test suite.

### 2023-02-11

- Fixed PostgreSQL test suite.

### 2023-01-30

- `PaginatorHelper` fix.

### 2023-01-22

- Fixed views cache when relative time is specified.

### 2023-01-11

- Fixed test suite to run under PHPUnit 9.5 and PHP8. Big kudos to @tenkoma :clap:

### 2022-10-20

- `MailTransport` fix.

### 2022-10-08

- Support for `full_path` when uploading a file, PHP 8.1 only.

### 2022-09-27

- Fixed multiple `CREATE UNIQUE INDEX` statements from schema shell that did not work on PostgreSQL.

### 2022-03-08

- Fixed passing `params["pass"]` argument to `invokeArgs` when resolving controller action - `array_values` used to avoid problems with named parameters.

### 2022-03-03

- Removed `String` class.

### 2022-03-02

- Fixed `ConsoleErrorHandler::handleError` to respect error suppression.

### 2022-01-31

- Fixed `Folder->read`, `array_values` is used to remove keys to prevent usign named arguments in `call_user_func_array`

### 2022-01-16

- Fix Shell `ReflectionMethod::__construct` default null argument in hasMethod

### 2022-01-15

- Readme file update - more explicit content.

### 2022-01-04

- Fixed more deprecation notices
	- `strtoupper` + `converting false to array` in Mysql.php
	- `preg_match` where `$subject = null` in CakeRoute.php
	- `strtoupper` in DboSource.php
	- Check history for details ☝️

### 2021-12-20

- Fixed deprecation notices in PHP 8.1 for production code implementations:
	- `ArrayAccess`
	- `Countable`
	- `IteratorAggregate`
- PHP 8.0 requirement in composer.json
- **Warning:** Tests are not updated, Cake's tests rely on old version of PHPUnit so running them may show a lot of deprecations notices. Added issue to cover it: kamilwylegala/cakephp2-php8#7

### 2021-02-24

- Fixed ErrorHandler accordingly to PHP8 migration guide. Otherwise, error handler is logging too much and doesn't respect configured `error_reporting`.
