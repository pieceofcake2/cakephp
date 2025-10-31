<?php
/**
 * CakePHP Legacy Class Autoloader
 *
 * Provides backward compatibility for non-namespaced class names.
 * Maps legacy class names (e.g., "AppController") to their namespaced equivalents (e.g., "\Cake\Controller\AppController").
 *
 * This autoloader is prepended to the autoloader stack to ensure legacy class names
 * continue to work while the codebase migrates to PSR-4 namespaces compatible with CakePHP 5.
 */

namespace Cake;

use Cake\Core\Configure;

/**
 * Legacy class loader
 */
class LegacyClassLoader
{
    /**
     * Legacy class name to namespace mapping
     *
     * @var array
     */
    private static array $classMap = [
        'AbstractPasswordHasher' => 'Cake\Controller\Component\Auth\AbstractPasswordHasher',
        'AbstractTransport' => 'Cake\Network\Email\AbstractTransport',
        'AclBehavior' => 'Cake\Model\Behavior\AclBehavior',
        'AclComponent' => 'Cake\Controller\Component\AclComponent',
        'AclInterface' => 'Cake\Controller\Component\Acl\AclInterface',
        'AclNode' => 'Cake\Model\AclNode',
        'AclShell' => 'Cake\Console\Command\AclShell',
        'Aco' => 'Cake\Model\Aco',
        'AcoAction' => 'Cake\Model\AcoAction',
        'ActionsAuthorize' => 'Cake\Controller\Component\Auth\ActionsAuthorize',
        'ApcEngine' => 'Cake\Cache\Engine\ApcEngine',
        'ApiShell' => 'Cake\Console\Command\ApiShell',
        'App' => 'Cake\Core\App',
        'Aro' => 'Cake\Model\Aro',
        'AssetDispatcher' => 'Cake\Routing\Filter\AssetDispatcher',
        'AuthComponent' => 'Cake\Controller\Component\AuthComponent',
        'BaseAuthenticate' => 'Cake\Controller\Component\Auth\BaseAuthenticate',
        'BaseAuthorize' => 'Cake\Controller\Component\Auth\BaseAuthorize',
        'BaseCoverageReport' => 'Cake\TestSuite\Coverage\BaseCoverageReport',
        'BaseLog' => 'Cake\Log\Engine\BaseLog',
        'BaseShellHelper' => 'Cake\Console\Helper\BaseShellHelper',
        'BasicAuthenticate' => 'Cake\Controller\Component\Auth\BasicAuthenticate',
        'BasicAuthentication' => 'Cake\Network\Http\BasicAuthentication',
        'BehaviorCollection' => 'Cake\Model\BehaviorCollection',
        'BlowfishAuthenticate' => 'Cake\Controller\Component\Auth\BlowfishAuthenticate',
        'BlowfishPasswordHasher' => 'Cake\Controller\Component\Auth\BlowfishPasswordHasher',
        'Cache' => 'Cake\Cache\Cache',
        'CacheDispatcher' => 'Cake\Routing\Filter\CacheDispatcher',
        'CacheEngine' => 'Cake\Cache\CacheEngine',
        'CacheHelper' => 'Cake\View\Helper\CacheHelper',
        'CacheSession' => 'Cake\Model\Datasource\Session\CacheSession',
        'CakeBaseReporter' => 'Cake\TestSuite\Reporter\CakeBaseReporter',
        'CakeEmail' => 'Cake\Network\Email\CakeEmail',
        'CakeErrorController' => 'Cake\Controller\CakeErrorController',
        'CakeEvent' => 'Cake\Event\CakeEvent',
        'CakeEventListener' => 'Cake\Event\CakeEventListener',
        'CakeEventManager' => 'Cake\Event\CakeEventManager',
        'CakeFixtureInjector' => 'Cake\TestSuite\Fixture\CakeFixtureInjector',
        'CakeFixtureManager' => 'Cake\TestSuite\Fixture\CakeFixtureManager',
        'CakeHtmlReporter' => 'Cake\TestSuite\Reporter\CakeHtmlReporter',
        'CakeLog' => 'Cake\Log\CakeLog',
        'CakeLogInterface' => 'Cake\Log\CakeLogInterface',
        'CakeNumber' => 'Cake\Utility\CakeNumber',
        'CakeObject' => 'Cake\Core\CakeObject',
        'CakePlugin' => 'Cake\Core\CakePlugin',
        'CakeRequest' => 'Cake\Network\CakeRequest',
        'CakeResponse' => 'Cake\Network\CakeResponse',
        'CakeRoute' => 'Cake\Routing\Route\CakeRoute',
        'CakeSchema' => 'Cake\Model\CakeSchema',
        'CakeSession' => 'Cake\Model\Datasource\CakeSession',
        'CakeSessionHandlerInterface' => 'Cake\Model\Datasource\Session\CakeSessionHandlerInterface',
        'CakeSocket' => 'Cake\Network\CakeSocket',
        'CakeTestCase' => 'Cake\TestSuite\CakeTestCase',
        'CakeTestFixture' => 'Cake\TestSuite\Fixture\CakeTestFixture',
        'CakeTestLoader' => 'Cake\TestSuite\CakeTestLoader',
        'CakeTestModel' => 'Cake\TestSuite\Fixture\CakeTestModel',
        'CakeTestSuite' => 'Cake\TestSuite\CakeTestSuite',
        'CakeTestSuiteCommand' => 'Cake\TestSuite\CakeTestSuiteCommand',
        'CakeTestSuiteDispatcher' => 'Cake\TestSuite\CakeTestSuiteDispatcher',
        'CakeText' => 'Cake\Utility\CakeText',
        'CakeTextReporter' => 'Cake\TestSuite\Reporter\CakeTextReporter',
        'CakeTime' => 'Cake\Utility\CakeTime',
        'CakeValidationRule' => 'Cake\Model\Validator\CakeValidationRule',
        'CakeValidationSet' => 'Cake\Model\Validator\CakeValidationSet',
        'ClassRegistry' => 'Cake\Utility\ClassRegistry',
        'CommandListShell' => 'Cake\Console\Command\CommandListShell',
        'CommandTask' => 'Cake\Console\Command\Task\CommandTask',
        'CompletionShell' => 'Cake\Console\Command\CompletionShell',
        'Component' => 'Cake\Controller\Component',
        'ComponentCollection' => 'Cake\Controller\ComponentCollection',
        'ConfigReaderInterface' => 'Cake\Configure\ConfigReaderInterface',
        'Configure' => 'Cake\Core\Configure',
        'ConnectionManager' => 'Cake\Model\ConnectionManager',
        'ConsoleErrorHandler' => 'Cake\Console\ConsoleErrorHandler',
        'ConsoleInput' => 'Cake\Console\ConsoleInput',
        'ConsoleInputArgument' => 'Cake\Console\ConsoleInputArgument',
        'ConsoleInputOption' => 'Cake\Console\ConsoleInputOption',
        'ConsoleInputSubcommand' => 'Cake\Console\ConsoleInputSubcommand',
        'ConsoleLog' => 'Cake\Log\Engine\ConsoleLog',
        'ConsoleOptionParser' => 'Cake\Console\ConsoleOptionParser',
        'ConsoleOutput' => 'Cake\Console\ConsoleOutput',
        'ConsoleOutputStub' => 'Cake\TestSuite\Stub\ConsoleOutputStub',
        'ConsoleShell' => 'Cake\Console\Command\ConsoleShell',
        'ContainableBehavior' => 'Cake\Model\Behavior\ContainableBehavior',
        'Controller' => 'Cake\Controller\Controller',
        'ControllerAuthorize' => 'Cake\Controller\Component\Auth\ControllerAuthorize',
        'ControllerTestCase' => 'Cake\TestSuite\ControllerTestCase',
        'ControllerTestDispatcher' => 'Cake\TestSuite\ControllerTestDispatcher',
        'CookieComponent' => 'Cake\Controller\Component\CookieComponent',
        'CrudAuthorize' => 'Cake\Controller\Component\Auth\CrudAuthorize',
        'DataSource' => 'Cake\Model\Datasource\DataSource',
        'DatabaseSession' => 'Cake\Model\Datasource\Session\DatabaseSession',
        'DbAcl' => 'Cake\Controller\Component\Acl\DbAcl',
        'DboSource' => 'Cake\Model\Datasource\DboSource',
        'DebugTransport' => 'Cake\Network\Email\DebugTransport',
        'Debugger' => 'Cake\Utility\Debugger',
        'DigestAuthenticate' => 'Cake\Controller\Component\Auth\DigestAuthenticate',
        'DigestAuthentication' => 'Cake\Network\Http\DigestAuthentication',
        'Dispatcher' => 'Cake\Routing\Dispatcher',
        'DispatcherFilter' => 'Cake\Routing\DispatcherFilter',
        'EmailComponent' => 'Cake\Controller\Component\EmailComponent',
        'ErrorHandler' => 'Cake\Error\ErrorHandler',
        'ExceptionRenderer' => 'Cake\Error\ExceptionRenderer',
        'ExtractTask' => 'Cake\Console\Command\Task\ExtractTask',
        'File' => 'Cake\Utility\File',
        'FileEngine' => 'Cake\Cache\Engine\FileEngine',
        'FileLog' => 'Cake\Log\Engine\FileLog',
        'FlashComponent' => 'Cake\Controller\Component\FlashComponent',
        'FlashHelper' => 'Cake\View\Helper\FlashHelper',
        'Folder' => 'Cake\Utility\Folder',
        'FormAuthenticate' => 'Cake\Controller\Component\Auth\FormAuthenticate',
        'FormHelper' => 'Cake\View\Helper\FormHelper',
        'Hash' => 'Cake\Utility\Hash',
        'HelpFormatter' => 'Cake\Console\HelpFormatter',
        'Helper' => 'Cake\View\Helper',
        'HelperCollection' => 'Cake\View\HelperCollection',
        'HtmlCoverageReport' => 'Cake\TestSuite\Coverage\HtmlCoverageReport',
        'HtmlHelper' => 'Cake\View\Helper\HtmlHelper',
        'HttpResponse' => 'Cake\Network\Http\HttpResponse',
        'HttpSocket' => 'Cake\Network\Http\HttpSocket',
        'HttpSocketResponse' => 'Cake\Network\Http\HttpSocketResponse',
        'I18n' => 'Cake\I18n\I18n',
        'I18nModel' => 'Cake\Model\I18nModel',
        'I18nShell' => 'Cake\Console\Command\I18nShell',
        'Inflector' => 'Cake\Utility\Inflector',
        'IniAcl' => 'Cake\Controller\Component\Acl\IniAcl',
        'IniReader' => 'Cake\Configure\IniReader',
        'InterceptContentHelper' => 'Cake\TestSuite\InterceptContentHelper',
        'JqueryEngineHelper' => 'Cake\View\Helper\JqueryEngineHelper',
        'JsBaseEngineHelper' => 'Cake\View\Helper\JsBaseEngineHelper',
        'JsHelper' => 'Cake\View\Helper\JsHelper',
        'JsonView' => 'Cake\View\JsonView',
        'L10n' => 'Cake\I18n\L10n',
        'LogEngineCollection' => 'Cake\Log\LogEngineCollection',
        'MailTransport' => 'Cake\Network\Email\MailTransport',
        'MediaView' => 'Cake\View\MediaView',
        'MemcacheEngine' => 'Cake\Cache\Engine\MemcacheEngine',
        'MemcachedEngine' => 'Cake\Cache\Engine\MemcachedEngine',
        'Model' => 'Cake\Model\Model',
        'ModelBehavior' => 'Cake\Model\ModelBehavior',
        'ModelValidator' => 'Cake\Model\ModelValidator',
        'MootoolsEngineHelper' => 'Cake\View\Helper\MootoolsEngineHelper',
        'Multibyte' => 'Cake\I18n\Multibyte',
        'Mysql' => 'Cake\Model\Datasource\Database\Mysql',
        'NumberHelper' => 'Cake\View\Helper\NumberHelper',
        'ObjectCollection' => 'Cake\Utility\ObjectCollection',
        'PDOExceptionWithQueryString' => 'Cake\Model\Datasource\PDOExceptionWithQueryString',
        'PaginatorComponent' => 'Cake\Controller\Component\PaginatorComponent',
        'PaginatorHelper' => 'Cake\View\Helper\PaginatorHelper',
        'Permission' => 'Cake\Model\Permission',
        'PhpAcl' => 'Cake\Controller\Component\Acl\PhpAcl',
        'PhpAco' => 'Cake\Controller\Component\Acl\PhpAco',
        'PhpAro' => 'Cake\Controller\Component\Acl\PhpAro',
        'PhpReader' => 'Cake\Configure\PhpReader',
        'PluginShortRoute' => 'Cake\Routing\Route\PluginShortRoute',
        'Postgres' => 'Cake\Model\Datasource\Database\Postgres',
        'ProgressShellHelper' => 'Cake\Console\Helper\ProgressShellHelper',
        'PrototypeEngineHelper' => 'Cake\View\Helper\PrototypeEngineHelper',
        'RedirectRoute' => 'Cake\Routing\Route\RedirectRoute',
        'RedisEngine' => 'Cake\Cache\Engine\RedisEngine',
        'RequestHandlerComponent' => 'Cake\Controller\Component\RequestHandlerComponent',
        'Router' => 'Cake\Routing\Router',
        'RssHelper' => 'Cake\View\Helper\RssHelper',
        'Sanitize' => 'Cake\Utility\Sanitize',
        'Scaffold' => 'Cake\Controller\Scaffold',
        'ScaffoldView' => 'Cake\View\ScaffoldView',
        'SchemaShell' => 'Cake\Console\Command\SchemaShell',
        'Security' => 'Cake\Utility\Security',
        'SecurityComponent' => 'Cake\Controller\Component\SecurityComponent',
        'ServerShell' => 'Cake\Console\Command\ServerShell',
        'SessionComponent' => 'Cake\Controller\Component\SessionComponent',
        'SessionHandlerAdapter' => 'Cake\Model\Datasource\SessionHandlerAdapter',
        'SessionHelper' => 'Cake\View\Helper\SessionHelper',
        'Set' => 'Cake\Utility\Set',
        'Shell' => 'Cake\Console\Shell',
        'ShellDispatcher' => 'Cake\Console\ShellDispatcher',
        'SimplePasswordHasher' => 'Cake\Controller\Component\Auth\SimplePasswordHasher',
        'SmtpTransport' => 'Cake\Network\Email\SmtpTransport',
        'Sqlite' => 'Cake\Model\Datasource\Database\Sqlite',
        'Sqlserver' => 'Cake\Model\Datasource\Database\Sqlserver',
        'SyslogLog' => 'Cake\Log\Engine\SyslogLog',
        'TableShellHelper' => 'Cake\Console\Helper\TableShellHelper',
        'TaskCollection' => 'Cake\Console\TaskCollection',
        'TestShell' => 'Cake\Console\Command\TestShell',
        'TestsuiteShell' => 'Cake\Console\Command\TestsuiteShell',
        'TextCoverageReport' => 'Cake\TestSuite\Coverage\TextCoverageReport',
        'TextHelper' => 'Cake\View\Helper\TextHelper',
        'ThemeView' => 'Cake\View\ThemeView',
        'TimeHelper' => 'Cake\View\Helper\TimeHelper',
        'TranslateBehavior' => 'Cake\Model\Behavior\TranslateBehavior',
        'TreeBehavior' => 'Cake\Model\Behavior\TreeBehavior',
        'Validation' => 'Cake\Utility\Validation',
        'View' => 'Cake\View\View',
        'ViewBlock' => 'Cake\View\ViewBlock',
        'Xml' => 'Cake\Utility\Xml',
        'XmlView' => 'Cake\View\XmlView',
    ];

    /**
     * Get the class map
     *
     * @return array The legacy class name to namespace mapping
     */
    public static function getClassMap(): array
    {
        return self::$classMap;
    }

    /**
     * Autoload function for legacy class names
     *
     * @param string $class The class name to autoload
     * @return void
     */
    public static function autoload(string $class): void
    {
        // Skip if class is already namespaced
        if (str_contains($class, '\\')) {
            return;
        }

        // Handle App* base classes (AppController, AppModel, AppHelper, AppShell)
        // These should be resolved from the application's namespace
        if (str_starts_with($class, 'App') && strlen($class) > 3 && ctype_upper($class[3])) {
            $namespacedClass = self::_resolveAppClass($class);
            if ($namespacedClass && class_exists($namespacedClass)) {
                class_alias($namespacedClass, $class);

                return;
            }
        }

        // Check if we have a mapping for this legacy class name
        if (isset(self::$classMap[$class])) {
            class_alias(self::$classMap[$class], $class);
        }
    }

    /**
     * Resolve App* base class to its namespaced equivalent
     *
     * Application base classes (AppController, AppModel, etc.) belong to the
     * application's namespace, not the framework's Cake namespace. This method
     * resolves them using the configured application namespace.
     *
     * @param string $class The class name (e.g., AppController, AppModel)
     * @return string|null The fully qualified namespaced class name, or null if not resolvable
     */
    protected static function _resolveAppClass(string $class): ?string
    {
        // Map application base class names to their namespace paths
        $appClassMap = [
            'AppController' => 'Controller\AppController',
            'AppModel' => 'Model\AppModel',
            'AppHelper' => 'View\Helper\AppHelper',
            'AppShell' => 'Console\Command\AppShell',
        ];

        if (!isset($appClassMap[$class])) {
            return null;
        }

        // Ensure Configure class is available before attempting to read configuration
        if (!class_exists('Cake\Core\Configure', false)) {
            return null;
        }

        // Get the application namespace from configuration (defaults to 'App')
        $appNamespace = Configure::read('App.namespace');
        if (!$appNamespace) {
            $appNamespace = 'App';
        }

        return $appNamespace . '\\' . $appClassMap[$class];
    }
}

// Register the autoloader
spl_autoload_register([LegacyClassLoader::class, 'autoload']);

// Eager load Exception classes for catch blocks
// Exception classes need to be loaded before being used in catch statements
class_alias('Cake\Error\AclException', 'AclException');
class_alias('Cake\Error\AuthSecurityException', 'AuthSecurityException');
class_alias('Cake\Error\BadRequestException', 'BadRequestException');
class_alias('Cake\Error\CacheException', 'CacheException');
class_alias('Cake\Error\CakeBaseException', 'CakeBaseException');
class_alias('Cake\Error\CakeException', 'CakeException');
class_alias('Cake\Error\CakeLogException', 'CakeLogException');
class_alias('Cake\Error\CakeSessionException', 'CakeSessionException');
class_alias('Cake\Error\ConfigureException', 'ConfigureException');
class_alias('Cake\Error\ConsoleException', 'ConsoleException');
class_alias('Cake\Error\FatalErrorException', 'FatalErrorException');
class_alias('Cake\Error\ForbiddenException', 'ForbiddenException');
class_alias('Cake\Error\HttpException', 'HttpException');
class_alias('Cake\Error\InternalErrorException', 'InternalErrorException');
class_alias('Cake\Error\MethodNotAllowedException', 'MethodNotAllowedException');
class_alias('Cake\Error\MissingActionException', 'MissingActionException');
class_alias('Cake\Error\MissingBehaviorException', 'MissingBehaviorException');
class_alias('Cake\Error\MissingComponentException', 'MissingComponentException');
class_alias('Cake\Error\MissingConnectionException', 'MissingConnectionException');
class_alias('Cake\Error\MissingControllerException', 'MissingControllerException');
class_alias('Cake\Error\MissingDatabaseException', 'MissingDatabaseException');
class_alias('Cake\Error\MissingDatasourceConfigException', 'MissingDatasourceConfigException');
class_alias('Cake\Error\MissingDatasourceException', 'MissingDatasourceException');
class_alias('Cake\Error\MissingDispatcherFilterException', 'MissingDispatcherFilterException');
class_alias('Cake\Error\MissingHelperException', 'MissingHelperException');
class_alias('Cake\Error\MissingLayoutException', 'MissingLayoutException');
class_alias('Cake\Error\MissingModelException', 'MissingModelException');
class_alias('Cake\Error\MissingPluginException', 'MissingPluginException');
class_alias('Cake\Error\MissingShellException', 'MissingShellException');
class_alias('Cake\Error\MissingShellMethodException', 'MissingShellMethodException');
class_alias('Cake\Error\MissingTableException', 'MissingTableException');
class_alias('Cake\Error\MissingTaskException', 'MissingTaskException');
class_alias('Cake\Error\MissingTestLoaderException', 'MissingTestLoaderException');
class_alias('Cake\Error\MissingViewException', 'MissingViewException');
class_alias('Cake\Error\NotFoundException', 'NotFoundException');
class_alias('Cake\Error\NotImplementedException', 'NotImplementedException');
class_alias('Cake\Error\PrivateActionException', 'PrivateActionException');
class_alias('Cake\Error\RouterException', 'RouterException');
class_alias('Cake\Error\SecurityException', 'SecurityException');
class_alias('Cake\Error\SocketException', 'SocketException');
class_alias('Cake\Error\UnauthorizedException', 'UnauthorizedException');
class_alias('Cake\Error\XmlException', 'XmlException');

// IDE helper - class aliases for code completion (never executed)
// phpcs:disable
if (false) { // @phpstan-ignore-line
    class_alias('Cake\Controller\Component\Auth\AbstractPasswordHasher', 'AbstractPasswordHasher');
    class_alias('Cake\Network\Email\AbstractTransport', 'AbstractTransport');
    class_alias('Cake\Model\Behavior\AclBehavior', 'AclBehavior');
    class_alias('Cake\Controller\Component\AclComponent', 'AclComponent');
    class_alias('Cake\Controller\Component\Acl\AclInterface', 'AclInterface');
    class_alias('Cake\Model\AclNode', 'AclNode');
    class_alias('Cake\Console\Command\AclShell', 'AclShell');
    class_alias('Cake\Model\Aco', 'Aco');
    class_alias('Cake\Model\AcoAction', 'AcoAction');
    class_alias('Cake\Controller\Component\Auth\ActionsAuthorize', 'ActionsAuthorize');
    class_alias('Cake\Cache\Engine\ApcEngine', 'ApcEngine');
    class_alias('Cake\Console\Command\ApiShell', 'ApiShell');
    class_alias('Cake\Core\App', 'App');
    // Note: AppShell is an application base class, not part of Cake framework
    class_alias('Cake\Model\Aro', 'Aro');
    class_alias('Cake\Routing\Filter\AssetDispatcher', 'AssetDispatcher');
    class_alias('Cake\Controller\Component\AuthComponent', 'AuthComponent');
    class_alias('Cake\Controller\Component\Auth\BaseAuthenticate', 'BaseAuthenticate');
    class_alias('Cake\Controller\Component\Auth\BaseAuthorize', 'BaseAuthorize');
    class_alias('Cake\TestSuite\Coverage\BaseCoverageReport', 'BaseCoverageReport');
    class_alias('Cake\Log\Engine\BaseLog', 'BaseLog');
    class_alias('Cake\Console\Helper\BaseShellHelper', 'BaseShellHelper');
    class_alias('Cake\Controller\Component\Auth\BasicAuthenticate', 'BasicAuthenticate');
    class_alias('Cake\Network\Http\BasicAuthentication', 'BasicAuthentication');
    class_alias('Cake\Model\BehaviorCollection', 'BehaviorCollection');
    class_alias('Cake\Controller\Component\Auth\BlowfishAuthenticate', 'BlowfishAuthenticate');
    class_alias('Cake\Controller\Component\Auth\BlowfishPasswordHasher', 'BlowfishPasswordHasher');
    class_alias('Cake\Cache\Cache', 'Cache');
    class_alias('Cake\Routing\Filter\CacheDispatcher', 'CacheDispatcher');
    class_alias('Cake\Cache\CacheEngine', 'CacheEngine');
    class_alias('Cake\View\Helper\CacheHelper', 'CacheHelper');
    class_alias('Cake\Model\Datasource\Session\CacheSession', 'CacheSession');
    class_alias('Cake\TestSuite\Reporter\CakeBaseReporter', 'CakeBaseReporter');
    class_alias('Cake\Network\Email\CakeEmail', 'CakeEmail');
    class_alias('Cake\Controller\CakeErrorController', 'CakeErrorController');
    class_alias('Cake\Event\CakeEvent', 'CakeEvent');
    class_alias('Cake\Event\CakeEventListener', 'CakeEventListener');
    class_alias('Cake\Event\CakeEventManager', 'CakeEventManager');
    class_alias('Cake\TestSuite\Fixture\CakeFixtureInjector', 'CakeFixtureInjector');
    class_alias('Cake\TestSuite\Fixture\CakeFixtureManager', 'CakeFixtureManager');
    class_alias('Cake\TestSuite\Reporter\CakeHtmlReporter', 'CakeHtmlReporter');
    class_alias('Cake\Log\CakeLog', 'CakeLog');
    class_alias('Cake\Log\CakeLogInterface', 'CakeLogInterface');
    class_alias('Cake\Utility\CakeNumber', 'CakeNumber');
    class_alias('Cake\Core\CakeObject', 'CakeObject');
    class_alias('Cake\Core\CakePlugin', 'CakePlugin');
    class_alias('Cake\Network\CakeRequest', 'CakeRequest');
    class_alias('Cake\Network\CakeResponse', 'CakeResponse');
    class_alias('Cake\Routing\Route\CakeRoute', 'CakeRoute');
    class_alias('Cake\Model\CakeSchema', 'CakeSchema');
    class_alias('Cake\Model\Datasource\CakeSession', 'CakeSession');
    class_alias('Cake\Model\Datasource\Session\CakeSessionHandlerInterface', 'CakeSessionHandlerInterface');
    class_alias('Cake\Network\CakeSocket', 'CakeSocket');
    class_alias('Cake\TestSuite\CakeTestCase', 'CakeTestCase');
    class_alias('Cake\TestSuite\Fixture\CakeTestFixture', 'CakeTestFixture');
    class_alias('Cake\TestSuite\CakeTestLoader', 'CakeTestLoader');
    class_alias('Cake\TestSuite\Fixture\CakeTestModel', 'CakeTestModel');
    class_alias('Cake\TestSuite\CakeTestSuite', 'CakeTestSuite');
    class_alias('Cake\TestSuite\CakeTestSuiteCommand', 'CakeTestSuiteCommand');
    class_alias('Cake\TestSuite\CakeTestSuiteDispatcher', 'CakeTestSuiteDispatcher');
    class_alias('Cake\Utility\CakeText', 'CakeText');
    class_alias('Cake\TestSuite\Reporter\CakeTextReporter', 'CakeTextReporter');
    class_alias('Cake\Utility\CakeTime', 'CakeTime');
    class_alias('Cake\Model\Validator\CakeValidationRule', 'CakeValidationRule');
    class_alias('Cake\Model\Validator\CakeValidationSet', 'CakeValidationSet');
    class_alias('Cake\Utility\ClassRegistry', 'ClassRegistry');
    class_alias('Cake\Console\Command\CommandListShell', 'CommandListShell');
    class_alias('Cake\Console\Command\Task\CommandTask', 'CommandTask');
    class_alias('Cake\Console\Command\CompletionShell', 'CompletionShell');
    class_alias('Cake\Controller\Component', 'Component');
    class_alias('Cake\Controller\ComponentCollection', 'ComponentCollection');
    class_alias('Cake\Configure\ConfigReaderInterface', 'ConfigReaderInterface');
    class_alias('Cake\Core\Configure', 'Configure');
    class_alias('Cake\Model\ConnectionManager', 'ConnectionManager');
    class_alias('Cake\Console\ConsoleErrorHandler', 'ConsoleErrorHandler');
    class_alias('Cake\Console\ConsoleInput', 'ConsoleInput');
    class_alias('Cake\Console\ConsoleInputArgument', 'ConsoleInputArgument');
    class_alias('Cake\Console\ConsoleInputOption', 'ConsoleInputOption');
    class_alias('Cake\Console\ConsoleInputSubcommand', 'ConsoleInputSubcommand');
    class_alias('Cake\Log\Engine\ConsoleLog', 'ConsoleLog');
    class_alias('Cake\Console\ConsoleOptionParser', 'ConsoleOptionParser');
    class_alias('Cake\Console\ConsoleOutput', 'ConsoleOutput');
    class_alias('Cake\TestSuite\Stub\ConsoleOutputStub', 'ConsoleOutputStub');
    class_alias('Cake\Console\Command\ConsoleShell', 'ConsoleShell');
    class_alias('Cake\Model\Behavior\ContainableBehavior', 'ContainableBehavior');
    class_alias('Cake\Controller\Controller', 'Controller');
    class_alias('Cake\Controller\Component\Auth\ControllerAuthorize', 'ControllerAuthorize');
    class_alias('Cake\TestSuite\ControllerTestCase', 'ControllerTestCase');
    class_alias('Cake\TestSuite\ControllerTestDispatcher', 'ControllerTestDispatcher');
    class_alias('Cake\Controller\Component\CookieComponent', 'CookieComponent');
    class_alias('Cake\Controller\Component\Auth\CrudAuthorize', 'CrudAuthorize');
    class_alias('Cake\Model\Datasource\DataSource', 'DataSource');
    class_alias('Cake\Model\Datasource\Session\DatabaseSession', 'DatabaseSession');
    class_alias('Cake\Controller\Component\Acl\DbAcl', 'DbAcl');
    class_alias('Cake\Model\Datasource\DboSource', 'DboSource');
    class_alias('Cake\Network\Email\DebugTransport', 'DebugTransport');
    class_alias('Cake\Utility\Debugger', 'Debugger');
    class_alias('Cake\Controller\Component\Auth\DigestAuthenticate', 'DigestAuthenticate');
    class_alias('Cake\Network\Http\DigestAuthentication', 'DigestAuthentication');
    class_alias('Cake\Routing\Dispatcher', 'Dispatcher');
    class_alias('Cake\Routing\DispatcherFilter', 'DispatcherFilter');
    class_alias('Cake\Controller\Component\EmailComponent', 'EmailComponent');
    class_alias('Cake\Console\Command\Task\ExtractTask', 'ExtractTask');
    class_alias('Cake\Utility\File', 'File');
    class_alias('Cake\Cache\Engine\FileEngine', 'FileEngine');
    class_alias('Cake\Log\Engine\FileLog', 'FileLog');
    class_alias('Cake\Controller\Component\FlashComponent', 'FlashComponent');
    class_alias('Cake\View\Helper\FlashHelper', 'FlashHelper');
    class_alias('Cake\Utility\Folder', 'Folder');
    class_alias('Cake\Controller\Component\Auth\FormAuthenticate', 'FormAuthenticate');
    class_alias('Cake\View\Helper\FormHelper', 'FormHelper');
    class_alias('Cake\Utility\Hash', 'Hash');
    class_alias('Cake\Console\HelpFormatter', 'HelpFormatter');
    class_alias('Cake\View\Helper', 'Helper');
    class_alias('Cake\View\HelperCollection', 'HelperCollection');
    class_alias('Cake\TestSuite\Coverage\HtmlCoverageReport', 'HtmlCoverageReport');
    class_alias('Cake\View\Helper\HtmlHelper', 'HtmlHelper');
    class_alias('Cake\Network\Http\HttpResponse', 'HttpResponse');
    class_alias('Cake\Network\Http\HttpSocket', 'HttpSocket');
    class_alias('Cake\Network\Http\HttpSocketResponse', 'HttpSocketResponse');
    class_alias('Cake\I18n\I18n', 'I18n');
    class_alias('Cake\Model\I18nModel', 'I18nModel');
    class_alias('Cake\Console\Command\I18nShell', 'I18nShell');
    class_alias('Cake\Utility\Inflector', 'Inflector');
    class_alias('Cake\Controller\Component\Acl\IniAcl', 'IniAcl');
    class_alias('Cake\Configure\IniReader', 'IniReader');
    class_alias('Cake\TestSuite\InterceptContentHelper', 'InterceptContentHelper');
    class_alias('Cake\View\Helper\JqueryEngineHelper', 'JqueryEngineHelper');
    class_alias('Cake\View\Helper\JsBaseEngineHelper', 'JsBaseEngineHelper');
    class_alias('Cake\View\Helper\JsHelper', 'JsHelper');
    class_alias('Cake\View\JsonView', 'JsonView');
    class_alias('Cake\I18n\L10n', 'L10n');
    class_alias('Cake\Log\LogEngineCollection', 'LogEngineCollection');
    class_alias('Cake\Network\Email\MailTransport', 'MailTransport');
    class_alias('Cake\View\MediaView', 'MediaView');
    class_alias('Cake\Cache\Engine\MemcacheEngine', 'MemcacheEngine');
    class_alias('Cake\Cache\Engine\MemcachedEngine', 'MemcachedEngine');
    class_alias('Cake\Model\Model', 'Model');
    class_alias('Cake\Model\ModelBehavior', 'ModelBehavior');
    class_alias('Cake\Model\ModelValidator', 'ModelValidator');
    class_alias('Cake\View\Helper\MootoolsEngineHelper', 'MootoolsEngineHelper');
    class_alias('Cake\I18n\Multibyte', 'Multibyte');
    class_alias('Cake\Model\Datasource\Database\Mysql', 'Mysql');
    class_alias('Cake\View\Helper\NumberHelper', 'NumberHelper');
    class_alias('Cake\Utility\ObjectCollection', 'ObjectCollection');
    class_alias('Cake\Model\Datasource\PDOExceptionWithQueryString', 'PDOExceptionWithQueryString');
    class_alias('Cake\Controller\Component\PaginatorComponent', 'PaginatorComponent');
    class_alias('Cake\View\Helper\PaginatorHelper', 'PaginatorHelper');
    class_alias('Cake\Model\Permission', 'Permission');
    class_alias('Cake\Controller\Component\Acl\PhpAcl', 'PhpAcl');
    class_alias('Cake\Controller\Component\Acl\PhpAco', 'PhpAco');
    class_alias('Cake\Controller\Component\Acl\PhpAro', 'PhpAro');
    class_alias('Cake\Configure\PhpReader', 'PhpReader');
    class_alias('Cake\Routing\Route\PluginShortRoute', 'PluginShortRoute');
    class_alias('Cake\Model\Datasource\Database\Postgres', 'Postgres');
    class_alias('Cake\Console\Helper\ProgressShellHelper', 'ProgressShellHelper');
    class_alias('Cake\View\Helper\PrototypeEngineHelper', 'PrototypeEngineHelper');
    class_alias('Cake\Routing\Route\RedirectRoute', 'RedirectRoute');
    class_alias('Cake\Cache\Engine\RedisEngine', 'RedisEngine');
    class_alias('Cake\Controller\Component\RequestHandlerComponent', 'RequestHandlerComponent');
    class_alias('Cake\Routing\Router', 'Router');
    class_alias('Cake\View\Helper\RssHelper', 'RssHelper');
    class_alias('Cake\Utility\Sanitize', 'Sanitize');
    class_alias('Cake\Controller\Scaffold', 'Scaffold');
    class_alias('Cake\View\ScaffoldView', 'ScaffoldView');
    class_alias('Cake\Console\Command\SchemaShell', 'SchemaShell');
    class_alias('Cake\Utility\Security', 'Security');
    class_alias('Cake\Controller\Component\SecurityComponent', 'SecurityComponent');
    class_alias('Cake\Console\Command\ServerShell', 'ServerShell');
    class_alias('Cake\Controller\Component\SessionComponent', 'SessionComponent');
    class_alias('Cake\Model\Datasource\SessionHandlerAdapter', 'SessionHandlerAdapter');
    class_alias('Cake\View\Helper\SessionHelper', 'SessionHelper');
    class_alias('Cake\Utility\Set', 'Set');
    class_alias('Cake\Console\Shell', 'Shell');
    class_alias('Cake\Console\ShellDispatcher', 'ShellDispatcher');
    class_alias('Cake\Controller\Component\Auth\SimplePasswordHasher', 'SimplePasswordHasher');
    class_alias('Cake\Network\Email\SmtpTransport', 'SmtpTransport');
    class_alias('Cake\Model\Datasource\Database\Sqlite', 'Sqlite');
    class_alias('Cake\Model\Datasource\Database\Sqlserver', 'Sqlserver');
    class_alias('Cake\Log\Engine\SyslogLog', 'SyslogLog');
    class_alias('Cake\Console\Helper\TableShellHelper', 'TableShellHelper');
    class_alias('Cake\Console\TaskCollection', 'TaskCollection');
    class_alias('Cake\Console\Command\TestShell', 'TestShell');
    class_alias('Cake\Console\Command\TestsuiteShell', 'TestsuiteShell');
    class_alias('Cake\TestSuite\Coverage\TextCoverageReport', 'TextCoverageReport');
    class_alias('Cake\View\Helper\TextHelper', 'TextHelper');
    class_alias('Cake\View\ThemeView', 'ThemeView');
    class_alias('Cake\View\Helper\TimeHelper', 'TimeHelper');
    class_alias('Cake\Model\Behavior\TranslateBehavior', 'TranslateBehavior');
    class_alias('Cake\Model\Behavior\TreeBehavior', 'TreeBehavior');
    class_alias('Cake\Utility\Validation', 'Validation');
    class_alias('Cake\View\View', 'View');
    class_alias('Cake\View\ViewBlock', 'ViewBlock');
    class_alias('Cake\Utility\Xml', 'Xml');
    class_alias('Cake\View\XmlView', 'XmlView');
}
// phpcs:enable
