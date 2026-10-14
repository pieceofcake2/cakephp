# Upgrade Guide

## Prerequisites

Before migrating to this fork, ensure:
- Your application is running on PHP 7.4
- You're using CakePHP 2.10.24 (earlier versions are not supported)
- Your application uses Composer for dependency management

## From Original CakePHP 2.x

1. **Update to CakePHP 2.10.24 first**: If you're using an earlier version, update to `cakephp/cakephp:2.10.24` on PHP 7.4 first
2. **Ensure PHP 7.4 Compatibility**: Your application must be fully working on PHP 7.4 before migrating to PHP 8.x
3. **Update Composer**: Replace `cakephp/cakephp` with `pieceofcake2/cakephp` in your `composer.json`
4. **Upgrade PHP**: Update your PHP version to 8.0 or newer
5. **Test Thoroughly**: Run your application's test suite to ensure compatibility

## v2.12.4

### Integration Testing: `IntegrationTestTrait` and `EmailTrait` (backport) ([PR #51](https://github.com/pieceofcake2/cakephp/pull/51))

This backports the integration testing support of CakePHP 4.x/5.x: [`IntegrationTestTrait`](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/IntegrationTestTrait.php) with its constraints ([`Constraint/Response`](https://github.com/cakephp/cakephp/tree/5.x/src/TestSuite/Constraint/Response), [`Constraint/Session`](https://github.com/cakephp/cakephp/tree/5.x/src/TestSuite/Constraint/Session) and [`Constraint/View`](https://github.com/cakephp/cakephp/tree/5.x/src/TestSuite/Constraint/View)), and [`EmailTrait`](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/EmailTrait.php) with [`TestEmailTransport`](https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/TestEmailTransport.php) and its [`Constraint/Email`](https://github.com/cakephp/cakephp/tree/5.x/src/TestSuite/Constraint/Email) constraints. Method names and semantics follow CakePHP 5.x, so tests written with them need few changes when the application moves to CakePHP 5.

It is opt-in and nothing changes for existing tests: `ControllerTestCase` and `testAction()` keep working, and both can be used in the same test suite.

**What it is.** `IntegrationTestTrait` builds a request from a URL and data, dispatches it through the real `Dispatcher` (dispatcher filters, routes, components, the controller, the view and the layout, nothing mocked) and captures the `CakeResponse` instead of sending it. The `assert*()` methods then check the status code, the redirect, headers, the body, the rendered template and layout, the session, flash messages and cookies. `EmailTrait` makes every `CakeEmail` send with `TestEmailTransport`, which delivers nothing and records the emails for the `assertMail*()` methods.

Use the traits in any test case, usually `CakeTestCase` (for fixtures and the restoring of `Configure` after each test):

```php
class ArticlesControllerTest extends CakeTestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    public $fixtures = ['app.article'];

    public function testIndex(): void
    {
        $this->get('/articles');

        $this->assertResponseOk();
        $this->assertResponseContains('<h1>Articles</h1>');
    }
}
```

#### Compared with `ControllerTestCase::testAction()`

| | `ControllerTestCase::testAction()` | `IntegrationTestTrait` |
|-|-|-|
| Base class | `ControllerTestCase` | Any test case (`CakeTestCase`) |
| Controller | Mocked with `generate()` (`_stop()` and components can be mocked) | Real controller, built by the `Dispatcher` |
| Request | `testAction($url, ['method' => ..., 'data' => ...])` | `get()`, `post()`, `put()`, `patch()`, `delete()`, `head()`, `options()` |
| Dispatcher filters | Not run | Run (`Dispatcher.filters`) |
| `SecurityComponent` | Mock `_validatePost()` or disable it | `enableSecurityToken()` / `enableCsrfToken()` add valid tokens |
| Redirects | `exit` avoided by mocking `_stop()`; read `$this->headers['Location']` | `exit` is replaced automatically; `assertRedirect()` |
| Results | `$this->vars`, `$this->view`, `$this->contents`, `$this->headers`, `$this->result` | `viewVariable()`, `$this->_response`, `$this->_controller`, assertions |
| Exceptions | Thrown to the test | Rendered as the error page (like production), or thrown with `disableErrorHandlerMiddleware()` |
| Session, cookies | Set `$_SESSION` / `$_COOKIE` yourself | `session()`, `cookie()`, `cookieEncrypted()`, `assertSession()`, `assertCookie()` |

The same test before and after:

```php
// Before: ControllerTestCase
class ArticlesControllerTest extends ControllerTestCase
{
    public function testAdd(): void
    {
        $Articles = $this->generate('Articles', [
            'components' => ['Security' => ['_validatePost']],
        ]);
        $Articles->Security->expects($this->any())
            ->method('_validatePost')
            ->will($this->returnValue(true));

        $this->testAction('/articles/add', [
            'method' => 'post',
            'data' => ['Article' => ['title' => 'New article']],
        ]);

        $this->assertStringContainsString('/articles/view/', $this->headers['Location']);
    }
}
```

```php
// After: IntegrationTestTrait
class ArticlesControllerTest extends CakeTestCase
{
    use IntegrationTestTrait;

    public function testAdd(): void
    {
        $this->enableSecurityToken();

        $this->post('/articles/add', [
            'Article' => ['title' => 'New article'],
        ]);

        $this->assertRedirectContains('/articles/view/');
    }
}
```

Mocking is still possible where it is needed (for example a model method that calls an external service): `$this->getMockForModel('Article', ['callApi'])` registers the mock in `ClassRegistry`, and the dispatched controller uses it.

#### Full Example

```php
App::uses('EmailTrait', 'TestSuite');
App::uses('IntegrationTestTrait', 'TestSuite');

class ArticlesControllerTest extends CakeTestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    public $fixtures = ['app.article', 'app.user'];

    public function testIndex(): void
    {
        $this->get('/articles?page=2');

        $this->assertResponseOk();
        $this->assertResponseContains('<h1>Articles</h1>');
        $this->assertTemplate('Articles' . DS . 'index');
        $this->assertLayout('default');
        $this->assertContentType('html');
        $this->assertCount(10, $this->viewVariable('articles'));
    }

    public function testAddRequiresLogin(): void
    {
        $this->get(['controller' => 'articles', 'action' => 'add']);

        $this->assertRedirect(['controller' => 'users', 'action' => 'login']);
        $this->assertSession('/articles/add', 'Auth.redirect');
    }

    public function testAdd(): void
    {
        $this->session(['Auth' => ['User' => ['id' => 1, 'role' => 'admin']]]);
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->post('/articles/add', [
            'Article' => ['title' => 'New article', 'body' => 'Body'],
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/articles/view/');
        $this->assertFlashMessage('The article has been saved.');
        $this->assertFlashElement('Flash/success');
        $this->assertNoMailSent();
    }

    public function testAddWithUnlockedField(): void
    {
        $this->session(['Auth' => ['User' => ['id' => 1, 'role' => 'admin']]]);
        $this->enableSecurityToken();
        // Also unlocked in the controller: $this->Security->unlockedFields = ['Article.preview'];
        $this->setUnlockedFields(['Article.preview']);

        $this->post('/articles/add', [
            'Article' => ['title' => 'Draft', 'preview' => '1'],
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Preview: Draft');
    }

    public function testRememberMeCookie(): void
    {
        $this->cookieEncrypted('CakeCookie[remember]', ['id' => 1]);
        $this->get('/users/dashboard');
        $this->assertResponseOk();

        $this->post('/users/logout');
        $this->assertCookieNotSet('CakeCookie[remember]');
    }

    public function testLogin(): void
    {
        $this->enableSecurityToken();
        $this->post('/users/login', ['User' => ['username' => 'mark', 'password' => 'secret', 'remember' => '1']]);

        $this->assertRedirect('/');
        $this->assertSessionHasKey('Auth.User.id');
        $this->assertCookieEncrypted(['id' => 1], 'CakeCookie[remember]');
    }

    public function testApiView(): void
    {
        $this->requestAsJson();
        $this->get('/api/articles/1.json');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertHeader('Cache-Control', 'no-store');
        $this->assertSame('First article', json_decode((string)$this->_response->body(), true)['article']['title']);
    }

    public function testApiAdd(): void
    {
        $this->requestAsJson();
        $this->post('/api/articles.json', json_encode(['title' => 'From the API']));

        $this->assertResponseCode(201);
        $this->assertHeaderContains('Location', '/api/articles/');
    }

    public function testViewMissing(): void
    {
        $this->get('/articles/view/999');

        $this->assertResponseError();
        $this->assertResponseCode(404);
        $this->assertResponseContains('Article not found');
    }

    public function testViewMissingThrows(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(NotFoundException::class);

        $this->get('/articles/view/999');
    }

    public function testDownload(): void
    {
        $this->get('/articles/download/1');

        $this->assertFileResponse(WWW_ROOT . 'files' . DS . 'article-1.pdf');
        $this->assertHeaderContains('Content-Disposition', 'article-1.pdf');
    }

    public function testRegisterSendsWelcomeEmail(): void
    {
        $this->enableSecurityToken();
        $this->post('/users/register', ['User' => ['email' => 'alice@example.com', 'username' => 'alice']]);

        $this->assertRedirect(['controller' => 'users', 'action' => 'thanks']);
        $this->assertMailCount(1);
        $this->assertMailSentTo('alice@example.com');
        $this->assertMailSentFrom('noreply@example.com');
        $this->assertMailSubjectContains('Welcome');
        $this->assertMailContainsText('Hello alice');
        $this->assertMailContainsHtml('<strong>alice</strong>');
        $this->assertMailSentWith('both', 'emailFormat');
        $this->assertMailContainsAttachment('terms.pdf', ['mimetype' => 'application/pdf']);
    }
}
```

The classes are autoloaded (Composer classmap), so the `App::uses()` lines are optional.

#### API Reference

**Requests** (each stores the response for the assertions; `$url` is a string such as `/articles/view/1?page=2`, a full URL such as `https://example.com/articles` to set the host and HTTPS, or a routing array):

| Method | Description |
|-|-|
| `get($url)`, `delete($url)`, `head($url)`, `options($url)` | Dispatch a request without body data |
| `post($url, $data = [])`, `put($url, $data = [])`, `patch($url, $data = [])` | An array is the request data (`$this->request->data`; scalars are cast to strings like real form data, arrays that look like uploaded files are kept); a string is the raw body (`$this->request->input()`) |
| `resolveUrl($url)` | Returns the string URL that is requested (`Router::url()` for arrays) |

**Request setup** (cleared after each test):

| Method | Description |
|-|-|
| `configRequest(array $data)` | Merge request settings: `headers` (used for the next request only), `environment` (`$_SERVER` values, e.g. `HTTP_REFERER`, `REMOTE_ADDR`), `cookies`, `files` (`$_FILES` array), `input` (raw body) |
| `replaceRequest(array $data)` | Replace the request settings |
| `requestAsJson()` | Set the `Accept` and `Content-Type` headers to `application/json` |
| `session(array $data)` | Session data written before each following request (`$_SESSION` is kept between the requests of a test and cleared after it) |
| `cookie(string $name, string $value)` | Request cookie; `Name[key]` becomes `$_COOKIE['Name']['key']` |
| `cookieEncrypted(string $name, array\|string $value, string\|false $encrypt = 'cipher', ?string $key = null)` | Request cookie encrypted like `CookieComponent` (`CakeCookie[key]`; modes `cipher`, `aes`, `rijndael`, or `false`; key defaults to `Security.salt`) |
| `enableSecurityToken()` | Add the `_Token` fields `SecurityComponent` validates (`fields`, `unlocked`, `debug` in debug mode) and its CSRF token, computed from the posted data |
| `setUnlockedFields(array $fields)` | Fields excluded from the `SecurityComponent` hash (must match the fields unlocked in the controller or with `FormHelper::unlockField()`) |
| `enableCsrfToken()` | Add only the CSRF token (`_Token.key` in the data and in the session), for actions where `SecurityComponent::$validatePost` is disabled but `csrfCheck` is enabled |
| `enableRetainFlashMessages()` | Keep flash messages in the session after the view rendered them, so they can be asserted |
| `disableErrorHandlerMiddleware()` | Throw exceptions to the test instead of rendering the error page |

**Inspecting the result**: `viewVariable(string $name)` returns a view variable (or `null`); `$this->_response` (`CakeResponse`), `$this->_controller` (the dispatched controller, `null` if none was created), `$this->_exception` (the exception thrown while dispatching, also when it was rendered), `$this->_viewName` and `$this->_layoutName` (the rendered files).

**Assertions** (all accept a custom failure message as the last argument; when an exception was rendered, its message and trace are appended to failures):

| Area | Assertions |
|-|-|
| Status | `assertResponseOk()` (200-204), `assertResponseSuccess()` (200-308), `assertResponseError()` (400-429), `assertResponseFailure()` (500-505), `assertResponseCode(int $code)` |
| Redirect | `assertRedirect($url = null)`, `assertRedirectEquals($url = null)`, `assertRedirectContains(string $url)`, `assertRedirectNotContains(string $url)`, `assertRedirectBackToReferer(?int $code = null)`, `assertNoRedirect()` (URLs are compared as absolute URLs, routing arrays are accepted) |
| Headers | `assertHeader(string $header, string $content)`, `assertHeaderContains(string $header, string $content)`, `assertHeaderNotContains(string $header, string $content)`, `assertContentType(string $type)` (`json` or `application/json`) |
| Body | `assertResponseContains(string $content, string $message = '', bool $ignoreCase = false)`, `assertResponseNotContains(...)`, `assertResponseEquals($content)`, `assertResponseNotEquals($content)`, `assertResponseRegExp(string $pattern)`, `assertResponseNotRegExp(string $pattern)`, `assertResponseEmpty()`, `assertResponseNotEmpty()` |
| View | `assertTemplate(string $content)`, `assertLayout(string $content)` (substring of the rendered file path) |
| Session | `assertSession($expected, string $path)`, `assertSessionHasKey(string $path)`, `assertSessionNotHasKey(string $path)` (`Hash` paths) |
| Flash | `assertFlashMessage(string $expected, string $key = 'flash')`, `assertFlashMessageAt(int $at, ...)`, `assertFlashMessageContains(string $expected, string $key = 'flash', string $message = '', bool $ignoreCase = false)`, `assertFlashMessageContainsAt(int $at, ...)`, `assertFlashElement(string $expected, string $key = 'flash')`, `assertFlashElementAt(int $at, ...)` |
| Cookies | `assertCookie($expected, string $name)`, `assertCookieIsSet(string $name)`, `assertCookieNotSet(string $name)`, `assertCookieEncrypted($expected, string $name, string $encrypt = 'cipher', ?string $key = null)` |
| Files | `assertFileResponse(string $path)` (`CakeResponse::file()`) |
| Email (`EmailTrait`) | `assertMailCount(int $count)`, `assertNoMailSent()`, `assertMailSentTo(string $address)`, `assertMailSentFrom(array\|string $address)`, `assertMailSentWith(string $expected, string $parameter)`, `assertMailContains(string $contents)`, `assertMailContainsText(string $contents)`, `assertMailContainsHtml(string $contents)`, `assertMailSubjectContains(string $contents)`, `assertMailContainsAttachment(string $filename, array $file = [])`, and the `...At(int $at, ...)` variants `assertMailSentToAt()`, `assertMailSentFromAt()`, `assertMailSentWithAt()`, `assertMailContainsAt()`, `assertMailContainsTextAt()`, `assertMailContainsHtmlAt()`, `assertMailSubjectContainsAt()` (0-based index) |

`TestEmailTransport` can also be used without `EmailTrait`: `TestEmailTransport::replaceAllTransports()` / `restoreTransports()`, `getMessages()` (the sent `CakeEmail` objects) and `clearMessages()`, or `'transport' => 'TestEmail'` in a configuration. Both use the new `CakeEmail::overrideTransport(?string $name)`, which makes every `CakeEmail` instance (also those created earlier) use the given transport until it is called with `null`.

#### Differences from CakePHP 5.x

- **No middleware or `Application` class**: requests go through `Dispatcher` (as `IntegrationTestDispatcher`), so dispatcher filters run. `useHttpServer()`, `ContainerStubTrait` and `TestSession` are not available.
- **`exit` is replaced**: CakePHP 2 sends the response and calls `exit` in `Controller::redirect()`, `AuthComponent`, `RequestHandlerComponent` (Ajax redirects), `AssetDispatcher` and redirect routes (`Router::redirect()`). The test response (`IntegrationTestResponse`) throws `IntegrationTestStop` instead of sending, which ends the request at the same point (code after the `redirect()` call and `afterFilter()` do not run), and the response is captured.
- **Superglobals**: `CakeRequest`, `CookieComponent` and others read `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE` and `$_SERVER`, so they are set while a request is dispatched and restored afterwards. `$_SESSION` is shared by the requests of a test and cleared after each test that dispatched a request.
- **SecurityComponent instead of FormProtection/CSRF middleware**: `enableSecurityToken()` also adds the CSRF token (`SecurityComponent::$csrfCheck` is enabled by default). `enableCsrfToken()` takes no `$cookieName` (CakePHP 2 has no cookie based CSRF tokens). Fields unlocked in the controller must also be passed to `setUnlockedFields()`.
- **Errors**: exceptions are rendered with `Exception.renderer` (`ExceptionRenderer` by default). `disableErrorHandlerMiddleware()` keeps its CakePHP 5 name and throws them instead. PHPUnit exceptions (failed assertions in the application) are always thrown.
- **Flash messages** are read from the `Message` session key (CakePHP 5: `Flash`). Element names are those stored by CakePHP 2 (`Flash/success` for `$this->Flash->success()`, `default` for `SessionComponent::setFlash()`). An `$at` of `0` checks the first message (CakePHP 5 checks all of them).
- **Cookies** use the `CookieComponent` format: values are in `CakeCookie[...]` cookies, arrays are JSON encoded, and `cookieEncrypted()` / `assertCookieEncrypted()` default to the `cipher` mode of `CookieComponent` (CakePHP 5: `aes`). Pass the `type` you configured for `CookieComponent` (e.g. `'aes'`).
- **Headers** are matched case-insensitively and multiple values are joined with `, `. `Content-Type` is always available (with the charset that `CakeResponse::send()` would add).
- **Emails** are `CakeEmail` objects, read with the `CakeEmail` getters (`assertMailSentWith('both', 'emailFormat')`). Subjects are MIME-decoded before `assertMailSubjectContains()`. `assertMailContainsAttachment()` always requires the file name to match.
- **Not backported**: `assertRedirectBack()` (CakePHP 2 has no `url` request setting), `assertFlashMessage*()` with a `Flash` session key, the `$cookieName` of `enableCsrfToken()`, `MiddlewareDispatcher`.
- **Class names**: CakePHP 2 has no namespaces, so the constraint classes have a `Constraint` suffix (`StatusOkConstraint`, `MailCountConstraint`, ...).
- **Uploaded files** passed in the data or with `configRequest(['files' => ...])` are not real uploads: `is_uploaded_file()` and `move_uploaded_file()` return `false` for them.

#### Modern and Legacy Layouts

Both layouts work the same way; only the test location differs:

- **Modern layout** (`pieceofcake2/app`): put the tests in `tests/TestCase/Controller/` and run `vendor/bin/phpunit` (or `bin/cake test app Controller/ArticlesController`). Fixtures are `app.article` (`tests/Fixture/ArticleFixture.php`).
- **Legacy layout** (`app/` directory): put the tests in `app/Test/Case/Controller/` and run `app/Console/cake test app Controller/ArticlesController`. Fixtures are `app.article` (`app/Test/Fixture/ArticleFixture.php`).

Requests use the routes of the application (`config/routes.php` or `app/Config/routes.php`) and its `core.php` settings (`Security.salt`, `Session`, `Exception.renderer`). URLs are dispatched as if the application were installed at the document root (`App.base` is empty unless configured).

## v2.12.3

### Xml::build() `readFile` Option Also Blocks https URLs ([PR #33](https://github.com/pieceofcake2/cakephp/pull/33))

- **Security fix (SSRF)**: `Xml::build($input, ['readFile' => false])` fetched `https://` URLs because of a missing pair of parentheses (inherited from the original CakePHP 2.x). With `'readFile' => false`, neither `http://` nor `https://` URLs are fetched now; an `XmlException` is thrown instead.

**Migration:**
- No action required if you pass `'readFile' => false` to disable remote loading; this now works as documented.
- If you relied on `Xml::build()` fetching an `https://` URL with `'readFile' => false`, either remove the option (default is `true`) or fetch the document yourself (e.g. with `HttpSocket`) and pass the XML string.

### CookieComponent Honors the `'type'` Setting ([PR #34](https://github.com/pieceofcake2/cakephp/pull/34))

- `CookieComponent` now applies the `'type'` setting given in `$components` (e.g. `'Cookie' => ['type' => 'aes']`). Previously the setting was silently ignored and cookies were encrypted with the insecure default `'cipher'` type.

**Migration:**
- If your controllers set `'type'` in `$components`, cookies will now be written with that type, and cookies already written with `'cipher'` can no longer be decrypted. Plan the switch like any other encryption type change.
- When using `'aes'`, set `Configure::write('Security.useOpenSsl', true);` unless the mcrypt extension is installed (it is not bundled with PHP 7.2+ and is only available from PECL).
- An invalid `'type'` setting now triggers an `E_USER_WARNING` and falls back to `'cipher'`, as `CookieComponent::type()` does.

### Test Case Listing Supports the `tests/TestCase` Layout ([PR #35](https://github.com/pieceofcake2/cakephp/pull/35))

- `CakeTestLoader::generateTestList()` now finds test cases in `tests/TestCase` for the app and plugins, falling back to the legacy `Test/Case` layout. Previously the list was empty for the modern layout, so `cake test app` / `cake test <Plugin>` without a test case name and the web test runner showed no test cases.

**Migration:**
- No action required.

### SQL Server Driver Sets `PDO::ATTR_ERRMODE` by Default ([PR #36](https://github.com/pieceofcake2/cakephp/pull/36))

- `Sqlserver::connect()` now merges `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` into the `'flags'` setting as the other drivers do. Because of an operator precedence bug, the default was never applied.

**Migration:**
- No action required. PDO already uses `PDO::ERRMODE_EXCEPTION` by default on PHP 8, and a `PDO::ATTR_ERRMODE` value given in `'flags'` still takes precedence.

### ServerShell Default Port Changed to 8765 ([PR #37](https://github.com/pieceofcake2/cakephp/pull/37))

- `cake server` now listens on port `8765` by default (as in CakePHP 3 and later) instead of `80`, which requires root privileges on most systems.
- `ServerShell::DEFAULT_PORT` is now `8765`.

**Migration:**
- To keep using port 80, pass it explicitly: `bin/cake server -p 80`.

## Breaking Changes

### Post-Installation Steps

#### For Legacy Directory Structure (`app/` directory)

After installation, install the Bake plugin and copy dispatcher files:

```bash
# Install Bake plugin
composer require --dev pieceofcake2/bake

# Copy web dispatcher files
cp plugins/Bake/Console/Templates/skel/webroot/index.php app/webroot/index.php
cp plugins/Bake/Console/Templates/skel/webroot/test.php app/webroot/test.php

# Copy console dispatcher
cp plugins/Bake/Console/Templates/skel/Console/cake app/Console/cake
chmod +x app/Console/cake
```

#### For Modern Directory Structure (`src/` directory)

The following files need to be updated:
- `config/define.php`
- `bin/cake`
- `webroot/index.php`
- `webroot/test.php`

See [`pieceofcake2/app`](https://github.com/pieceofcake2/app) for the modern application skeleton compatible with both CakePHP 2.x and 5.x.

### Directory Structure Modernization ([PR #21](https://github.com/pieceofcake2/cakephp/pull/21))

- **Directory layout has been restructured to modern standards**
- `lib/Cake/` → `src/Cake/` (framework source code)
- `lib/Cake/Test/Case/` → `tests/TestCase/` (framework tests)
- `lib/Cake/Test/Fixture/` → `tests/Fixture/` (test fixtures)
- `lib/Cake/Console/cake` → `bin/cake` (console executable)
- `lib/Cake/Config/` → `config/` (framework configuration files at root level)
- `lib/Cake/View/` and `lib/Cake/Templates/` → `templates/` (framework default templates at root level)
- All configuration files and paths updated accordingly
- Added `CORE_ROOT` constant to reference framework root directory

**Modern Test Directory Support:**
- Applications and plugins can now use modern `tests/TestCase/` and `tests/Fixture/` directory structure
- Test suite automatically detects both `Test/Case/` (traditional) and `tests/TestCase/` (modern) directories
- Fixture loader supports both `Test/Fixture/` and `tests/Fixture/` locations
- Enables gradual migration to modern directory structure for application tests
- No breaking changes - both directory structures work simultaneously

**Migration:**
- No action required for applications using this framework as a dependency
- `bin/cake` (or `vendor/bin/cake` when installed via Composer) now intelligently detects your project structure
- Automatically works with both modern `src/` and traditional `app/` directory layouts
- Optionally migrate your application tests from `app/Test/Case/` to `app/tests/TestCase/` at your own pace

**Enhanced Console Executable:**
- `bin/cake` now automatically detects project root from Composer's autoloader
- No manual configuration needed - works out of the box with any project structure
- Supports modern (`src/`) and traditional (`app/`) application directories

### Composer Autoloading Migration ([PR #22](https://github.com/pieceofcake2/cakephp/pull/22))

The framework has migrated from include-path to Composer classmap autoloading. This change simplifies class loading and improves compatibility with modern PHP tooling.

**What changed:**
- Removed `App::uses()` declarations from CakePHP framework classes (798 occurrences removed)
- CakePHP framework classes are now automatically loaded via Composer's classmap autoloader
- Application classes can be automatically loaded by configuring Composer classmap autoloading (see Migration section below)
- CakePHP framework classes that extend application classes use `App::uses()`:
  - `App::uses('AppController', 'Controller')` - for CakeErrorController
  - `App::uses('AppModel', 'Model')` - for I18nModel, AcoAction, Permission
  - `App::uses('AppShell', 'Console/Command')` - for all core shell commands
  - `App::uses('AppHelper', 'View/Helper')` - for all core helpers

**Migration:**

**For new projects or modern directory structure (`src/`):**
- Recommended: Update your `composer.json` to include classmap autoloading (similar to [pieceofcake2/app](https://github.com/pieceofcake2/app)):
  ```json
  {
    "autoload": {
      "classmap": ["src/"]
    }
  }
  ```

**For legacy projects (`app/` directory):**
- No action required - existing applications continue to work without changes
- The framework's `App::uses()` calls for application classes ensure backward compatibility

**Plugin classes:**
- Plugins that don't support Composer autoloading require `App::uses()` to load their classes (same as before):
  ```php
  App::uses('MyPluginHelper', 'MyPlugin.View/Helper');
  ```
- If a plugin's documentation indicates autoloading support, `App::uses()` may not be needed

**Custom Autoloader Override (optional):**
If you need to override autoloading behavior for specific classes, register your custom autoloader with the prepend flag to give it priority over Composer's autoloader:

   ```php
   // Example: Custom classmap-based autoloader
   $customClassMap = [
       'MyCustomClass' => '/path/to/MyCustomClass.php',
       'AnotherClass' => '/path/to/AnotherClass.php',
   ];

   spl_autoload_register(function($class) use ($customClassMap) {
       if (isset($customClassMap[$class])) {
           require $customClassMap[$class];
           return true;
       }
       return false;
   }, true, true); // throw=true, prepend=true (priority over Composer)
   ```

   **Parameters explained:**
   - First `true`: Throw exception if autoloader registration fails
   - Second `true`: Prepend to autoloader queue (gives priority over Composer's autoloader)

**Duplicate Class Name Warnings:**

When Composer encounters duplicate class names in the classmap, it will issue a warning during `composer install` or `composer dump-autoload`:

```
Warning: Ambiguous class resolution, "AppController" was found in both "/path/to/vendor/pieceofcake2/app/src/Controller/AppController.php" and "/path/to/your-app/src/Controller/AppController.php", the first will be used.
```

To prevent these warnings and ensure the correct class is loaded, use `exclude-from-classmap` in your `composer.json`:

```json
{
  "autoload": {
    "classmap": ["src/"],
    "exclude-from-classmap": [
      "vendor/pieceofcake2/app/src/Controller/AppController.php",
      "vendor/pieceofcake2/app/src/Model/AppModel.php"
    ]
  },
  "autoload-dev": {
    ...
  }
}
```

This tells Composer to ignore specific files when building the classmap, preventing conflicts with your application's base classes.

### Bake Plugin Extraction ([PR #17](https://github.com/pieceofcake2/cakephp/pull/17))

- **Bake functionality has been extracted to a separate plugin** ([pieceofcake2/bake](https://github.com/pieceofcake2/bake))
- `BakeShell` and all Bake tasks removed from core (`BakeTask`, `ModelTask`, `ControllerTask`, `ViewTask`, `FixtureTask`, `TestTask`, `TemplateTask`, `ProjectTask`, `PluginTask`, `DbConfigTask`, `CommandTask`)
- Application skeleton templates moved from `lib/Cake/Console/Templates/skel/` to Bake plugin
- Dispatcher files (`index.php`, `test.php`, `cake`) now located in Bake plugin
- Console bake commands no longer available without installing the Bake plugin

**Migration:**
1. Install the Bake plugin separately: `composer require --dev pieceofcake2/bake`
2. Load the plugin in your `app/Config/bootstrap.php`:
   ```php
   CakePlugin::load('Bake');
   ```
3. Copy dispatcher files from Bake plugin if needed (for new projects)

**Why this change:**
- Allows independent development and versioning of Bake functionality
- Reduces core framework size
- Most production applications don't need Bake in production

### Legacy Upgrade Plugin Extraction ([PR #19](https://github.com/pieceofcake2/cakephp/pull/19))

- **Legacy Upgrade functionality has been extracted to a separate plugin** ([pieceofcake2/upgrade](https://github.com/pieceofcake2/upgrade))
- `UpgradeShell` removed from core
- Legacy upgrade commands (1.3 → 2.0) no longer available without installing the Upgrade plugin

**Migration:**
> [!NOTE]
> The Upgrade plugin is only relevant for upgrading legacy CakePHP 1.3 applications to 2.0, which is extremely rare in 2025. Most users will never need this plugin.

If you need to upgrade a CakePHP 1.3 application to 2.0, install the Upgrade plugin separately:
```bash
composer require --dev pieceofcake2/upgrade
```

Then load the plugin in your `app/Config/bootstrap.php`:
```php
CakePlugin::load('Upgrade');
```

**Why this change:**
- Reduces core framework size
- The Upgrade plugin is only useful for upgrading from CakePHP 1.3 to 2.0
- Almost no one needs this functionality in 2025

### Composer-Only Installation Required ([PR #14](https://github.com/pieceofcake2/cakephp/pull/14))

- **Non-Composer installation is no longer supported**
- Dispatcher files (`app/webroot/index.php`, `app/webroot/test.php`, `app/Console/cake`) require Composer autoload
- Removed manual `CAKE_CORE_INCLUDE_PATH` detection from dispatcher files
- Removed `include_path` manipulation logic
- Removed legacy `app/Vendor/cakephp/cakephp` path detection
- Removed `app/Console/cake.bat` and `app/Console/cake.php` (Windows batch file and PHP wrapper no longer needed)

**Migration:**
1. Ensure you're using Composer for dependency management
2. Install the Bake plugin: `composer require --dev pieceofcake2/bake`
3. Copy updated dispatcher files from `plugins/Bake/Console/Templates/skel/` to your application:
   ```bash
   cp plugins/Bake/Console/Templates/skel/webroot/index.php app/webroot/index.php
   cp plugins/Bake/Console/Templates/skel/webroot/test.php app/webroot/test.php
   cp plugins/Bake/Console/Templates/skel/Console/cake app/Console/cake
   ```
4. Remove old dispatcher files if present:
   ```bash
   rm -f app/Console/cake.bat app/Console/cake.php
   ```
5. Run `composer install` to ensure all dependencies are properly loaded

### Cache Engines Removed ([PR #4](https://github.com/pieceofcake2/cakephp/pull/4))

- **Xcache** support has been removed (not compatible with PHP 7.0+)
- **Wincache** support has been removed (not actively maintained for PHP 8.x)

**Migration:**
- If using these cache engines, migrate to Redis, Memcached, or APCu

### Database Driver Methods Added ([PR #3](https://github.com/pieceofcake2/cakephp/pull/3))

- New methods added to database drivers (may cause issues if you have custom driver implementations)

**MySQL Driver (Mysql.php):**
- `getVersion(): string` - Returns MySQL/MariaDB/Aurora MySQL version
- `getServerType(): string` - Returns 'MySQL', 'MariaDB', or 'Aurora MySQL'
- `utf8mb4Supported(): bool` - Checks utf8mb4 character set support
- `integerDisplayWidthDeprecated(): bool` - Checks if integer display width is deprecated (MySQL 8.0.17+)

**PostgreSQL Driver (Postgres.php):**
- `getVersion(): string` - Returns PostgreSQL version

**Migration:**
- If you have custom database drivers extending these classes, implement these methods

### Database Charset Configuration Changes ([PR #11](https://github.com/pieceofcake2/cakephp/pull/11))

- Character set configuration moved from `SET NAMES` to DSN connection options
- **MySQL**: Charset now in DSN (e.g., `mysql:...;charset=utf8`)
- **PostgreSQL**: Client encoding in DSN options (e.g., `pgsql:...;options='--client_encoding=UTF8'`)
- **PostgreSQL**: `sslmode` parameter is now optional in DSN

**Migration:**
- No action required - changes are backward compatible
- `setEncoding()` methods still work for runtime changes
- More efficient connection setup with charset in DSN

### SQL Server Driver Updates ([PR #9](https://github.com/pieceofcake2/cakephp/pull/9))

#### Configuration Format

- **Schema-based configuration**: Each connection can use a schema (`schema`, a string; defaults to `dbo`), so several connections can share one database instead of using multiple databases
  ```php
  // Old approach (still works)
  public $test2 = [
      'datasource' => 'Database/Sqlserver',
      'database' => 'cakephp_test2',
      // ...
  ];

  // New recommended approach: same database, one schema per connection
  public $test = [
      'datasource' => 'Database/Sqlserver',
      'database' => 'cakephp_test',
      'schema' => 'dbo', // default when omitted
      // ...
  ];
  public $test2 = [
      'datasource' => 'Database/Sqlserver',
      'database' => 'cakephp_test',
      'schema' => 'test2',
      // ...
  ];
  ```

> [!NOTE]
> `schema` must be a string. The array form used in this repository's `tests/config/database.php` is resolved to a string per connection by that file's constructor; the driver itself does not accept an array.

- **Connection options**: SSL/TLS options now in `options` array
  ```php
  'options' => [
      'TrustServerCertificate' => 'yes',
      'Encrypt' => 'no',
  ]
  ```

- **Port configuration**: Specify port separately (automatically appended to server)

#### Method Signature Changes

- `describe($model): array` - Now has explicit return type
- `insertMulti()` - Now returns `bool` instead of `void`

**Migration:**
- Optionally set `schema` per connection to share one database between connections
- Move SSL/TLS options to `options` array if using inline DSN
- If extending Sqlserver class, update method signatures to match

### Mail Function Updates ([PR #10](https://github.com/pieceofcake2/cakephp/pull/10))

- `MailTransport::_mail()` method signature changed with strict types
- Old: `protected function _mail($to, $subject, $message, $headers, $params = null)`
- New: `protected function _mail(string $to, string $subject, string $message, array|string $headers = [], string $params = ''): void`

**Migration:**
- No action required unless you've extended `MailTransport` class
- If extending, update method signature to match strict types

### CSRF Token Security Enhancement ([PR #5](https://github.com/pieceofcake2/cakephp/pull/5))

- New CSRF tokens use HMAC-SHA1 signatures (prevents CVE-2020-15400)
- Token format changed to base64-encoded (16-byte value + 20-byte HMAC)

**Migration:**
- **No action required** - automatic and backward compatible
- Existing tokens continue to work
- New tokens generated with enhanced security

### strftime() Replacement

- `strftime()` deprecated in PHP 8.1, removed in PHP 8.2
- Now uses `IntlDateFormatter` via Symfony's ICU Polyfill
- Fallback to `PHP81_BC\strftime` for compatibility

**Migration:**
- Most date formatting works identically
- Edge cases may produce slightly different output
- Test date formatting in your application

### Development Tools Updates

#### PHP CodeSniffer ([PR #8](https://github.com/pieceofcake2/cakephp/pull/8))

- Updated from 1.0.0 to 5.3
- Applied automatic formatting fixes

**Migration:**
- Development-time change only
- Update `phpcs.xml` if you have custom coding standards

#### PHPUnit Compatibility

- Framework tests migrated to PHPUnit 9.6
- All deprecated PHPUnit features fixed

**Migration:**
- Update your tests if using deprecated PHPUnit features

### PHP 8 Syntax Modernization ([PR #7](https://github.com/pieceofcake2/cakephp/pull/7))

- Codebase modernized to PHP 8 syntax

**Changes:**
- `array()` → `[]`
- `get_class()` → `::class`
- `list()` → `[]` for array destructuring
- `dirname(__FILE__)` → `__DIR__`
- Added null coalescing operators
- Native `str_contains()`, `str_starts_with()`, `str_ends_with()`

**Migration:**
- **No action required** - syntax changes only, no functionality changes

### CookieComponent 'cipher' Type - Insecure and Should Be Replaced

- The default `CookieComponent` encryption type `'cipher'` is horribly insecure

> [!WARNING]
> As stated in the CakePHP source code comments:
> > "Cipher is horribly insecure and only the default because of backwards compatibility. In new applications you should always change this to 'aes' or 'rijndael'."

**Why 'cipher' is insecure:**
- Uses `Security::cipher()` with XOR encryption (cryptographically weak)
- Uses `Security.cipherSeed` with undefined float-to-int casting behavior
- The seed value: `mt_srand((int)(float)Configure::read('Security.cipherSeed'))` has no guaranteed consistency
- Not suitable for protecting sensitive data

**Migration:**
```php
// OLD (insecure - DO NOT USE)
public $components = [
    'Cookie' => [
        'type' => 'cipher'  // Default, horribly insecure
    ]
];

// NEW (recommended)
public $components = [
    'Cookie' => [
        'type' => 'aes'  // or 'rijndael' (requires the mcrypt extension)
    ]
];

// Or dynamically in your controller
$this->Cookie->type('aes');
```

Without the mcrypt extension (not bundled with PHP 7.2+), enable OpenSSL for `'aes'` in `config/core.php`:

```php
Configure::write('Security.useOpenSsl', true);
```

> [!NOTE]
> The `'type'` setting in `$components` is honored since the fix in [PR #34](https://github.com/pieceofcake2/cakephp/pull/34). In v2.12.2 and earlier it was silently ignored, so use `$this->Cookie->type('aes')` (e.g. in `beforeFilter()`) there.
>
> `'aes'` uses OpenSSL only when `Security.useOpenSsl` is `true`; otherwise it calls mcrypt functions, which fail unless the mcrypt extension is installed from PECL. `'rijndael'` always requires the mcrypt extension.

> [!IMPORTANT]
> Changing encryption type will invalidate existing cookies. Plan your migration strategy accordingly (e.g., support both types during transition period).
