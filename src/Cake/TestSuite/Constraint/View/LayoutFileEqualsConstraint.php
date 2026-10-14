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
 * @package       Cake.TestSuite.Constraint.View
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('TemplateFileEqualsConstraint', 'TestSuite/Constraint/View');

/**
 * Asserts that a string is part of the path of the rendered layout file.
 *
 * Backport of `Cake\TestSuite\Constraint\View\LayoutFileEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/View/LayoutFileEquals.php).
 *
 * @package       Cake.TestSuite.Constraint.View
 */
class LayoutFileEqualsConstraint extends TemplateFileEqualsConstraint
{
    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('equals layout file `%s`', (string)$this->filename);
    }
}
