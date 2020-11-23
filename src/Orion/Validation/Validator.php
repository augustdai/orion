<?php

namespace Orion\Validation;

/**
 * Class Validator
 * @package Orion\Validation
 */
class Validator {

    public $success = true;
    public $errors = [];
    private $reasons = [];
    private $data;
    private $rules;

    /**
     * Validator constructor.
     * @param $data
     * @param $rules
     */
    public function __construct($data, $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->reasons = require __DIR__.'/reasons.php';
        $this->fire();
    }

    /**
     *
     */
    public function fire()
    {
        foreach ($this->rules as $attribute => $rule) {
            foreach (explode('|', $rule) as $item) {
                $detial = explode(':', $item);
                if ( count( $detial ) > 1 ) {
                    $reason = call_user_func_array([$this, $detial[0]], [$this->data[$attribute], $detial[1]]);
                } else {
                    $reason = $this->$item($this->data[$attribute]);
                }
                if ( $reason !== true ) {
                    $this->errors[] = str_replace(':attribute', $attribute, $reason);
                }
            }
        }
        if ( count($this->errors) ) {
            $this->success = false;
        }
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function required($value)
    {
        return !$value ? $this->reasons['required'] : true;
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : $this->reasons['email'];
    }

    /**
     * @param $value
     * @param $min
     * @return bool|string|string[]
     */
    protected function min($value, $min)
    {
        return mb_strlen($value, 'UTF-8') >= $min ? true : str_replace(':min', $min, $this->reasons['min']);
    }

    /**
     * @param $value
     * @param $max
     * @return bool|string|string[]
     */
    protected function max($value, $max)
    {
        return mb_strlen($value, 'UTF-8') <= $max ? true : str_replace(':max', $max, $this->reasons['max']);
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function numeric($value)
    {
        return is_numeric($value) ? true : $this->reasons['numeric'];
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function integer($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? true : $this->reasons['integer'];
    }

    /**
     * @param $method
     * @param $parameters
     */
    public function __call($method, $parameters)
    {
        throw new \UnexpectedValueException("Validate rule [$method] does not exist!");
    }

}