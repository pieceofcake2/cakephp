<?php

declare(strict_types=1);

/**
 * CakeDotenv subclass with configurable default files
 *
 * @package       Cake.Test.Case.Core
 */
class TestCakeDotenv extends CakeDotenv
{
    /**
     * @var array<string>
     */
    public static $files = [];

    /**
     * @return array<string>
     */
    public static function defaultFiles(): array
    {
        return static::$files;
    }
}

/**
 * CakeDotenvTest
 *
 * Parser tests are partly adapted from vlucas/phpdotenv (tests/Dotenv/DotenvTest.php,
 * tests/Dotenv/Parser/EntryParserTest.php and tests/fixtures/env), which is
 * BSD-3-Clause licensed. See tests/test_app/Config/dotenv/LICENSE-phpdotenv.
 *
 * @package       Cake.Test.Case.Core
 */
class CakeDotenvTest extends CakeTestCase
{
    /**
     * @var string
     */
    protected $fixtures;

    /**
     * @var string
     */
    protected $tmpDir;

    /**
     * @var array<string, string>
     */
    protected $originalEnv = [];

    /**
     * @var array<string, mixed>
     */
    protected $originalEnvGlobal = [];

    /**
     * @var array<string, mixed>
     */
    protected $originalServer = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->fixtures = CORE_TESTS . DS . 'test_app' . DS . 'Config' . DS . 'dotenv' . DS;
        $this->tmpDir = TMP . 'tests' . DS . 'dotenv_' . uniqid() . DS;
        mkdir($this->tmpDir, 0777, true);
        $this->originalEnv = getenv();
        $this->originalEnvGlobal = $_ENV;
        $this->originalServer = $_SERVER;
        TestCakeDotenv::$files = [];
    }

    /**
     * tearDown method
     *
     * Restores putenv(), $_ENV and $_SERVER.
     *
     * @return void
     */
    public function tearDown(): void
    {
        foreach (array_keys(getenv()) as $name) {
            if (!array_key_exists($name, $this->originalEnv)) {
                putenv((string)$name);
            }
        }
        foreach ($this->originalEnv as $name => $value) {
            if (getenv((string)$name) !== $value) {
                putenv($name . '=' . $value);
            }
        }
        $_ENV = $this->originalEnvGlobal;
        $_SERVER = $this->originalServer;

        foreach (glob($this->tmpDir . '{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        foreach (glob($this->tmpDir . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            foreach (glob($dir . DS . '{,.}*', GLOB_BRACE) ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
        rmdir($this->tmpDir);
        TestCakeDotenv::$files = [];
        parent::tearDown();
    }

    /**
     * Writes a file into the temporary directory.
     *
     * @param string $name File name, relative to the temporary directory.
     * @param string $contents File contents.
     * @return string Absolute path.
     */
    protected function writeFile(string $name, string $contents): string
    {
        $path = $this->tmpDir . $name;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * Asserts that a variable is not set anywhere.
     *
     * @param string $name Variable name.
     * @return void
     */
    protected function assertEnvNotSet(string $name): void
    {
        $this->assertFalse(getenv($name));
        $this->assertArrayNotHasKey($name, $_ENV);
        $this->assertArrayNotHasKey($name, $_SERVER);
    }

    /**
     * Asserts that a variable has a value everywhere.
     *
     * @param string $name Variable name.
     * @param string $value Expected value.
     * @return void
     */
    protected function assertEnvSame(string $name, string $value): void
    {
        $this->assertSame($value, getenv($name));
        $this->assertSame($value, $_ENV[$name] ?? null);
        $this->assertSame($value, $_SERVER[$name] ?? null);
        $this->assertSame($value, env($name));
    }

    /**
     * Asserts that parsing fails with a message.
     *
     * @param string $contents The contents.
     * @param string $message Expected message.
     * @return void
     */
    protected function assertParseError(string $contents, string $message): void
    {
        try {
            CakeDotenv::parse($contents);
            $this->fail('Expected CakeDotenvException was not thrown.');
        } catch (CakeDotenvException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function testParseBasic(): void
    {
        $this->assertSame(['FOO' => 'BAR'], CakeDotenv::parse('FOO=BAR'));
        $this->assertSame(
            ['FOO' => 'bar', 'BAZ' => 'qux'],
            CakeDotenv::parse("FOO=bar\nBAZ=qux\n"),
        );
    }

    public function testParseEmptyContents(): void
    {
        $this->assertSame([], CakeDotenv::parse(''));
        $this->assertSame([], CakeDotenv::parse("\n\n  \n\t\n"));
        $this->assertSame([], CakeDotenv::parse("# only a comment\n## another ##\n"));
    }

    public function testParseCommentsAndBlankLines(): void
    {
        $contents = "# comment\n\nA=1\n   # indented comment\n\t# tab indented\nB=2\n#C=3\n";
        $this->assertSame(['A' => '1', 'B' => '2'], CakeDotenv::parse($contents));
    }

    public function testParseLineEndings(): void
    {
        $this->assertSame(['A' => '1', 'B' => '2', 'C' => '3'], CakeDotenv::parse("A=1\r\nB=2\rC=3"));
    }

    public function testParseByteOrderMark(): void
    {
        $this->assertSame(['KEY' => '1'], CakeDotenv::parse("\xEF\xBB\xBFKEY=1"));
        $this->assertSame(['A' => '2'], CakeDotenv::parse("\xEF\xBB\xBF# c\nA=2"));
        $this->assertSame(['B' => '3'], CakeDotenv::parse("\xEF\xBB\xBF\nB=3"));
    }

    public function testParseExport(): void
    {
        $this->assertSame(['FOO' => 'bar baz'], CakeDotenv::parse('export FOO="bar baz"'));
        $this->assertSame(['FOO' => 'bar baz'], CakeDotenv::parse("export\t\"FOO\"='bar baz'"));
        $this->assertSame(['A' => '1'], CakeDotenv::parse('export A=1'));
        $this->assertSame(['A' => '1'], CakeDotenv::parse('export    A=1'));
        $this->assertSame(['exportFOO' => 'bar'], CakeDotenv::parse('exportFOO=bar'));
        $this->assertSame(['export' => 'bar'], CakeDotenv::parse('export=bar'));
    }

    public function testParseQuotedNames(): void
    {
        $this->assertSame(['FOO' => 'bar'], CakeDotenv::parse('"FOO"=bar'));
        $this->assertSame(['FOO' => 'bar'], CakeDotenv::parse("'FOO'=bar"));
    }

    public function testParseTrimsWhitespace(): void
    {
        $this->assertSame(['FOO' => 'bar'], CakeDotenv::parse("  FOO \t=  bar  "));
        $this->assertSame(['FOO' => ' bar '], CakeDotenv::parse('FOO = " bar " '));
    }

    public function testParseNames(): void
    {
        $this->assertSame(['N.VAR6' => 'x', 'a_b' => 'y', '_X' => 'z'], CakeDotenv::parse("N.VAR6=x\na_b=y\n_X=z"));
        $this->assertSame([7 => '1', 'FOO' => 'b', 123 => 'x'], CakeDotenv::parse("7=1\nFOO=b\n123=x"));
        $this->assertSame(['FOOƱ' => 'BAZ'], CakeDotenv::parse('FOOƱ=BAZ'));
    }

    public function testParseEmptyValues(): void
    {
        $contents = "A=\nB=\"\"\nC=''\nD= # comment\nE=# comment\nF=   \n";
        $this->assertSame(
            ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => '', 'F' => ''],
            CakeDotenv::parse($contents),
        );
    }

    public function testParseNameWithoutValue(): void
    {
        $this->assertSame(['FOO' => null], CakeDotenv::parse('FOO'));
        $this->assertSame(['FOO' => null, 'BAR' => '1'], CakeDotenv::parse("FOO\nBAR=1"));
        $this->assertSame(['FOO' => null], CakeDotenv::parse('export FOO'));
    }

    public function testParseValuesAreStrings(): void
    {
        $contents = implode("\n", [
            'SEED=76859309657453542496749683645',
            'ZERO_PADDED=007',
            'NEGATIVE=-2',
            'FLOAT=1.50',
            'BOOL_TRUE=true',
            'BOOL_FALSE=false',
            'NULL_VALUE=null',
            'QUOTED_NUMBER="0"',
        ]);
        $this->assertSame([
            'SEED' => '76859309657453542496749683645',
            'ZERO_PADDED' => '007',
            'NEGATIVE' => '-2',
            'FLOAT' => '1.50',
            'BOOL_TRUE' => 'true',
            'BOOL_FALSE' => 'false',
            'NULL_VALUE' => 'null',
            'QUOTED_NUMBER' => '0',
        ], CakeDotenv::parse($contents));
    }

    public function testParseUnquotedValues(): void
    {
        $this->assertSame(['A' => 'pgsql:host=localhost;dbname=test'], CakeDotenv::parse('A=pgsql:host=localhost;dbname=test'));
        $this->assertSame(['A' => '\n\v'], CakeDotenv::parse('A=\n\v'));
        $this->assertSame(['A' => '\q'], CakeDotenv::parse('A=\q'));
        $this->assertSame(['A' => 'a"b"'], CakeDotenv::parse('A=a"b"'));
        $this->assertSame(['A' => "it's"], CakeDotenv::parse("A=it's"));
        $this->assertSame(['A' => '(x)'], CakeDotenv::parse('A=(x)'));
    }

    public function testParseInlineComments(): void
    {
        $this->assertSame(['A' => 'foo'], CakeDotenv::parse('A=foo # comment'));
        $this->assertSame(['A' => 'foo'], CakeDotenv::parse('A=foo# comment'));
        $this->assertSame(['A' => 'secret!@'], CakeDotenv::parse('A=secret!@#'));
        $this->assertSame(['A' => 'with spaces'], CakeDotenv::parse('A="with spaces" # comment'));
        $this->assertSame(['A' => 'with spaces'], CakeDotenv::parse("A='with spaces'# comment"));
        $this->assertSame(['A' => 'a # b'], CakeDotenv::parse('A="a # b" # comment'));
        $this->assertSame(['A' => '#foo'], CakeDotenv::parse("A='#foo'"));
        $this->assertSame(['A' => '1', 'B' => '2'], CakeDotenv::parse("A=1 # example=\"unterminated\nB=2"));
    }

    public function testParseSingleQuotedValues(): void
    {
        $this->assertSame(['A' => '\t'], CakeDotenv::parse("A='\\t'"));
        $this->assertSame(['A' => 'iiii\\a'], CakeDotenv::parse("A='iiii\\a'"));
        $this->assertSame('TEST $BAR $$BAZ ${BAR}', CakeDotenv::parse("BAR=x\nA='TEST \$BAR \$\$BAZ \${BAR}'")['A']);
        $this->assertSame(['A' => 'say "hi"'], CakeDotenv::parse("A='say \"hi\"'"));
    }

    public function testParseDoubleQuotedEscapes(): void
    {
        $this->assertSame(['A' => "\n"], CakeDotenv::parse('A="\n"'));
        $this->assertSame(['A' => "\t"], CakeDotenv::parse('A="\t"'));
        $this->assertSame(['A' => "a\r\f\vb"], CakeDotenv::parse('A="a\r\f\vb"'));
        $this->assertSame(['A' => 'a "quote" and \\'], CakeDotenv::parse('A="a \"quote\" and \\\\"'));
        $this->assertSame(['A' => '$HOME ${HOME}'], CakeDotenv::parse('A="\$HOME \${HOME}"'));
        $this->assertSame(['A' => "line1\nline2"], CakeDotenv::parse('A="line1\nline2"'));
        $this->assertSame(['A' => "\nabc"], CakeDotenv::parse('A="\nabc"'));
        $this->assertSame(
            ['A' => 'test some escaped characters like a quote " or maybe a backslash \\'],
            CakeDotenv::parse('A="test some escaped characters like a quote \\" or maybe a backslash \\\\" # not escaped'),
        );
    }

    public function testParseVariableExpansion(): void
    {
        $contents = implode("\n", [
            'BASE_DIR="/var/webroot/project-root"',
            'CACHE_DIR="${BASE_DIR}/cache"',
            'TMP_DIR=${BASE_DIR}/tmp',
            'SINGLE=\'${BASE_DIR}\'',
            'BARE="$BASE_DIR"',
            'UNDEFINED="${NOT_DEFINED_ANYWHERE}"',
            'ESCAPED="\${BASE_DIR}"',
        ]);
        $this->assertSame([
            'BASE_DIR' => '/var/webroot/project-root',
            'CACHE_DIR' => '/var/webroot/project-root/cache',
            'TMP_DIR' => '/var/webroot/project-root/tmp',
            'SINGLE' => '${BASE_DIR}',
            'BARE' => '$BASE_DIR',
            'UNDEFINED' => '${NOT_DEFINED_ANYWHERE}',
            'ESCAPED' => '${BASE_DIR}',
        ], CakeDotenv::parse($contents));

        $this->assertSame(['FOO' => 'Bar', 'BAZ' => 'Hello Bar'], CakeDotenv::parse("FOO=Bar\nBAZ=\"Hello \${FOO}\""));
        $this->assertSame(['X' => 'Y', 'FOO' => 'ĀĀĀY'], CakeDotenv::parse("X=Y\nFOO=\"ĀĀĀ\${X}\""));
        $this->assertSame(['B' => 'hi', 'FOO' => '$A ${B}'], CakeDotenv::parse("B=hi\nFOO=\"\$A \\\${B}\""));
        $this->assertSame(['A' => '${B}', 'B' => 'x'], CakeDotenv::parse("A=\${B}\nB=x"));
        $this->assertSame(['A' => null, 'B' => '${A}'], CakeDotenv::parse("A\nB=\${A}"));
    }

    public function testParseDoesNotReadEnvironment(): void
    {
        putenv('CAKE_DOTENV_TEST_REAL=real');
        $_ENV['CAKE_DOTENV_TEST_REAL'] = 'real';
        $_SERVER['CAKE_DOTENV_TEST_REAL'] = 'real';

        $this->assertSame(['A' => '${CAKE_DOTENV_TEST_REAL}'], CakeDotenv::parse('A=${CAKE_DOTENV_TEST_REAL}'));
        $this->assertSame(['CAKE_DOTENV_TEST_REAL' => 'dotenv'], CakeDotenv::parse('CAKE_DOTENV_TEST_REAL=dotenv'));
        $this->assertSame('real', getenv('CAKE_DOTENV_TEST_REAL'));
    }

    public function testParseRedefinition(): void
    {
        $this->assertSame(['MULTI1' => 'bar', 'MULTI2' => 'foo'], CakeDotenv::parse("MULTI1=foo\nMULTI2=\${MULTI1}\nMULTI1=bar"));
    }

    public function testParseMultiline(): void
    {
        $this->assertSame(['A' => "one\ntwo", 'B' => '2'], CakeDotenv::parse("A=\"one\ntwo\"\nB=2"));
        $this->assertSame(['A' => "one\ntwo", 'B' => '2'], CakeDotenv::parse("A=\"one\r\ntwo\"\r\nB=2"));
        $this->assertSame(['A' => "one # data\ntwo", 'B' => '2'], CakeDotenv::parse("A=\"one # data\ntwo\"\nB=2"));
        $this->assertSame(['A' => "\n# not a comment\n", 'B' => '2'], CakeDotenv::parse("A=\"\n# not a comment\n\"\nB=2"));
        $this->assertSame(['A' => "x\n\ny"], CakeDotenv::parse("A=\"x\n\ny\" # comment"));
        $this->assertSame(['A' => "say \"hi\"\nbye"], CakeDotenv::parse("A=\"say \\\"hi\\\"\nbye\""));
        $this->assertSame(['A' => 'one'], CakeDotenv::parse("A='one'"));
    }

    /**
     * Fixtures adapted from vlucas/phpdotenv.
     *
     * @return array
     */
    public static function fixtureProvider(): array
    {
        return [
            'assertions' => ['assertions.env', [
                'ASSERTVAR1' => 'val1',
                'ASSERTVAR2' => '',
                'ASSERTVAR3' => 'val3   ',
                'ASSERTVAR4' => '0',
                'ASSERTVAR5' => '#foo',
                'ASSERTVAR6' => "val1\nval2",
                'ASSERTVAR7' => "\nval3",
                'ASSERTVAR8' => "val3\n",
                'ASSERTVAR9' => "\n\n",
            ]],
            'commented' => ['commented.env', [
                'CFOO' => 'bar',
                'CSPACED' => 'with spaces',
                'CQUOTES' => 'a value with a # character',
                'CQUOTESWITHQUOTE' => 'a value with a # character & a quote " character inside quotes',
                'EMPTY' => '',
                'EMPTY2' => '',
                'FOOO' => 'foo',
                'BOOLEAN' => 'yes',
                'CNULL' => '',
            ]],
            'exported' => ['exported.env', [
                'EFOO' => 'bar',
                'EBAR' => 'baz',
                'ESPACED' => 'with spaces',
                'EDQUOTED' => '123',
                'ESQUOTED' => '456',
                'E' => '789',
                'ENULL' => '',
            ]],
            'multiline' => ['multiline.env', [
                'TEST' => "test\n     test\"test\"\n     test",
                'TEST_ND' => "test\ntest",
                'TEST_NS' => 'test\\ntest',
                'TEST_EQD' => 'https://vision.googleapis.com/v1/images:annotate?key=',
                'TEST_EQS' => 'https://vision.googleapis.com/v1/images:annotate?key=',
                'BASE64_ENCODED_MULTILINE' => "qS1zCzMVVUJWQShokv6YVYi+ruKSC/bHV7GmEiyVkLaBWJHNVHCHsgTksEBsy8wJ\n" .
                    "uwycAvR07ZyOJJed4XTRMKnKp1/v+6UATpWzkIjZXytK+pD+XlZimUHTx3uiDcmU\n" .
                    "jhQX1wWSxHDqrSWxeIJiTD+BuUyId8FzmXQ3TcBydJ474tmOU2F492ubk3LAiZ18\n" .
                    'mhiRGoshXAOSbS/P3+RZi4bDeNE/No4=',
            ]],
            'nested' => ['nested.env', [
                'NVAR1' => 'Hellō',
                'NVAR2' => 'World!',
                'NVAR3' => '{$NVAR1} {$NVAR2}',
                'NVAR4' => 'Hellō World!',
                'NVAR5' => '$NVAR1 {NVAR2}',
                'N.VAR6' => 'Special Value',
                'NVAR7' => 'Special Value',
                'NVAR8' => '',
                'NVAR9' => '',
                'NVAR10' => '${NVAR888}',
                'NVAR11' => 'NVAR1',
                'NVAR12' => 'Hellō',
                'NVAR13' => '${${NVAR11}}',
                'NVAR14' => '${NVAR1} ${NVAR2}',
                'NVAR15' => '${NVAR1} ${NVAR2}',
            ]],
            'quoted' => ['quoted.env', [
                'QFOO' => 'bar',
                'QBAR' => 'baz',
                'QSPACED' => 'with spaces',
                'QEQUALS' => 'pgsql:host=localhost;dbname=test',
                'QNULL' => '',
                'QWHITESPACE' => 'no space',
                'QESCAPED' => 'test some escaped characters like a quote (") or maybe a backslash (\\)',
                'QSLASH' => 'iiiiviiiixiiiiviiii\\n',
                'SQSLASH' => 'iiiiviiiixiiiiviiii\\\\n',
            ]],
            'specialchars' => ['specialchars.env', [
                'SPVAR1' => '$a6^C7k%zs+e^.jvjXk',
                'SPVAR2' => '?BUty3koaV3%GA*hMAwH}B',
                'SPVAR3' => 'jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r',
                'SPVAR4' => '22222:22#2^{',
                'SPVAR5' => 'test some escaped characters like a quote " or maybe a backslash \\',
                'SPVAR6' => 'secret!@',
                'SPVAR7' => 'secret!@#',
                'SPVAR8' => 'secret!@#',
            ]],
            'multiple' => ['multiple.env', [
                'MULTI1' => 'bar',
                'MULTI2' => 'foo',
            ]],
            'multibyte' => ['multibyte.env', [
                'MB1' => 'Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě',
                'MB2' => '行内支付',
                'APP_ENV' => '🚀',
            ]],
            'unicodevarnames' => ['unicodevarnames.env', [
                'AlbertÅberg' => 'Skybert',
                'ДатаЗакрытияРасчетногоПериода' => '2022-04-01T00:00',
            ]],
        ];
    }

    /**
     * @dataProvider fixtureProvider
     * @param string $file Fixture file.
     * @param array $expected Expected variables.
     * @return void
     */
    public function testParseFileFixtures(string $file, array $expected): void
    {
        $this->assertSame($expected, CakeDotenv::parseFile($this->fixtures . $file));
    }

    public function testParseErrorInvalidName(): void
    {
        $this->assertParseError('FOO_ASD!=BAZ', 'Failed to parse dotenv contents on line 1. Encountered an invalid name at [FOO_ASD!].');
        $this->assertParseError("A=1\nFOO BAR=1", 'Failed to parse dotenv contents on line 2. Encountered an invalid name at [FOO BAR].');
        $this->assertParseError("A=1\n\nB-C=1", 'Failed to parse dotenv contents on line 3. Encountered an invalid name at [B-C].');
        $this->assertParseError('export "FOO="bar baz"', 'Failed to parse dotenv contents on line 1. Encountered an invalid name at ["FOO].');
        $this->assertParseError("\xC3=1", "Failed to parse dotenv contents on line 1. Encountered an invalid name at [\xC3].");
        $this->assertParseError('FOO BAR', 'Failed to parse dotenv contents on line 1. Encountered an invalid name at [FOO BAR].');
        $this->assertParseError('FOO ', 'Failed to parse dotenv contents on line 1. Encountered an invalid name at [FOO ].');
        $this->assertParseError('$FOO=1', 'Failed to parse dotenv contents on line 1. Encountered an invalid name at [$FOO].');
    }

    public function testParseErrorUnexpectedEquals(): void
    {
        $this->assertParseError('=', 'Failed to parse dotenv contents on line 1. Encountered an unexpected equals at [=].');
        $this->assertParseError("A=1\n=value", 'Failed to parse dotenv contents on line 2. Encountered an unexpected equals at [=value].');
    }

    public function testParseErrorUnexpectedWhitespace(): void
    {
        $this->assertParseError('FOO=bar baz', 'Failed to parse dotenv contents on line 1. Encountered unexpected whitespace at [bar baz].');
        $this->assertParseError('FOO="bar" baz', 'Failed to parse dotenv contents on line 1. Encountered unexpected whitespace at ["bar" baz].');
        $this->assertParseError("FOO='bar'baz", "Failed to parse dotenv contents on line 1. Encountered unexpected whitespace at ['bar'baz].");
    }

    public function testParseErrorEscapeSequence(): void
    {
        $this->assertParseError('FOO="\q"', 'Failed to parse dotenv contents on line 1. Encountered an unexpected escape sequence at ["\q"].');
        $this->assertParseError('FOO_BAD="iiiiviiiixiiiiviiii\\a"', 'Failed to parse dotenv contents on line 1. Encountered an unexpected escape sequence at ["iiiiviiiixiiiiviiii\a"].');
    }

    public function testParseErrorMissingClosingQuote(): void
    {
        $this->assertParseError("A=1\nTEST='erert", "Failed to parse dotenv contents on line 2. Encountered a missing closing quote at ['erert].");
        $this->assertParseError('TEST="\\', 'Failed to parse dotenv contents on line 1. Encountered a missing closing quote at ["\\].');
        // An unterminated double quoted value runs to the end of the contents.
        $this->assertParseError("A=1\nTEST=\"erert\nB=2\n", 'Failed to parse dotenv contents on line 2. Encountered a missing closing quote at ["erert].');
    }

    public function testParseErrorNulByte(): void
    {
        $this->assertParseError("A=a\0b", 'Failed to parse dotenv contents on line 1. Encountered a NUL byte at [a\000b].');
        $this->assertParseError("A=\"a\0b\"", 'Failed to parse dotenv contents on line 1. Encountered a NUL byte at ["a\000b"].');
    }

    public function testParseErrorMessageFormatting(): void
    {
        $this->assertParseError(
            'FOO="' . str_repeat('a', 100) . '\q"',
            'Failed to parse dotenv contents on line 1. Encountered an unexpected escape sequence at ["' . str_repeat('a', 79) . '...].',
        );
        $this->assertParseError(
            'FOO="' . str_repeat('a', 77) . '🚀' . str_repeat('b', 20) . '\q"',
            'Failed to parse dotenv contents on line 1. Encountered an unexpected escape sequence at ["' . str_repeat('a', 77) . '...].',
        );
        $this->assertParseError(
            "FOO=\"a\x01b\\q\"",
            'Failed to parse dotenv contents on line 1. Encountered an unexpected escape sequence at ["a\001b\q"].',
        );
    }

    public function testParseFileErrorContainsFileAndLine(): void
    {
        $file = $this->writeFile('.env', "# comment\nA=1\n\nB=\"x\ny\"\nC=bad value\n");
        $this->expectException(CakeDotenvException::class);
        $this->expectExceptionMessage(sprintf('Failed to parse dotenv file "%s" on line 6. Encountered unexpected whitespace at [bad value].', $file));
        CakeDotenv::parseFile($file);
    }

    public function testParseFileMissing(): void
    {
        $file = $this->tmpDir . 'missing.env';
        $this->expectException(CakeDotenvException::class);
        $this->expectExceptionMessage(sprintf('Unable to read dotenv file "%s".', $file));
        CakeDotenv::parseFile($file);
    }

    public function testLoad(): void
    {
        $file = $this->writeFile('.env', "CAKE_DOTENV_TEST_A=foo\nexport CAKE_DOTENV_TEST_B=\"bar baz\"\nCAKE_DOTENV_TEST_C=007\n");

        $result = CakeDotenv::load($file);

        $this->assertSame([
            'CAKE_DOTENV_TEST_A' => 'foo',
            'CAKE_DOTENV_TEST_B' => 'bar baz',
            'CAKE_DOTENV_TEST_C' => '007',
        ], $result);
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'foo');
        $this->assertEnvSame('CAKE_DOTENV_TEST_B', 'bar baz');
        $this->assertEnvSame('CAKE_DOTENV_TEST_C', '007');
    }

    public function testLoadArrayOfFiles(): void
    {
        $first = $this->writeFile('first.env', 'CAKE_DOTENV_TEST_A=1');
        $second = $this->writeFile('second.env', 'CAKE_DOTENV_TEST_B=2');

        $this->assertSame(
            ['CAKE_DOTENV_TEST_A' => '1', 'CAKE_DOTENV_TEST_B' => '2'],
            CakeDotenv::load([$first, $second]),
        );
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', '1');
        $this->assertEnvSame('CAKE_DOTENV_TEST_B', '2');
    }

    public function testLoadMissingFilesAreSkipped(): void
    {
        $this->assertSame([], CakeDotenv::load($this->tmpDir . 'missing.env'));
        $this->assertSame([], CakeDotenv::load([]));
        $this->assertSame([], CakeDotenv::load($this->tmpDir));

        $file = $this->writeFile('.env', 'CAKE_DOTENV_TEST_A=1');
        $this->assertSame(['CAKE_DOTENV_TEST_A' => '1'], CakeDotenv::load([$this->tmpDir . 'missing.env', $file]));
    }

    public function testLoadDoesNotOverwriteExistingVariables(): void
    {
        putenv('CAKE_DOTENV_TEST_PUTENV=real');
        $_ENV['CAKE_DOTENV_TEST_ENV'] = 'real';
        $_SERVER['CAKE_DOTENV_TEST_SERVER'] = 'real';
        $file = $this->writeFile('.env', implode("\n", [
            'CAKE_DOTENV_TEST_PUTENV=dotenv',
            'CAKE_DOTENV_TEST_ENV=dotenv',
            'CAKE_DOTENV_TEST_SERVER=dotenv',
            'CAKE_DOTENV_TEST_NEW=dotenv',
            'CAKE_DOTENV_TEST_PUTENV=again',
        ]));

        $result = CakeDotenv::load($file);

        $this->assertSame(['CAKE_DOTENV_TEST_NEW' => 'dotenv'], $result);
        $this->assertSame('real', getenv('CAKE_DOTENV_TEST_PUTENV'));
        $this->assertArrayNotHasKey('CAKE_DOTENV_TEST_PUTENV', $_ENV);
        $this->assertArrayNotHasKey('CAKE_DOTENV_TEST_PUTENV', $_SERVER);
        $this->assertSame('real', $_ENV['CAKE_DOTENV_TEST_ENV']);
        $this->assertFalse(getenv('CAKE_DOTENV_TEST_ENV'));
        $this->assertSame('real', $_SERVER['CAKE_DOTENV_TEST_SERVER']);
        $this->assertFalse(getenv('CAKE_DOTENV_TEST_SERVER'));
        $this->assertEnvSame('CAKE_DOTENV_TEST_NEW', 'dotenv');
    }

    public function testLoadDoesNotOverwriteEmptyExistingVariable(): void
    {
        putenv('CAKE_DOTENV_TEST_A=');
        $file = $this->writeFile('.env', 'CAKE_DOTENV_TEST_A=dotenv');

        $this->assertSame([], CakeDotenv::load($file));
        $this->assertSame('', getenv('CAKE_DOTENV_TEST_A'));
    }

    public function testLoadOverwrite(): void
    {
        putenv('CAKE_DOTENV_TEST_A=real');
        $_ENV['CAKE_DOTENV_TEST_A'] = 'real';
        $_SERVER['CAKE_DOTENV_TEST_A'] = 'real';
        $file = $this->writeFile('.env', 'CAKE_DOTENV_TEST_A=dotenv');

        $this->assertSame(['CAKE_DOTENV_TEST_A' => 'dotenv'], CakeDotenv::load($file, true));
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'dotenv');
    }

    public function testLoadRedefinitionInSameFile(): void
    {
        $file = $this->writeFile('.env', "CAKE_DOTENV_TEST_A=foo\nCAKE_DOTENV_TEST_B=\${CAKE_DOTENV_TEST_A}\nCAKE_DOTENV_TEST_A=bar\n");

        $this->assertSame(
            ['CAKE_DOTENV_TEST_A' => 'bar', 'CAKE_DOTENV_TEST_B' => 'foo'],
            CakeDotenv::load($file),
        );
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'bar');
        $this->assertEnvSame('CAKE_DOTENV_TEST_B', 'foo');
    }

    public function testLoadFirstFileWins(): void
    {
        $first = $this->writeFile('first.env', "CAKE_DOTENV_TEST_A=first\nCAKE_DOTENV_TEST_B=first\n");
        $second = $this->writeFile('second.env', "CAKE_DOTENV_TEST_A=second\nCAKE_DOTENV_TEST_C=second-\${CAKE_DOTENV_TEST_A}\n");

        $this->assertSame([
            'CAKE_DOTENV_TEST_A' => 'first',
            'CAKE_DOTENV_TEST_B' => 'first',
            'CAKE_DOTENV_TEST_C' => 'second-first',
        ], CakeDotenv::load([$first, $second]));
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'first');

        // The order of the files also decides with $overwrite.
        putenv('CAKE_DOTENV_TEST_B=real');
        $this->assertSame([
            'CAKE_DOTENV_TEST_A' => 'first',
            'CAKE_DOTENV_TEST_B' => 'first',
            'CAKE_DOTENV_TEST_C' => 'second-first',
        ], CakeDotenv::load([$first, $second], true));
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'first');
        $this->assertEnvSame('CAKE_DOTENV_TEST_B', 'first');
    }

    public function testLoadSeparateCallsDoNotOverwrite(): void
    {
        $first = $this->writeFile('first.env', 'CAKE_DOTENV_TEST_A=first');
        $second = $this->writeFile('second.env', 'CAKE_DOTENV_TEST_A=second');

        CakeDotenv::load($first);
        $this->assertSame([], CakeDotenv::load($second));
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'first');
    }

    public function testLoadExpandsEnvironmentVariables(): void
    {
        putenv('CAKE_DOTENV_TEST_HOST=real.example.com');
        $_SERVER['CAKE_DOTENV_TEST_SERVER_ONLY'] = 'server';
        $_ENV['CAKE_DOTENV_TEST_ENV_ONLY'] = 'env';
        $file = $this->writeFile('.env', implode("\n", [
            'CAKE_DOTENV_TEST_HOST=dotenv.example.com',
            'CAKE_DOTENV_TEST_URL="https://${CAKE_DOTENV_TEST_HOST}/"',
            'CAKE_DOTENV_TEST_OTHERS=${CAKE_DOTENV_TEST_SERVER_ONLY}-${CAKE_DOTENV_TEST_ENV_ONLY}',
        ]));

        $result = CakeDotenv::load($file);

        $this->assertSame([
            'CAKE_DOTENV_TEST_URL' => 'https://real.example.com/',
            'CAKE_DOTENV_TEST_OTHERS' => 'server-env',
        ], $result);
        $this->assertEnvSame('CAKE_DOTENV_TEST_URL', 'https://real.example.com/');
    }

    public function testLoadUnset(): void
    {
        putenv('CAKE_DOTENV_TEST_REAL=real');
        $file = $this->writeFile('.env', "CAKE_DOTENV_TEST_A=foo\nCAKE_DOTENV_TEST_A\nCAKE_DOTENV_TEST_B\nCAKE_DOTENV_TEST_REAL\n");

        $this->assertSame(
            ['CAKE_DOTENV_TEST_A' => null, 'CAKE_DOTENV_TEST_B' => null],
            CakeDotenv::load($file),
        );
        $this->assertEnvNotSet('CAKE_DOTENV_TEST_A');
        $this->assertEnvNotSet('CAKE_DOTENV_TEST_B');
        $this->assertSame('real', getenv('CAKE_DOTENV_TEST_REAL'));

        $this->assertSame(
            ['CAKE_DOTENV_TEST_A' => null, 'CAKE_DOTENV_TEST_B' => null, 'CAKE_DOTENV_TEST_REAL' => null],
            CakeDotenv::load($file, true),
        );
        $this->assertEnvNotSet('CAKE_DOTENV_TEST_REAL');
    }

    public function testLoadFixture(): void
    {
        $result = CakeDotenv::load($this->fixtures . 'quoted.env');

        $this->assertSame('pgsql:host=localhost;dbname=test', $result['QEQUALS']);
        $this->assertEnvSame('QFOO', 'bar');
        $this->assertEnvSame('QNULL', '');
        $this->assertEnvSame('QWHITESPACE', 'no space');
    }

    public function testLoadNulByte(): void
    {
        $file = $this->writeFile('.env', "CAKE_DOTENV_TEST_A=1\nCAKE_DOTENV_TEST_NUL=a\0b");
        try {
            CakeDotenv::load($file);
            $this->fail('Expected CakeDotenvException was not thrown.');
        } catch (CakeDotenvException $e) {
            $this->assertSame(
                sprintf('Failed to parse dotenv file "%s" on line 2. Encountered a NUL byte at [a\000b].', $file),
                $e->getMessage(),
            );
        }
        $this->assertEnvNotSet('CAKE_DOTENV_TEST_A');
    }

    public function testLoadParseError(): void
    {
        $file = $this->writeFile('.env', "CAKE_DOTENV_TEST_A=1\nCAKE_DOTENV_TEST_B='x\n");
        try {
            CakeDotenv::load($file);
            $this->fail('Expected CakeDotenvException was not thrown.');
        } catch (CakeDotenvException $e) {
            $this->assertSame(
                sprintf('Failed to parse dotenv file "%s" on line 2. Encountered a missing closing quote at [\'x].', $file),
                $e->getMessage(),
            );
        }
        // Nothing is loaded from a file that cannot be parsed.
        $this->assertEnvNotSet('CAKE_DOTENV_TEST_A');
    }

    public function testDefaultFiles(): void
    {
        $this->assertSame([CONFIG . '.env', ROOT . DS . '.env'], CakeDotenv::defaultFiles());
    }

    public function testLoadDefaultsPrecedence(): void
    {
        // Same order as defaultFiles(): config directory first, project root second.
        $config = $this->writeFile('config' . DS . '.env', "CAKE_DOTENV_TEST_A=config\nCAKE_DOTENV_TEST_B=config\n");
        $root = $this->writeFile('.env', "CAKE_DOTENV_TEST_A=root\nCAKE_DOTENV_TEST_B=root\nCAKE_DOTENV_TEST_C=root\n");
        TestCakeDotenv::$files = [$config, $root];
        putenv('CAKE_DOTENV_TEST_A=real');

        $this->assertSame(
            ['CAKE_DOTENV_TEST_B' => 'config', 'CAKE_DOTENV_TEST_C' => 'root'],
            TestCakeDotenv::loadDefaults(),
        );
        $this->assertSame('real', getenv('CAKE_DOTENV_TEST_A'));
        $this->assertEnvSame('CAKE_DOTENV_TEST_B', 'config');
        $this->assertEnvSame('CAKE_DOTENV_TEST_C', 'root');
    }

    public function testLoadDefaultsOnlyRoot(): void
    {
        $root = $this->writeFile('.env', 'CAKE_DOTENV_TEST_A=root');
        TestCakeDotenv::$files = [$this->tmpDir . 'config' . DS . '.env', $root];

        $this->assertSame(['CAKE_DOTENV_TEST_A' => 'root'], TestCakeDotenv::loadDefaults());
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'root');
    }

    public function testLoadDefaultsOverwrite(): void
    {
        $config = $this->writeFile('config' . DS . '.env', 'CAKE_DOTENV_TEST_A=config');
        TestCakeDotenv::$files = [$config];
        putenv('CAKE_DOTENV_TEST_A=real');

        $this->assertSame(['CAKE_DOTENV_TEST_A' => 'config'], TestCakeDotenv::loadDefaults(true));
        $this->assertEnvSame('CAKE_DOTENV_TEST_A', 'config');
    }
}
