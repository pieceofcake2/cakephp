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
 * Asserts the content type of the response.
 *
 * The expected value can be a MIME type (`application/json`) or an alias known to
 * `CakeResponse` (`json`, `html`, ...).
 *
 * Backport of `Cake\TestSuite\Constraint\Response\ContentType` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Response/ContentType.php).
 *
 * @package       Cake.TestSuite.Constraint.Response
 */
class ContentTypeConstraint extends ResponseConstraintBase
{
    /**
     * Checks assertion
     *
     * @param mixed $other Expected type
     * @return bool
     */
    public function matches($other): bool
    {
        $mimeType = $this->response->getMimeType((string)$other);
        if (is_array($mimeType)) {
            $mimeType = reset($mimeType);
        }
        if (is_string($mimeType)) {
            $other = $mimeType;
        }

        return $other === $this->response->type();
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return 'is set as the Content-Type (`' . $this->response->type() . '`)';
    }
}
