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
 * @package       Cake.TestSuite.Constraint.Response
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ResponseConstraintBase', 'TestSuite/Constraint/Response');

/**
 * Asserts the path of the file sent with `CakeResponse::file()`.
 *
 * Backport of `Cake\TestSuite\Constraint\Response\FileSentAs` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/FileSentAs.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class FileSentAsConstraint extends ResponseConstraintBase
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected type
     * @return bool
     */
    public function matches($other): bool
    {
        $file = $this->_getFile();
        if (!$file) {
            return false;
        }

        return $file->path === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'file was sent';
    }
}
