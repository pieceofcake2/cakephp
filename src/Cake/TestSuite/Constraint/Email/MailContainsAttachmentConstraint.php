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
 * @package       Cake.TestSuite.Constraint.Email
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('MailContainsConstraint', 'TestSuite/Constraint/Email');

/**
 * Asserts that an email has an attachment.
 *
 * The expected value is `[$filename, $fileInfo]`. When `$fileInfo` is not empty, the
 * attachment must also contain these keys and values (e.g. `['mimetype' => 'image/png']`,
 * as returned by `CakeEmail::attachments()`).
 *
 * Backport of `Cake\TestSuite\Constraint\Email\MailContainsAttachment` from CakePHP 5.x
 * (https://github.com/cakephp/cakephp/blob/5.x/src/TestSuite/Constraint/Email/MailContainsAttachment.php).
 *
 * Unlike CakePHP 5.x, the file name must always match, also when `$fileInfo` is given.
 *
 * @package       Cake.TestSuite.Constraint.Email
 */
class MailContainsAttachmentConstraint extends MailContainsConstraint
{
    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other): bool
    {
        [$expectedFilename, $expectedFileInfo] = $other;

        foreach ($this->getMessages() as $message) {
            foreach ($message->attachments() as $filename => $fileInfo) {
                if ($filename !== $expectedFilename) {
                    continue;
                }
                if (!$expectedFileInfo || array_intersect_assoc($expectedFileInfo, $fileInfo) === $expectedFileInfo) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('is an attachment of email #%d', $this->at);
        }

        return 'is an attachment of an email';
    }

    /**
     * Overwrites the descriptions so we can remove the automatic "expected" message
     *
     * @param mixed $other Value
     * @return string
     */
    protected function failureDescription($other): string
    {
        [$expectedFilename] = $other;

        return "'" . $expectedFilename . "' " . $this->toString();
    }
}
