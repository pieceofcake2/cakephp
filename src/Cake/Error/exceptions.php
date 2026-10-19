<?php
/**
 * Exceptions file. Contains the various exceptions CakePHP will throw until they are
 * moved into their permanent location.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html
 * @package       Cake.Error
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Error;

use Cake\Network\CakeResponse;
use Exception;
use RuntimeException;

/**
 * Base class that all Exceptions extend.
 *
 * @package       Cake.Error
 */
class CakeBaseException extends RuntimeException
{
    /**
     * Array of headers to be passed to CakeResponse::header()
     *
     * @var array
     */
    protected $_responseHeaders = null;

    /**
     * Get/set the response header to be used
     *
     * @param array|string $header An array of header strings or a single header string
     *  - an associative array of "header name" => "header value"
     *  - an array of string headers is also accepted
     * @param string $value The header value.
     * @return array
     * @see CakeResponse::header()
     */
    public function responseHeader($header = null, $value = null)
    {
        if ($header) {
            if (is_array($header)) {
                return $this->_responseHeaders = $header;
            }
            $this->_responseHeaders = [$header => $value];
        }

        return $this->_responseHeaders;
    }
}

class_alias(CakeBaseException::class, 'CakeBaseException');

/**
 * Parent class for all of the HTTP related exceptions in CakePHP.
 *
 * All HTTP status/error related exceptions should extend this class so
 * catch blocks can be specifically typed.
 *
 * @package       Cake.Error
 */
class HttpException extends CakeBaseException
{
}

// pecl/http 1.x declares a global HttpException
if (!class_exists('HttpException', false)) {
    class_alias(HttpException::class, 'HttpException');
}

/**
 * Represents an HTTP 400 error.
 *
 * @package       Cake.Error
 */
class BadRequestException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message If no message is given 'Bad Request' will be the message
     * @param int $code Status code, defaults to 400
     */
    public function __construct($message = null, $code = 400)
    {
        if (empty($message)) {
            $message = 'Bad Request';
        }
        parent::__construct($message, $code);
    }
}

class_alias(BadRequestException::class, 'BadRequestException');

/**
 * Represents an HTTP 401 error.
 *
 * @package       Cake.Error
 */
class UnauthorizedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message If no message is given 'Unauthorized' will be the message
     * @param int $code Status code, defaults to 401
     */
    public function __construct($message = null, $code = 401)
    {
        if (empty($message)) {
            $message = 'Unauthorized';
        }
        parent::__construct($message, $code);
    }
}

class_alias(UnauthorizedException::class, 'UnauthorizedException');

/**
 * Represents an HTTP 403 error.
 *
 * @package       Cake.Error
 */
class ForbiddenException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message If no message is given 'Forbidden' will be the message
     * @param int $code Status code, defaults to 403
     */
    public function __construct($message = null, $code = 403)
    {
        if (empty($message)) {
            $message = 'Forbidden';
        }
        parent::__construct($message, $code);
    }
}

class_alias(ForbiddenException::class, 'ForbiddenException');

/**
 * Represents an HTTP 404 error.
 *
 * @package       Cake.Error
 */
class NotFoundException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message If no message is given 'Not Found' will be the message
     * @param int $code Status code, defaults to 404
     */
    public function __construct($message = null, $code = 404)
    {
        if (empty($message)) {
            $message = 'Not Found';
        }
        parent::__construct($message, $code);
    }
}

class_alias(NotFoundException::class, 'NotFoundException');

/**
 * Represents an HTTP 405 error.
 *
 * @package       Cake.Error
 */
class MethodNotAllowedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message If no message is given 'Method Not Allowed' will be the message
     * @param int $code Status code, defaults to 405
     */
    public function __construct($message = null, $code = 405)
    {
        if (empty($message)) {
            $message = 'Method Not Allowed';
        }
        parent::__construct($message, $code);
    }
}

class_alias(MethodNotAllowedException::class, 'MethodNotAllowedException');

/**
 * Represents an HTTP 500 error.
 *
 * @package       Cake.Error
 */
class InternalErrorException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message If no message is given 'Internal Server Error' will be the message
     * @param int $code Status code, defaults to 500
     */
    public function __construct($message = null, $code = 500)
    {
        if (empty($message)) {
            $message = 'Internal Server Error';
        }
        parent::__construct($message, $code);
    }
}

class_alias(InternalErrorException::class, 'InternalErrorException');

/**
 * CakeException is used a base class for CakePHP's internal exceptions.
 * In general framework errors are interpreted as 500 code errors.
 *
 * @package       Cake.Error
 */
class CakeException extends CakeBaseException
{
    /**
     * Array of attributes that are passed in from the constructor, and
     * made available in the view when a development error is displayed.
     *
     * @var array
     */
    protected $_attributes = [];

    /**
     * Template string that has attributes sprintf()'ed into it.
     *
     * @var string
     */
    protected $_messageTemplate = '';

    /**
     * Constructor.
     *
     * Allows you to create exceptions that are treated as framework errors and disabled
     * when debug = 0.
     *
     * @param array|string $message Either the string of the error message, or an array of attributes
     *   that are made available in the view, and sprintf()'d into CakeException::$_messageTemplate
     * @param int $code The code of the error, is also the HTTP status code for the error.
     */
    public function __construct($message, $code = 500)
    {
        if (is_array($message)) {
            $this->_attributes = $message;
            $message = __d('cake_dev', $this->_messageTemplate, $message);
        }
        parent::__construct($message, $code);
    }

    /**
     * Get the passed in attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }
}

class_alias(CakeException::class, 'CakeException');

/**
 * Missing Controller exception - used when a controller
 * cannot be found.
 *
 * @package       Cake.Error
 */
class MissingControllerException extends CakeException
{
    protected $_messageTemplate = 'Controller class %s could not be found.';

//@codingStandardsIgnoreStart

    public function __construct($message, $code = 404)
    {
        parent::__construct($message, $code);
    }

//@codingStandardsIgnoreEnd
}

class_alias(MissingControllerException::class, 'MissingControllerException');

/**
 * Missing Action exception - used when a controller action
 * cannot be found.
 *
 * @package       Cake.Error
 */
class MissingActionException extends CakeException
{
    protected $_messageTemplate = 'Action %s::%s() could not be found.';

//@codingStandardsIgnoreStart

    public function __construct($message, $code = 404)
    {
        parent::__construct($message, $code);
    }

//@codingStandardsIgnoreEnd
}

class_alias(MissingActionException::class, 'MissingActionException');

/**
 * Private Action exception - used when a controller action
 * starts with a  `_`.
 *
 * @package       Cake.Error
 */
class PrivateActionException extends CakeException
{
    protected $_messageTemplate = 'Private Action %s::%s() is not directly accessible.';

//@codingStandardsIgnoreStart

    public function __construct($message, $code = 404, ?Exception $previous = null)
    {
        // CakeException::__construct() does not accept a previous exception,
        // so the message template is applied here and RuntimeException is called directly.
        if (is_array($message)) {
            $this->_attributes = $message;
            $message = __d('cake_dev', $this->_messageTemplate, $message);
        }
        RuntimeException::__construct($message, $code, $previous);
    }

//@codingStandardsIgnoreEnd
}

class_alias(PrivateActionException::class, 'PrivateActionException');

/**
 * Used when a component cannot be found.
 *
 * @package       Cake.Error
 */
class MissingComponentException extends CakeException
{
    protected $_messageTemplate = 'Component class %s could not be found.';
}

class_alias(MissingComponentException::class, 'MissingComponentException');

/**
 * Used when a behavior cannot be found.
 *
 * @package       Cake.Error
 */
class MissingBehaviorException extends CakeException
{
    protected $_messageTemplate = 'Behavior class %s could not be found.';
}

class_alias(MissingBehaviorException::class, 'MissingBehaviorException');

/**
 * Used when a view file cannot be found.
 *
 * @package       Cake.Error
 */
class MissingViewException extends CakeException
{
    protected $_messageTemplate = 'View file "%s" is missing.';
}

class_alias(MissingViewException::class, 'MissingViewException');

/**
 * Used when a layout file cannot be found.
 *
 * @package       Cake.Error
 */
class MissingLayoutException extends CakeException
{
    protected $_messageTemplate = 'Layout file "%s" is missing.';
}

class_alias(MissingLayoutException::class, 'MissingLayoutException');

/**
 * Used when a helper cannot be found.
 *
 * @package       Cake.Error
 */
class MissingHelperException extends CakeException
{
    protected $_messageTemplate = 'Helper class %s could not be found.';
}

class_alias(MissingHelperException::class, 'MissingHelperException');

/**
 * Runtime Exceptions for ConnectionManager
 *
 * @package       Cake.Error
 */
class MissingDatabaseException extends CakeException
{
    protected $_messageTemplate = 'Database connection "%s" could not be found.';
}

class_alias(MissingDatabaseException::class, 'MissingDatabaseException');

/**
 * Used when no connections can be found.
 *
 * @package       Cake.Error
 */
class MissingConnectionException extends CakeException
{
    protected $_messageTemplate = 'Database connection "%s" is missing, or could not be created.';

    /**
     * Constructor
     *
     * @param array|string $message The error message.
     * @param int $code The error code.
     */
    public function __construct($message, $code = 500)
    {
        if (is_array($message)) {
            $message += ['enabled' => true];
        }
        if (isset($message['message'])) {
            $this->_messageTemplate .= ' %s';
        }
        parent::__construct($message, $code);
    }
}

class_alias(MissingConnectionException::class, 'MissingConnectionException');

/**
 * Used when a Task cannot be found.
 *
 * @package       Cake.Error
 */
class MissingTaskException extends CakeException
{
    protected $_messageTemplate = 'Task class %s could not be found.';
}

class_alias(MissingTaskException::class, 'MissingTaskException');

/**
 * Used when a shell method cannot be found.
 *
 * @package       Cake.Error
 */
class MissingShellMethodException extends CakeException
{
    protected $_messageTemplate = "Unknown command %1\$s %2\$s.\nFor usage try `cake %1\$s --help`";
}

class_alias(MissingShellMethodException::class, 'MissingShellMethodException');

/**
 * Used when a shell cannot be found.
 *
 * @package       Cake.Error
 */
class MissingShellException extends CakeException
{
    protected $_messageTemplate = 'Shell class %s could not be found.';
}

class_alias(MissingShellException::class, 'MissingShellException');

/**
 * Exception class to be thrown when a datasource configuration is not found
 *
 * @package       Cake.Error
 */
class MissingDatasourceConfigException extends CakeException
{
    protected $_messageTemplate = 'The datasource configuration "%s" was not found in database.php';
}

class_alias(MissingDatasourceConfigException::class, 'MissingDatasourceConfigException');

/**
 * Used when a datasource cannot be found.
 *
 * @package       Cake.Error
 */
class MissingDatasourceException extends CakeException
{
    protected $_messageTemplate = 'Datasource class %s could not be found. %s';
}

class_alias(MissingDatasourceException::class, 'MissingDatasourceException');

/**
 * Exception class to be thrown when a database table is not found in the datasource
 *
 * @package       Cake.Error
 */
class MissingTableException extends CakeException
{
    protected $_messageTemplate = 'Table %s for model %s was not found in datasource %s.';
}

class_alias(MissingTableException::class, 'MissingTableException');

/**
 * Exception raised when a Model could not be found.
 *
 * @package       Cake.Error
 */
class MissingModelException extends CakeException
{
    protected $_messageTemplate = 'Model %s could not be found.';
}

class_alias(MissingModelException::class, 'MissingModelException');

/**
 * Exception raised when a test loader could not be found
 *
 * @package       Cake.Error
 */
class MissingTestLoaderException extends CakeException
{
    protected $_messageTemplate = 'Test loader %s could not be found.';
}

class_alias(MissingTestLoaderException::class, 'MissingTestLoaderException');

/**
 * Exception raised when a plugin could not be found
 *
 * @package       Cake.Error
 */
class MissingPluginException extends CakeException
{
    protected $_messageTemplate = 'Plugin %s could not be found.';
}

class_alias(MissingPluginException::class, 'MissingPluginException');

/**
 * Exception raised when a Dispatcher filter could not be found
 *
 * @package       Cake.Error
 */
class MissingDispatcherFilterException extends CakeException
{
    protected $_messageTemplate = 'Dispatcher filter %s could not be found.';
}

class_alias(MissingDispatcherFilterException::class, 'MissingDispatcherFilterException');

/**
 * Exception class for AclComponent and Interface implementations.
 *
 * @package       Cake.Error
 */
class AclException extends CakeException
{
}

class_alias(AclException::class, 'AclException');

/**
 * Exception class for Cache. This exception will be thrown from Cache when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class CacheException extends CakeException
{
}

class_alias(CacheException::class, 'CacheException');

/**
 * Exception class for Router. This exception will be thrown from Router when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class RouterException extends CakeException
{
}

class_alias(RouterException::class, 'RouterException');

/**
 * Exception class for CakeLog. This exception will be thrown from CakeLog when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class CakeLogException extends CakeException
{
}

class_alias(CakeLogException::class, 'CakeLogException');

/**
 * Exception class for CakeSession. This exception will be thrown from CakeSession when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class CakeSessionException extends CakeException
{
}

class_alias(CakeSessionException::class, 'CakeSessionException');

/**
 * Exception class for Configure. This exception will be thrown from Configure when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class ConfigureException extends CakeException
{
}

class_alias(ConfigureException::class, 'ConfigureException');

/**
 * Exception class for Socket. This exception will be thrown from CakeSocket, CakeEmail, HttpSocket
 * SmtpTransport, MailTransport and HttpResponse when it encounters an error.
 *
 * @package       Cake.Error
 */
class SocketException extends CakeException
{
}

class_alias(SocketException::class, 'SocketException');

/**
 * Exception class for Xml. This exception will be thrown from Xml when it
 * encounters an error.
 *
 * @package       Cake.Error
 */
class XmlException extends CakeException
{
}

class_alias(XmlException::class, 'XmlException');

/**
 * Exception class for Console libraries. This exception will be thrown from Console library
 * classes when they encounter an error.
 *
 * @package       Cake.Error
 */
class ConsoleException extends CakeException
{
}

class_alias(ConsoleException::class, 'ConsoleException');

/**
 * Represents a fatal error
 *
 * @package       Cake.Error
 */
class FatalErrorException extends CakeException
{
    /**
     * Constructor
     *
     * @param string $message The error message.
     * @param int $code The error code.
     * @param string $file The file the error occurred in.
     * @param int $line The line the error occurred on.
     */
    public function __construct($message, $code = 500, $file = null, $line = null)
    {
        parent::__construct($message, $code);
        if ($file) {
            $this->file = $file;
        }
        if ($line) {
            $this->line = $line;
        }
    }
}

class_alias(FatalErrorException::class, 'FatalErrorException');

/**
 * Not Implemented Exception - used when an API method is not implemented
 *
 * @package       Cake.Error
 */
class NotImplementedException extends CakeException
{
    protected $_messageTemplate = '%s is not implemented.';

//@codingStandardsIgnoreStart

    public function __construct($message, $code = 501)
    {
        parent::__construct($message, $code);
    }

//@codingStandardsIgnoreEnd
}

class_alias(NotImplementedException::class, 'NotImplementedException');

/**
 * Security exception - used when SecurityComponent detects any issue with the current request
 *
 * @package       Cake.Error
 */
class SecurityException extends BadRequestException
{
    /**
     * Security Exception type
     *
     * @var string
     */
    protected $_type = 'secure';

    /**
     * Reason for request blackhole
     *
     * @var string
     */
    protected $_reason = null;

    /**
     * Getter for type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set Message
     *
     * @param string $message Exception message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Set Reason
     *
     * @param string|null $reason Reason details
     * @return void
     */
    public function setReason($reason = null)
    {
        $this->_reason = $reason;
    }

    /**
     * Get Reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->_reason;
    }
}

class_alias(SecurityException::class, 'SecurityException');

/**
 * Auth Security exception - used when SecurityComponent detects any issue with the current request
 *
 * @package       Cake.Error
 */
class AuthSecurityException extends SecurityException
{
    /**
     * Security Exception type
     *
     * @var string
     */
    protected $_type = 'auth';
}

class_alias(AuthSecurityException::class, 'AuthSecurityException');
