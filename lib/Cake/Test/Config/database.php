<?php
class DATABASE_CONFIG
{
    private $identities = [
        'mysql' => [
            'datasource' => 'Database/Mysql',
            'host' => '127.0.0.1',
            'login' => 'root',
            'password' => 'root',
            'encoding' => 'utf8',
        ],
        'mysql80' => [
            'datasource' => 'Database/Mysql',
            'host' => '127.0.0.1',
            'port' => '3307',
            'login' => 'root',
            'password' => 'root',
            'encoding' => 'utf8mb4',
        ],
        'pgsql' => [
            'datasource' => 'Database/Postgres',
            'host' => '127.0.0.1',
            'login' => 'postgres',
            'password' => 'postgres',
            'database' => 'cakephp_test',
            'schema' => [
                'default' => 'public',
                'test' => 'public',
                'test2' => 'test2',
                'test_database_three' => 'test3',
            ],
        ],
        'sqlite' => [
            'datasource' => 'Database/Sqlite',
            'database' => [
                'default' => ':memory:',
                'test' => ':memory:',
                'test2' => '/tmp/cakephp_test2.db',
                'test_database_three' => '/tmp/cakephp_test3.db',
            ],
        ],
        'sqlsrv' => [
            'datasource' => 'Database/Sqlserver',
            'host' => '127.0.0.1',
            'login' => 'sa',
            'password' => 'Password123!',
            'database' => [
                'default' => 'cakephp_test',
                'test' => 'cakephp_test',
                'test2' => 'cakephp_test2',
                'test_database_three' => 'cakephp_test3',
            ],
            'encoding' => 'utf8',
            'options' => [
                'TrustServerCertificate' => 'yes',
                'Encrypt' => 'no',
            ],
        ],
    ];

    public $default = [
        'persistent' => false,
        'host' => '',
        'login' => '',
        'password' => '',
        'database' => 'cakephp_test',
        'prefix' => '',
    ];
    public $test = [
        'persistent' => false,
        'host' => '',
        'login' => '',
        'password' => '',
        'database' => 'cakephp_test',
        'prefix' => '',
    ];
    public $test2 = [
        'persistent' => false,
        'host' => '',
        'login' => '',
        'password' => '',
        'database' => 'cakephp_test2',
        'prefix' => '',
    ];
    public $test_database_three = [
        'persistent' => false,
        'host' => '',
        'login' => '',
        'password' => '',
        'database' => 'cakephp_test3',
        'prefix' => '',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $db = 'mysql';
        if (!empty($_SERVER['DB'])) {
            $db = $_SERVER['DB'];
        }
        foreach (['default', 'test', 'test2', 'test_database_three'] as $source) {
            $config = array_merge($this->{$source}, $this->identities[$db]);
            if (is_array($config['database'])) {
                $config['database'] = $config['database'][$source];
            }
            if (!empty($config['schema']) && is_array($config['schema'])) {
                $config['schema'] = $config['schema'][$source];
            }
            $this->{$source} = $config;
        }
    }
}
