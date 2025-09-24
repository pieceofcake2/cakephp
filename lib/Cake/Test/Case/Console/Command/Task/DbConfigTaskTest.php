<?php
/**
 * DBConfigTask Test Case
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('DbConfigTask', 'Console/Command/Task');

/**
 * DbConfigTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class DbConfigTaskTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() : void {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', [], [], '', false);
		$in = $this->getMock('ConsoleInput', [], [], '', false);

		$this->Task = $this->getMock('DbConfigTask',
			['in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest', '_verify'],
			[$out, $out, $in]
		);

		$this->Task->path = CONFIG;
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() : void {
		unset($this->Task);

		parent::tearDown();
	}

/**
 * Test the getConfig method.
 *
 * @return void
 */
	public function testGetConfig() {
		$this->Task->expects($this->any())
			->method('in')
			->will($this->returnValue('test'));

		$result = $this->Task->getConfig();
		$this->assertEquals('test', $result);
	}

/**
 * test that initialize sets the path up.
 *
 * @return void
 */
	public function testInitialize() {
		$this->Task->initialize();
		$this->assertFalse(empty($this->Task->path));
		$this->assertEquals(CONFIG, $this->Task->path);
	}

/**
 * test execute and by extension _interactive
 *
 * @return void
 */
	public function testExecuteIntoInteractive() {
		$this->Task->initialize();

		$out = $this->getMock('ConsoleOutput', [], [], '', false);
		$in = $this->getMock('ConsoleInput', [], [], '', false);
		$this->Task = $this->getMock(
			'DbConfigTask',
			['in', '_stop', 'createFile', 'bake'], [$out, $out, $in]
		);

		$this->Task->expects($this->once())->method('_stop');

		$inReturns = [
			'default',    // 0: name
			'mysql',      // 1: db type
			'n',          // 2: persistent
			'localhost',  // 3: server
			'n',          // 4: port
			'root',       // 5: user
			'password',   // 6: password
			null,
			null,
			null,
			'cake_test',  // 10: db
			'n',          // 11: prefix
			'n',          // 12: encoding
			'y',          // 13: looks good
			'n'           // 14: another
		];
		$inCallIndex = 0;
		$this->Task->expects($this->any())
			->method('in')
			->willReturnCallback(function() use ($inReturns, &$inCallIndex) {
				$return = $inReturns[$inCallIndex] ?? null;
				$inCallIndex++;
				return $return;
			});

		$this->Task->expects($this->once())
			->method('bake')
			->with([
				[
					'name' => 'default',
					'datasource' => 'mysql',
					'persistent' => 'false',
					'host' => 'localhost',
					'login' => 'root',
					'password' => 'password',
					'database' => 'cake_test',
					'prefix' => null,
					'encoding' => null,
					'port' => '',
					'schema' => null
				]
			]);

		$this->Task->execute();
	}
}
