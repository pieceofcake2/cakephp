<?php
/**
 * ApiShellTest file
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\ApiShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\TestSuite\CakeTestCase;

/**
 * ApiShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ApiShellTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            ApiShell::class,
            ['in', 'out', 'createFile', 'hr', '_stop'],
            [$out, $out, $in],
        );
    }

    /**
     * Test that method names are detected properly including those with no arguments.
     *
     * @return void
     */
    public function testMethodNameDetection()
    {
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('q'));
        $outCalls = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$outCalls) {
                $outCalls[] = $message;
            });

        $expected = [
            '1. afterFilter()',
            '2. afterScaffoldSave($method)',
            '3. afterScaffoldSaveError($method)',
            '4. beforeFilter()',
            '5. beforeRedirect($url, $status = NULL, $exit = true)',
            '6. beforeRender()',
            '7. beforeScaffold($method)',
            '8. constructClasses()',
            '9. disableCache()',
            '10. flash($message, $url, $pause = 1, $layout = \'flash\')',
            '11. getEventManager()',
            '12. header($status)',
            '13. httpCodes($code = NULL)',
            '14. implementedEvents()',
            '15. invokeAction($request)',
            '16. loadModel($modelClass = NULL, $id = NULL)',
            '17. paginate($object = NULL, $scope = array (), $whitelist = array ())',
            '18. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
            '19. redirect($url, $status = NULL, $exit = true)',
            '20. referer($default = NULL, $local = false)',
            '21. render($view = NULL, $layout = NULL)',
            '22. scaffoldError($method)',
            '23. set($one, $two = NULL)',
            '24. setAction($action, $args)',
            '25. setRequest($request)',
            '26. shutdownProcess()',
            '27. startupProcess()',
            '28. validate($args)',
            '29. validateErrors($objects)',
        ];

        $this->Shell->args = ['controller'];
        $this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
        $this->Shell->main();

        $this->assertEquals('Controller', $outCalls[0]);
        $this->assertEquals($expected, $outCalls[1]);
    }
}
