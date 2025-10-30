<?php
/**
 * CakeValidationSet.
 *
 * Provides the Model validation logic.
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
 * @package       Cake.Model.Validator
 * @since         CakePHP(tm) v 2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Model\Validator;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * CakeValidationSet object. Holds all validation rules for a field and exposes
 * methods to dynamically add or remove validation rules
 *
 * @package       Cake.Model.Validator
 * @link          https://book.cakephp.org/2.0/en/data-validation.html
 */
class CakeValidationSet implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Holds the CakeValidationRule objects
     *
     * @var array<CakeValidationRule>
     */
    protected array $_rules = [];

    /**
     * List of methods available for validation
     *
     * @var array
     */
    protected array $_methods = [];

    /**
     * I18n domain for validation messages.
     *
     * @var string|null
     */
    protected ?string $_validationDomain = null;

    /**
     * Whether the validation is stopped
     *
     * @var bool
     */
    public bool $isStopped = false;

    /**
     * Holds the fieldname
     *
     * @var string|null
     */
    public ?string $field = null;

    /**
     * Holds the original ruleSet
     *
     * @var array
     */
    public array $ruleSet = [];

    /**
     * Constructor
     *
     * @param string $fieldName The fieldname.
     * @param array|string $ruleSet Rules set.
     */
    public function __construct(string $fieldName, array|string $ruleSet)
    {
        $this->field = $fieldName;

        if (!is_array($ruleSet) || isset($ruleSet['rule'])) {
            $ruleSet = [$ruleSet];
        }

        foreach ($ruleSet as $index => $validateProp) {
            $this->_rules[$index] = new CakeValidationRule($validateProp);
        }
        $this->ruleSet = $ruleSet;
    }

    /**
     * Sets the list of methods to use for validation
     *
     * @param array &$methods Methods list
     * @return void
     */
    public function setMethods(array &$methods): void
    {
        $this->_methods =& $methods;
    }

    /**
     * Sets the I18n domain for validation messages.
     *
     * @param string|null $validationDomain The validation domain to be used.
     * @return void
     */
    public function setValidationDomain(?string $validationDomain): void
    {
        $this->_validationDomain = $validationDomain;
    }

    /**
     * Runs all validation rules in this set and returns a list of
     * validation errors
     *
     * @param array $data Data array
     * @param bool $isUpdate Is record being updated or created
     * @return array list of validation errors for this field
     */
    public function validate(array $data, bool $isUpdate = false): array
    {
        $this->reset();
        $errors = [];
        foreach ($this->getRules() as $name => $rule) {
            $rule->isUpdate($isUpdate);
            if ($rule->skip()) {
                continue;
            }

            $checkRequired = $rule->checkRequired($this->field, $data);
            if (!$checkRequired && array_key_exists($this->field, $data)) {
                if ($rule->checkEmpty($this->field, $data)) {
                    break;
                }
                $rule->process($this->field, $data, $this->_methods);
            }

            if ($checkRequired || !$rule->isValid()) {
                $errors[] = $this->_processValidationResponse($name, $rule);
                if ($rule->isLast()) {
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Resets internal state for all validation rules in this set
     *
     * @return void
     */
    public function reset(): void
    {
        foreach ($this->getRules() as $rule) {
            $rule->reset();
        }
    }

    /**
     * Gets a rule for a given name if exists
     *
     * @param string $name Field name.
     * @return CakeValidationRule|null
     */
    public function getRule(string $name): ?CakeValidationRule
    {
        if (!empty($this->_rules[$name])) {
            return $this->_rules[$name];
        }

        return null;
    }

    /**
     * Returns all rules for this validation set
     *
     * @return array<CakeValidationRule>
     */
    public function getRules(): array
    {
        return $this->_rules;
    }

    /**
     * Sets a CakeValidationRule $rule with a $name
     *
     * ## Example:
     *
     * ```
     *      $set
     *          ->setRule('required', array('rule' => 'notBlank', 'required' => true))
     *          ->setRule('between', array('rule' => array('lengthBetween', 4, 10))
     * ```
     *
     * @param string $name The name under which the rule should be set
     * @param CakeValidationRule|array $rule The validation rule to be set
     * @return self
     */
    public function setRule(string $name, CakeValidationRule|array $rule): self
    {
        if (!($rule instanceof CakeValidationRule)) {
            $rule = new CakeValidationRule($rule);
        }
        $this->_rules[$name] = $rule;

        return $this;
    }

    /**
     * Removes a validation rule from the set
     *
     * ## Example:
     *
     * ```
     *      $set
     *          ->removeRule('required')
     *          ->removeRule('inRange')
     * ```
     *
     * @param string $name The name under which the rule should be unset
     * @return self
     */
    public function removeRule(string $name): self
    {
        unset($this->_rules[$name]);

        return $this;
    }

    /**
     * Sets the rules for a given field
     *
     * ## Example:
     *
     * ```
     *      $set->setRules(array(
     *          'required' => array('rule' => 'notBlank', 'required' => true),
     *          'inRange' => array('rule' => array('between', 4, 10)
     *      ));
     * ```
     *
     * @param array $rules The rules to be set
     * @param bool $mergeVars [optional] If true, merges vars instead of replace. Defaults to true.
     * @return self
     */
    public function setRules(array $rules = [], bool $mergeVars = true): self
    {
        if ($mergeVars === false) {
            $this->_rules = [];
        }
        foreach ($rules as $name => $rule) {
            $this->setRule($name, $rule);
        }

        return $this;
    }

    /**
     * Fetches the correct error message for a failed validation
     *
     * @param string|int|null $name the name of the rule as it was configured
     * @param CakeValidationRule $rule the object containing validation information
     * @return string|null
     */
    protected function _processValidationResponse(string|int|null $name, CakeValidationRule $rule): ?string
    {
        $message = $rule->getValidationResult();
        if (is_string($message)) {
            return $message;
        }
        $message = $rule->message;

        if ($message !== null) {
            $args = null;
            if (is_array($message)) {
                $result = $message[0];
                $args = array_slice($message, 1);
            } else {
                $result = $message;
            }
            if (is_array($rule->rule) && $args === null) {
                $args = array_slice($rule->rule, 1);
            }
            $args = $this->_translateArgs($args);

            $message = __d($this->_validationDomain, $result, $args);
        } elseif (is_string($name)) {
            if (is_array($rule->rule)) {
                $args = array_slice($rule->rule, 1);
                $args = $this->_translateArgs($args);
                $message = __d($this->_validationDomain, $name, $args);
            } else {
                $message = __d($this->_validationDomain, $name);
            }
        } else {
            $message = __d('cake', 'This field cannot be left blank');
        }

        return $message;
    }

    /**
     * Applies translations to validator arguments.
     *
     * @param array|string|null $args The args to translate
     * @return array|null Translated args.
     */
    protected function _translateArgs(array|string|null $args): ?array
    {
        foreach ((array)$args as $k => $arg) {
            if (is_string($arg)) {
                $args[$k] = __d($this->_validationDomain, $arg);
            }
        }

        return $args;
    }

    /**
     * Returns whether an index exists in the rule set
     *
     * @param mixed $offset name of the rule
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_rules[$offset]);
    }

    /**
     * Returns a rule object by its index
     *
     * @param mixed $offset name of the rule
     * @return CakeValidationRule
     */
    public function offsetGet(mixed $offset): CakeValidationRule
    {
        return $this->_rules[$offset];
    }

    /**
     * Sets or replace a validation rule.
     *
     * This is a wrapper for ArrayAccess. Use setRule() directly for
     * chainable access.
     *
     * @param string $offset Name of the rule.
     * @param CakeValidationRule|array $value Rule to add to $index.
     * @return void
     * @see http://www.php.net/manual/en/arrayobject.offsetset.php
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setRule($offset, $value);
    }

    /**
     * Unsets a validation rule
     *
     * @param string $offset name of the rule
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->_rules[$offset]);
    }

    /**
     * Returns an iterator for each of the rules to be applied
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_rules);
    }

    /**
     * Returns the number of rules in this set
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->_rules);
    }
}
