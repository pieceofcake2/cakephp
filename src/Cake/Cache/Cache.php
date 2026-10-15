<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Cache provides a consistent interface to Caching in your application. It allows you
 * to use several different Cache engines, without coupling your application to a specific
 * implementation. It also allows you to change out cache storage or configuration without effecting
 * the rest of your application.
 *
 * You can configure Cache engines in your application's `bootstrap.php` file. A sample configuration would
 * be
 *
 * ```
 *  Cache::config('shared', array(
 *      'engine' => 'Apc',
 *      'prefix' => 'my_app_'
 *  ));
 * ```
 *
 * This would configure an APC cache engine to the 'shared' alias. You could then read and write
 * to that cache alias by using it for the `$config` parameter in the various Cache methods. In
 * general all Cache operations are supported by all cache engines. However, Cache::increment() and
 * Cache::decrement() are not supported by File caching.
 *
 * A configuration may set its engine with a DSN in the `'dsn'` key, for example
 * `Cache::config('default', ['dsn' => 'redis://localhost:6379?prefix=app_'])`.
 * See `parseDsn()` and `setDsnClassMap()`.
 *
 * @package       Cake.Cache
 */
class Cache
{
    /**
     * Cache configuration stack
     * Keeps the permanent/default settings for each cache engine.
     * These settings are used to reset the engines after temporary modification.
     *
     * @var array
     */
    protected static $_config = [];

    /**
     * Group to Config mapping
     *
     * @var array
     */
    protected static $_groups = [];

    /**
     * Whether to reset the settings with the next call to Cache::set();
     *
     * @var bool
     */
    protected static $_reset = false;

    /**
     * Engine instances keyed by configuration name.
     *
     * @var array
     */
    protected static $_engines = [];

    /**
     * The cache DSN class map (scheme => engine class name or callable).
     * `null` until `getDsnClassMap()` sets the built-in schemes.
     *
     * @var array<string, mixed>|null
     */
    protected static $_dsnClassMap = null;

    /**
     * Set the cache configuration to use. config() can
     * both create new configurations, return the settings for already configured
     * configurations.
     *
     * To create a new configuration, or to modify an existing configuration permanently:
     *
     * `Cache::config('my_config', array('engine' => 'File', 'path' => TMP));`
     *
     * If you need to modify a configuration temporarily, use Cache::set().
     * To get the settings for a configuration:
     *
     * `Cache::config('default');`
     *
     * There are 5 built-in caching engines:
     *
     * - `FileEngine` - Uses simple files to store content. Poor performance, but good for
     *    storing large objects, or things that are not IO sensitive.
     * - `ApcEngine` - Uses the APC object cache, one of the fastest caching engines.
     * - `MemcacheEngine` - Uses the PECL::Memcache extension and Memcached for storage.
     *   Fast reads/writes, and benefits from memcache being distributed.
     * - `XcacheEngine` - Uses the Xcache extension, an alternative to APC.
     * - `WincacheEngine` - Uses Windows Cache Extension for PHP. Supports wincache 1.1.0 and higher.
     *
     * The following keys are used in core cache engines:
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     * - `servers' Used by memcache. Give the address of the memcached servers to use.
     * - `compress` Used by memcache. Enables memcache's compressed format.
     * - `serialize` Used by FileCache. Should cache objects be serialized first.
     * - `path` Used by FileCache. Path to where cachefiles should be saved.
     * - `lock` Used by FileCache. Should files be locked before writing to them?
     * - `user` Used by Xcache. Username for XCache
     * - `password` Used by Xcache/Redis. Password for XCache/Redis
     * - `dsn` A DSN that sets the engine and its connection, e.g.
     *   `redis://:secret@localhost:6379/0?prefix=app_`. See `parseDsn()`. The
     *   keys parsed from the DSN replace the same keys of the settings, and an
     *   empty `dsn` (`null`, `''` or `false`) is ignored.
     *
     * @param string $name Name of the configuration
     * @param array $settings Optional associative array of settings passed to the engine
     * @return array|false array(engine, settings) on success, false on failure
     * @throws CacheException
     * @see app/Config/core.php for configuration settings
     */
    public static function config($name = null, $settings = [])
    {
        if (is_array($name)) {
            $settings = $name;
        }

        $current = [];
        if (isset(static::$_config[$name])) {
            $current = static::$_config[$name];
        }

        if (!empty($settings)) {
            $settings = CakeDsn::expandConfig(
                $settings,
                [static::class, 'parseDsn'],
                sprintf('the "%s" cache configuration', is_string($name) ? $name : ''),
                'CacheException',
            );
            static::$_config[$name] = $settings + $current;
        }

        if (empty(static::$_config[$name]['engine'])) {
            return false;
        }

        if (!empty(static::$_config[$name]['groups'])) {
            foreach (static::$_config[$name]['groups'] as $group) {
                static::$_groups[$group][] = $name;
                sort(static::$_groups[$group]);
                static::$_groups[$group] = array_unique(static::$_groups[$group]);
            }
        }

        $engine = static::$_config[$name]['engine'];

        if (!isset(static::$_engines[$name])) {
            static::_buildEngine($name);
            $settings = static::$_config[$name] = static::settings($name);
        } elseif ($settings = static::set(static::$_config[$name], null, $name)) {
            static::$_config[$name] = $settings;
        }

        return compact('engine', 'settings');
    }

    /**
     * Adds cache DSN schemes, or replaces built-in ones, for the `'dsn'` key of
     * cache configurations.
     *
     * A plugin registers its schemes in its `Config/bootstrap.php`:
     *
     * ```
     * Cache::setDsnClassMap([
     *     // mycache://user:pass@host:port/path uses MyPlugin.MyCache with the keys
     *     // username, password, host, port, path and the query parameters
     *     'mycache' => 'MyPlugin.MyCache',
     *     // a callable returns the configuration keys itself
     *     'othercache' => function (array $parts, array $config) {
     *         return ['engine' => 'MyPlugin.Other', 'endpoint' => $parts['host'] ?? null] + $parts['query'];
     *     },
     * ]);
     * ```
     *
     * A value is either an engine class name in plugin dot syntax (as in the
     * `engine` key), or a callable (not a string)
     * `function (array $parts, array $config): array` that gets the parts of
     * `CakeDsn::parse()` and the configuration without the `'dsn'` key and
     * returns the configuration keys. `null` removes a scheme. Schemes are
     * case-insensitive.
     *
     * `Cache::config()` builds the engine at once, so a configuration that uses
     * a scheme of a plugin must come after the plugin is loaded.
     *
     * @param array<string, mixed> $map Scheme => engine class name or callable.
     * @return void
     */
    public static function setDsnClassMap($map)
    {
        static::$_dsnClassMap = CakeDsn::mergeClassMap($map, static::getDsnClassMap());
    }

    /**
     * Returns the cache DSN class map (scheme => engine class name or callable).
     *
     * @return array<string, mixed>
     */
    public static function getDsnClassMap()
    {
        if (static::$_dsnClassMap === null) {
            static::$_dsnClassMap = static::_defaultDsnClassMap();
        }

        return static::$_dsnClassMap;
    }

    /**
     * Returns the built-in cache DSN schemes.
     *
     * @return array<string, mixed>
     */
    protected static function _defaultDsnClassMap()
    {
        $file = function ($parts) {
            $config = ['engine' => 'File'];
            if (isset($parts['path']) && $parts['path'] !== '') {
                $config['path'] = $parts['path'];
            }

            return $parts['query'] + $config;
        };
        $apc = function ($parts) {
            return $parts['query'] + ['engine' => 'Apc'];
        };
        $memcached = function ($parts) {
            $config = ['engine' => $parts['scheme'] === 'memcache' ? 'Memcache' : 'Memcached'];
            if (isset($parts['host'])) {
                $config['servers'] = [$parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '')];
            } elseif (isset($parts['path']) && $parts['path'] !== '') {
                $config['servers'] = ['unix://' . $parts['path']];
            }
            if ($config['engine'] === 'Memcached' && isset($parts['username']) && $parts['username'] !== '') {
                $config['login'] = $parts['username'];
                $config['password'] = $parts['password'] ?? '';
            }

            return $parts['query'] + $config;
        };
        $redis = function ($parts) {
            $config = ['engine' => 'Redis'];
            if (isset($parts['host'])) {
                $config['server'] = trim($parts['host'], '[]');
                if (isset($parts['path']) && preg_match('#^/(\d+)/?$#', $parts['path'], $match)) {
                    $config['database'] = (int)$match[1];
                }
            } elseif (isset($parts['path']) && $parts['path'] !== '') {
                $config['unix_socket'] = $parts['path'];
            }
            if (isset($parts['port'])) {
                $config['port'] = $parts['port'];
            }
            if (isset($parts['password']) && $parts['password'] !== '') {
                $config['password'] = isset($parts['username']) && $parts['username'] !== ''
                    ? [$parts['username'], $parts['password']]
                    : $parts['password'];
            }
            $query = $parts['query'];
            if (isset($query['database']) && is_string($query['database']) && ctype_digit($query['database'])) {
                $query['database'] = (int)$query['database'];
            }

            return $query + $config;
        };

        return [
            'file' => $file,
            'apc' => $apc,
            'apcu' => $apc,
            'memcached' => $memcached,
            'memcache' => $memcached,
            'redis' => $redis,
        ];
    }

    /**
     * Parses a cache DSN into cache configuration keys.
     *
     * ```
     * Cache::parseDsn('redis://:secret@localhost:6379/1?prefix=app_&duration=3600');
     * // ['engine' => 'Redis', 'server' => 'localhost', 'port' => 6379, 'password' => 'secret',
     * //  'database' => 1, 'prefix' => 'app_', 'duration' => '3600']
     * ```
     *
     * The scheme is looked up in the DSN class map (see `setDsnClassMap()`).
     * The built-in schemes are:
     *
     * | Scheme | Configuration |
     * | --- | --- |
     * | `file:///path/to/cache/` | `File` engine, `path` |
     * | `apcu://`, `apc://` | `Apc` engine |
     * | `memcached://user:pass@host:11211`, `memcached:///path/to/socket` | `Memcached` engine, `servers`, `login`, `password` |
     * | `memcache://host:11211` | `Memcache` engine, `servers` |
     * | `redis://user:pass@host:6379/1`, `redis:///path/to/socket` | `Redis` engine, `server`, `port`, `password` (`[user, password]` with a user name), `database` (the path or `?database=`), `unix_socket` |
     *
     * - The user, password, host and path are URL-decoded.
     * - Query parameters become configuration keys as they are (`prefix`,
     *   `duration`, `groups[]`, `probability`, `persistent`, `timeout`, ...).
     *   `true`, `false` and `null` are converted to the PHP values. Encode a
     *   `+` in a duration (`duration=%2B1%20hours`) or use seconds.
     * - For an engine class name in the map, the user, password, host, port
     *   and path become `username`, `password`, `host`, `port` and `path`, as
     *   in CakePHP 3.x and later. An `engine` query parameter overrides a class
     *   name and allows schemes that are not in the map.
     * - A callable in the map returns the configuration keys itself.
     *
     * @param string $dsn The DSN.
     * @param array $config The configuration the DSN is merged into (passed to a callable of the map).
     * @return array The cache configuration.
     * @throws CacheException When the DSN cannot be parsed or its scheme is not supported.
     */
    public static function parseDsn($dsn, $config = [])
    {
        try {
            return CakeDsn::toConfig($dsn, static::getDsnClassMap(), 'engine', [CakeDsn::class, 'genericConfig'], $config);
        } catch (CacheException $e) {
            throw $e;
        } catch (CakeException $e) {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * Finds and builds the instance of the required engine class.
     *
     * @param string $name Name of the config array that needs an engine instance built
     * @return bool
     * @throws CacheException
     */
    protected static function _buildEngine($name)
    {
        $config = static::$_config[$name];

        [$plugin, $class] = pluginSplit($config['engine'], true);
        $cacheClass = $class . 'Engine';
        App::uses($cacheClass, $plugin . 'Cache/Engine');
        if (!class_exists($cacheClass)) {
            throw new CacheException(__d('cake_dev', 'Cache engine %s is not available.', $name));
        }
        if (!is_subclass_of($cacheClass, 'CacheEngine')) {
            throw new CacheException(__d('cake_dev', 'Cache engines must use %s as a base class.', 'CacheEngine'));
        }
        static::$_engines[$name] = new $cacheClass();
        if (!static::$_engines[$name]->init($config)) {
            $msg = __d(
                'cake_dev',
                'Cache engine "%s" is not properly configured. Ensure required extensions are installed, and credentials/permissions are correct',
                $name,
            );
            throw new CacheException($msg);
        }
        if (static::$_engines[$name]->settings['probability'] && time() % static::$_engines[$name]->settings['probability'] === 0) {
            static::$_engines[$name]->gc();
        }

        return true;
    }

    /**
     * Returns an array containing the currently configured Cache settings.
     *
     * @return array Array of configured Cache config names.
     */
    public static function configured()
    {
        return array_keys(static::$_config);
    }

    /**
     * Drops a cache engine. Deletes the cache configuration information
     * If the deleted configuration is the last configuration using a certain engine,
     * the Engine instance is also unset.
     *
     * @param string $name A currently configured cache config you wish to remove.
     * @return bool success of the removal, returns false when the config does not exist.
     */
    public static function drop($name)
    {
        if (!isset(static::$_config[$name])) {
            return false;
        }
        unset(static::$_config[$name], static::$_engines[$name]);

        return true;
    }

    /**
     * Temporarily change the settings on a cache config. The settings will persist for the next write
     * operation (write, decrement, increment, clear). Any reads that are done before the write, will
     * use the modified settings. If `$settings` is empty, the settings will be reset to the
     * original configuration.
     *
     * Can be called with 2 or 3 parameters. To set multiple values at once.
     *
     * `Cache::set(array('duration' => '+30 minutes'), 'my_config');`
     *
     * Or to set one value.
     *
     * `Cache::set('duration', '+30 minutes', 'my_config');`
     *
     * To reset a config back to the originally configured values.
     *
     * `Cache::set(null, 'my_config');`
     *
     * @param array|string|null $settings Optional string for simple name-value pair or array
     * @param string $value Optional for a simple name-value pair
     * @param string $config The configuration name you are changing. Defaults to 'default'
     * @return array|false Array of settings.
     */
    public static function set($settings = [], $value = null, $config = 'default')
    {
        if (is_array($settings) && $value !== null) {
            $config = $value;
        }
        if (!isset(static::$_config[$config]) || !isset(static::$_engines[$config])) {
            return false;
        }
        if (!empty($settings)) {
            static::$_reset = true;
        }

        if (static::$_reset === true) {
            if (empty($settings)) {
                static::$_reset = false;
                $settings = static::$_config[$config];
            } else {
                if (is_string($settings) && $value !== null) {
                    $settings = [$settings => $value];
                }
                $settings += static::$_config[$config];
                if (isset($settings['duration']) && !is_numeric($settings['duration'])) {
                    $settings['duration'] = strtotime($settings['duration']) - time();
                }
            }
            static::$_engines[$config]->settings = $settings;
        }

        return static::settings($config);
    }

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @param string $config [optional] The config name you wish to have garbage collected. Defaults to 'default'
     * @param int $expires [optional] An expires timestamp. Defaults to NULL
     * @return bool
     */
    public static function gc($config = 'default', $expires = null)
    {
        return static::$_engines[$config]->gc($expires);
    }

    /**
     * Write data for key into a cache engine.
     *
     * ### Usage:
     *
     * Writing to the active cache config:
     *
     * `Cache::write('cached_data', $data);`
     *
     * Writing to a specific cache config:
     *
     * `Cache::write('cached_data', $data, 'long_term');`
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached - anything except a resource
     * @param string $config Optional string configuration name to write to. Defaults to 'default'
     * @return bool True if the data was successfully cached, false on failure
     */
    public static function write($key, $value, $config = 'default')
    {
        $settings = static::settings($config);

        if (empty($settings)) {
            return false;
        }
        if (!static::isInitialized($config)) {
            return false;
        }
        $key = static::$_engines[$config]->key($key);

        if (!$key || is_resource($value)) {
            return false;
        }

        $success = static::$_engines[$config]->write($settings['prefix'] . $key, $value, $settings['duration']);
        static::set(null, $config);
        if ($success === false && $value !== '') {
            trigger_error(
                __d(
                    'cake_dev',
                    "%s cache was unable to write '%s' to %s cache",
                    $config,
                    $key,
                    static::$_engines[$config]->settings['engine'],
                ),
                E_USER_WARNING,
            );
        }

        return $success;
    }

    /**
     * Read a key from a cache config.
     *
     * ### Usage:
     *
     * Reading from the active cache configuration.
     *
     * `Cache::read('my_data');`
     *
     * Reading from a specific cache configuration.
     *
     * `Cache::read('my_data', 'long_term');`
     *
     * @param string $key Identifier for the data
     * @param string $config optional name of the configuration to use. Defaults to 'default'
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    public static function read($key, $config = 'default')
    {
        $settings = static::settings($config);

        if (empty($settings)) {
            return false;
        }
        if (!static::isInitialized($config)) {
            return false;
        }
        $key = static::$_engines[$config]->key($key);
        if (!$key) {
            return false;
        }

        return static::$_engines[$config]->read($settings['prefix'] . $key);
    }

    /**
     * Increment a number under the key and return incremented value.
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @param string $config Optional string configuration name. Defaults to 'default'
     * @return mixed new value, or false if the data doesn't exist, is not integer,
     *    or if there was an error fetching it.
     */
    public static function increment($key, $offset = 1, $config = 'default')
    {
        $settings = static::settings($config);

        if (empty($settings)) {
            return false;
        }
        if (!static::isInitialized($config)) {
            return false;
        }
        $key = static::$_engines[$config]->key($key);

        if (!$key || !is_int($offset) || $offset < 0) {
            return false;
        }
        $success = static::$_engines[$config]->increment($settings['prefix'] . $key, $offset);
        static::set(null, $config);

        return $success;
    }

    /**
     * Decrement a number under the key and return decremented value.
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @param string $config Optional string configuration name. Defaults to 'default'
     * @return mixed new value, or false if the data doesn't exist, is not integer,
     *   or if there was an error fetching it
     */
    public static function decrement($key, $offset = 1, $config = 'default')
    {
        $settings = static::settings($config);

        if (empty($settings)) {
            return false;
        }
        if (!static::isInitialized($config)) {
            return false;
        }
        $key = static::$_engines[$config]->key($key);

        if (!$key || !is_int($offset) || $offset < 0) {
            return false;
        }
        $success = static::$_engines[$config]->decrement($settings['prefix'] . $key, $offset);
        static::set(null, $config);

        return $success;
    }

    /**
     * Delete a key from the cache.
     *
     * ### Usage:
     *
     * Deleting from the active cache configuration.
     *
     * `Cache::delete('my_data');`
     *
     * Deleting from a specific cache configuration.
     *
     * `Cache::delete('my_data', 'long_term');`
     *
     * @param string $key Identifier for the data
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public static function delete($key, $config = 'default')
    {
        $settings = static::settings($config);

        if (empty($settings)) {
            return false;
        }
        if (!static::isInitialized($config)) {
            return false;
        }
        $key = static::$_engines[$config]->key($key);
        if (!$key) {
            return false;
        }

        $success = static::$_engines[$config]->delete($settings['prefix'] . $key);
        static::set(null, $config);

        return $success;
    }

    /**
     * Delete all keys from the cache.
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public static function clear($check = false, $config = 'default')
    {
        if (!static::isInitialized($config)) {
            return false;
        }
        $success = static::$_engines[$config]->clear($check);
        static::set(null, $config);

        return $success;
    }

    /**
     * Delete all keys from the cache belonging to the same group.
     *
     * @param string $group name of the group to be cleared
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the cache group was successfully cleared, false otherwise
     */
    public static function clearGroup($group, $config = 'default')
    {
        if (!static::isInitialized($config)) {
            return false;
        }
        $success = static::$_engines[$config]->clearGroup($group);
        static::set(null, $config);

        return $success;
    }

    /**
     * Check if Cache has initialized a working config for the given name.
     *
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool Whether or not the config name has been initialized.
     */
    public static function isInitialized($config = 'default')
    {
        if (Configure::read('Cache.disable')) {
            return false;
        }

        return isset(static::$_engines[$config]);
    }

    /**
     * Return the settings for the named cache engine.
     *
     * @param string $name Name of the configuration to get settings for. Defaults to 'default'
     * @return array list of settings for this engine
     * @see Cache::config()
     */
    public static function settings($name = 'default')
    {
        if (!empty(static::$_engines[$name])) {
            return static::$_engines[$name]->settings();
        }

        return [];
    }

    /**
     * Retrieve group names to config mapping.
     *
     * ```
     *  Cache::config('daily', array(
     *      'duration' => '1 day', 'groups' => array('posts')
     *  ));
     *  Cache::config('weekly', array(
     *      'duration' => '1 week', 'groups' => array('posts', 'archive')
     *  ));
     *  $configs = Cache::groupConfigs('posts');
     * ```
     *
     * $config will equal to `array('posts' => array('daily', 'weekly'))`
     *
     * @param string $group group name or null to retrieve all group mappings
     * @return array map of group and all configuration that has the same group
     * @throws CacheException
     */
    public static function groupConfigs($group = null)
    {
        if ($group === null) {
            return static::$_groups;
        }
        if (isset(static::$_groups[$group])) {
            return [$group => static::$_groups[$group]];
        }
        throw new CacheException(__d('cake_dev', 'Invalid cache group %s', $group));
    }

    /**
     * Provides the ability to easily do read-through caching.
     *
     * When called if the $key is not set in $config, the $callable function
     * will be invoked. The results will then be stored into the cache config
     * at key.
     *
     * Examples:
     *
     * Using a Closure to provide data, assume $this is a Model:
     *
     * ```
     * $model = $this;
     * $results = Cache::remember('all_articles', function() use ($model) {
     *      return $model->find('all');
     * });
     * ```
     *
     * @param string $key The cache key to read/store data at.
     * @param callable $callable The callable that provides data in the case when
     *   the cache key is empty. Can be any callable type supported by your PHP.
     * @param string $config The cache configuration to use for this operation.
     *   Defaults to default.
     * @return mixed The results of the callable or unserialized results.
     */
    public static function remember($key, $callable, $config = 'default')
    {
        $existing = static::read($key, $config);
        if ($existing !== false) {
            return $existing;
        }
        $results = call_user_func($callable);
        static::write($key, $results, $config);

        return $results;
    }

    /**
     * Write data for key into a cache engine if it doesn't exist already.
     *
     * ### Usage:
     *
     * Writing to the active cache config:
     *
     * `Cache::add('cached_data', $data);`
     *
     * Writing to a specific cache config:
     *
     * `Cache::add('cached_data', $data, 'long_term');`
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached - anything except a resource.
     * @param string $config Optional string configuration name to write to. Defaults to 'default'.
     * @return bool True if the data was successfully cached, false on failure.
     *   Or if the key existed already.
     */
    public static function add($key, $value, $config = 'default')
    {
        $settings = self::settings($config);

        if (empty($settings)) {
            return false;
        }
        if (!self::isInitialized($config)) {
            return false;
        }
        $key = self::$_engines[$config]->key($key);

        if (!$key || is_resource($value)) {
            return false;
        }

        $success = self::$_engines[$config]->add($settings['prefix'] . $key, $value, $settings['duration']);
        self::set(null, $config);

        return $success;
    }

    /**
     * Fetch the engine attached to a specific configuration name.
     *
     * @param string $config Optional string configuration name to get an engine for. Defaults to 'default'.
     * @return CacheEngine|null Null if the engine has not been initialized or the engine.
     */
    public static function engine($config = 'default')
    {
        if (self::isInitialized($config)) {
            return self::$_engines[$config];
        }

        return null;
    }
}
