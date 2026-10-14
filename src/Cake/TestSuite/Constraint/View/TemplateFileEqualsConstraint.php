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

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that a string is part of the path of the rendered view file.
 *
 * Backport of `Cake\TestSuite\Constraint\View\TemplateFileEquals` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/View/TemplateFileEquals.php).
 *
 * @package       Cake.TestSuite.Constraint.View
 */
class TemplateFileEqualsConstraint extends Constraint
{
    /**
     * The rendered file, or null if nothing was rendered.
     *
     * @var string|null
     */
    protected ?string $filename;

    /**
     * Constructor
     *
     * @param string|null $filename Template file name
     */
    public function __construct(?string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected filename
     * @return bool
     */
    public function matches($other): bool
    {
        return $this->filename !== null && str_contains($this->filename, (string)$other);
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('equals template file `%s`', (string)$this->filename);
    }
}
