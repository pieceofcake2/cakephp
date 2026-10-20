<?php
/**
 * CakeDsnTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Utility
 * @since         2.12.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CakeDsnTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class CakeDsnTest extends CakeTestCase
{
    /**
     * Data provider for testParse
     *
     * @return array
     */
    public static function parseProvider(): array
    {
        return [
            'full' => [
                'mysql://user:secret@localhost:3306/app?encoding=utf8mb4#ignored',
                ['scheme' => 'mysql', 'username' => 'user', 'password' => 'secret', 'host' => 'localhost', 'port' => 3306, 'path' => '/app', 'query' => ['encoding' => 'utf8mb4']],
            ],
            'scheme only' => [
                'null://',
                ['scheme' => 'null', 'query' => []],
            ],
            'path only' => [
                'sqlite:///:memory:',
                ['scheme' => 'sqlite', 'path' => '/:memory:', 'query' => []],
            ],
            'user name with @' => [
                'smtp://me@example.com:secret@smtp.example.com:587',
                ['scheme' => 'smtp', 'username' => 'me@example.com', 'password' => 'secret', 'host' => 'smtp.example.com', 'port' => 587, 'query' => []],
            ],
            'password with unencoded reserved characters' => [
                'mysql://user:p@s:s/w#rd@db/app',
                ['scheme' => 'mysql', 'username' => 'user', 'password' => 'p@s:s/w#rd', 'host' => 'db', 'path' => '/app', 'query' => []],
            ],
            'password with encoded characters' => [
                'mysql://user:p%3Fss%2Bw%20rd+@db/app',
                ['scheme' => 'mysql', 'username' => 'user', 'password' => 'p?ss+w rd+', 'host' => 'db', 'path' => '/app', 'query' => []],
            ],
            '@ in query' => [
                'native://default?additionalParameters=-fme@example.com',
                ['scheme' => 'native', 'host' => 'default', 'query' => ['additionalParameters' => '-fme@example.com']],
            ],
            'ipv6' => [
                'smtp://[::1]:25',
                ['scheme' => 'smtp', 'host' => '[::1]', 'port' => 25, 'query' => []],
            ],
            'query values' => [
                'x://h?a=true&b=false&c=null&d=1&e[f]=g&h=',
                ['scheme' => 'x', 'host' => 'h', 'query' => ['a' => true, 'b' => false, 'c' => null, 'd' => '1', 'e' => ['f' => 'g'], 'h' => '']],
            ],
        ];
    }

    /**
     * Test parse()
     *
     * @param string $dsn DSN
     * @param array $expected Expected parts
     * @return void
     * @dataProvider parseProvider
     */
    public function testParse($dsn, $expected): void
    {
        $this->assertSame($expected, CakeDsn::parse($dsn));
    }

    /**
     * Test parse() with invalid DSNs
     *
     * @return void
     */
    public function testParseInvalid(): void
    {
        foreach (['', 'localhost', 'mysql:/user:secret@host', 'mysql://host:port/db'] as $dsn) {
            try {
                CakeDsn::parse($dsn);
                $this->fail('No exception for ' . $dsn);
            } catch (CakeException $e) {
                $this->assertStringContainsString('could not be parsed', $e->getMessage());
                $this->assertStringNotContainsString('secret', $e->getMessage());
            }
        }
    }

    /**
     * Test redact()
     *
     * @return void
     */
    public function testRedact(): void
    {
        $this->assertSame('mysql://****@db/app', CakeDsn::redact('mysql://user:p@ss@db/app'));
        $this->assertSame('mysql:****@db/app', CakeDsn::redact('mysql:/user:secret@db/app'));
        $this->assertSame('sqlite:///:memory:', CakeDsn::redact('sqlite:///:memory:'));
    }

    /**
     * Test toConfig() with class names, callables, overrides and unknown schemes
     *
     * @return void
     */
    public function testToConfig(): void
    {
        $generic = function (array $parts) {
            $config = [];
            foreach (['username', 'password', 'host', 'port', 'path'] as $key) {
                if (isset($parts[$key])) {
                    $config[$key] = $parts[$key];
                }
            }

            return $parts['query'] + $config;
        };
        $map = [
            'thing' => 'MyPlugin.Thing',
            'other' => function (array $parts, array $config) {
                return ['class' => 'Other', 'host' => $parts['host'], 'seen' => $config['seen']];
            },
        ];

        $this->assertSame(
            ['a' => '1', 'username' => 'u', 'password' => 'p', 'host' => 'h', 'port' => 1, 'path' => '/x', 'class' => 'MyPlugin.Thing'],
            CakeDsn::toConfig('thing://u:p@h:1/x?a=1', $map, 'class', $generic),
        );
        $this->assertSame(
            ['class' => 'Override', 'host' => 'h'],
            CakeDsn::toConfig('thing://h?class=Override', $map, 'class', $generic),
        );
        $this->assertSame(
            ['class' => 'Other', 'host' => 'h', 'seen' => true],
            CakeDsn::toConfig('OTHER://h', $map, 'class', $generic, ['seen' => true]),
        );
        $this->assertSame(
            ['class' => 'Custom', 'host' => 'h'],
            CakeDsn::toConfig('unknown://h?class=Custom', $map, 'class', $generic),
        );

        try {
            CakeDsn::toConfig('unknown://u:secret@h', $map, 'class', $generic);
            $this->fail('No exception thrown');
        } catch (CakeException $e) {
            $this->assertStringContainsString('The DSN scheme "unknown" is not supported. Use one of thing, other', $e->getMessage());
            $this->assertStringNotContainsString('secret', $e->getMessage());
        }

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('neither a class name nor a callable');
        CakeDsn::toConfig('bad://h', ['bad' => 42], 'class', $generic);
    }

    /**
     * Test mergeClassMap()
     *
     * @return void
     */
    public function testMergeClassMap(): void
    {
        $this->assertSame(
            ['a' => 'A2', 'c' => 'C', 'my+api' => 'M'],
            CakeDsn::mergeClassMap(['A' => 'A2', 'b' => null, 'My+Api' => 'M'], ['a' => 'A', 'b' => 'B', 'c' => 'C']),
        );
    }

    /**
     * Test expandConfig()
     *
     * @return void
     */
    public function testExpandConfig(): void
    {
        $parser = function ($dsn, $config) {
            if ($dsn === 'bad') {
                throw new CakeException('Broken');
            }

            return ['host' => $dsn, 'had' => $config['keep']];
        };

        $this->assertSame(['host' => 'h', 'had' => 1, 'keep' => 1], CakeDsn::expandConfig(['dsn' => 'h', 'host' => 'old', 'keep' => 1], $parser, 'x'));
        foreach ([null, '', false] as $empty) {
            $this->assertSame(['host' => 'old'], CakeDsn::expandConfig(['dsn' => $empty, 'host' => 'old'], $parser, 'x'));
        }
        $this->assertSame(['host' => 'old'], CakeDsn::expandConfig(['host' => 'old'], $parser, 'x'));
        $this->assertSame('name', CakeDsn::expandConfig('name', $parser, 'x'));

        $this->expectException(ConfigureException::class);
        $this->expectExceptionMessage('Invalid "dsn" of the "a" thing: Broken');
        CakeDsn::expandConfig(['dsn' => 'bad'], $parser, 'the "a" thing', 'ConfigureException');
    }
}
