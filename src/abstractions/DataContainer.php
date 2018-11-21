<?php

/*
 * This file is part of the Core package in (c)Paybox Integration Component.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Paybox\Core\Abstractions;

use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use Paybox\Core\Exceptions\Payment as PaymentException;
use Paybox\Core\Exceptions\Property as PropertyException;
use Paybox\Core\Exceptions\Method as MethodException;

/**
 *
 * @package Paybox\Core\Abstractions
 * @version 1.1.0
 * @author Sergey Astapenko <sa@paybox.money> @link https://paybox.money
 * @copyright LLC Paybox.money
 * @license GPLv3 @link https://www.gnu.org/licenses/gpl-3.0-standalone.html
 *
 */

abstract class DataContainer extends DataProvider {

    /**
     * @var array $required Collection of required properties.
     */
    private $required = [];

    /**
     * @var string $prefix Prefix for request properties
     */
    protected $prefix = 'pg';

    /**
     * @var string $delimeter Delimeter for request properties
     */
    protected $delimeter = '_';

    /**
     * @var array $serverAnswer Parsed answer from Payment gateway
     */
    protected $serverAnswer = [];

    /**
     * @var array $query Parameters of request
     */
    protected $query = [];

    /**
     *
     * This method check all request parameters,
     * check filling required properties
     * and add signature
     *
     * @return void
     *
     */

    protected function save($script = null, $use_ext = true) {
        $this->url = (is_null($script))
            ? $_SERVER['REQUEST_URI']
            : ($use_ext ? $script . '.php' : $script);
        $this->checkFilling();
        foreach($this as $object) {
            if($this->isModel($object)) {
                unset($object->required);
                $this->addModel($object);
            }
        }
        $this->sign();
    }

    /**
     *
     * Validate signature of requests
     *
     * @param array Request parameters
     *
     * @return bool TRUE if signature is valid
     */

    public function checkSig($data) {
        $this->serverAnswer = $data;
        $sign = $this->getServerAnswer('sig');
        $salt = $this->getServerAnswer('salt');
        unset($data[$this->toProperty('sig')], $data[$this->toProperty('salt')]);
        $this->sign($data, $salt);
        return ($sign == $data[$this->toProperty('sig')]);
    }

    /**
     *
     * This method add model to query
     *
     * @return void
     *
     */

    private function addModel($model) {
        $defProps = (new ReflectionClass($model))
            ->getProperties(ReflectionProperty::IS_PUBLIC);
        $default = ['prefix', 'delimeter'];
        foreach($defProps as $prop) {
            $property = $prop->name;
            if(!empty($model->$property)) {
                $this->query[$this->getKeyName($model, $prop->name)] = $model->$property;
                array_push($default, $prop->name);
            }
        }

        foreach($model as $prop => $value) {
            if(((new ReflectionObject($model))->hasProperty($prop) === true)) {
                if(!in_array($prop, $default) && (!empty($value))) {
                    $this->query[$prop] = $value;
                }
            }
        }
    }

    /**
     *
     * This method add a signature to request
     *
     * @return void
     *
     */

    private function sign(&$data = null, $salt = null) {
        $salt = (is_null($salt))
            ? $this->getSalt(16)
            : $salt;

        $arr = (is_null($data))
            ? $this->query
            : $data;

        $index = $this->toProperty('secretkey');

        $key = (empty($arr[$this->toProperty('secretkey')]))
                ? $this->merchant->secretKey
                : $arr[$this->toProperty('secretkey')];

        $this->url = (empty($this->url))
            ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
            : $this->url;

        $url = explode('/', $this->url);
        unset($arr[$this->prefix . $this->delimeter . 'secret' . $this->delimeter . 'key']);
        $arr[$this->toProperty('salt')] = $salt;
        ksort($arr);
        array_unshift($arr, end($url));
        array_push($arr, $key);
        $arr[$this->toProperty('sig')] = md5(implode(';', $arr));
        unset($arr[0], $arr[1]);
        if(is_null($data)) {
            $this->query = $arr;
        } else {
            $data = $arr;
        }
    }

    /**
     *
     * Method for checking a models for filling a required properties
     *
     * @return void
     *
     */

    private function checkFilling() {
        foreach(get_object_vars($this) as $prop) {
            if($this->isModel($prop)) {
                $this->isFilled($prop);
            }
        }
    }

    /**
     *
     * Set a property as required
     *
     * @return void
     *
     */

    protected function required($property) {
         array_push($this->required, $property);
    }

    /**
     *
     * Checks whether the variable is a model
     *
     * @return bool
     *
     */

    private function isModel($elem) {
        return ($elem instanceof DataContainer);
    }

    /**
     *
     * Check model for filling required properties
     *
     * @return bool
     *
     */

    private function isFilled($model) {
        if(property_exists($model, 'required')) {
            foreach($model->required as $prop) {
                if(empty($model->$prop)) {
                    throw new PropertyException(
                        'Property `' . strtolower($prop) .
                        '` not filled in ' .
                        (new ReflectionClass(get_class($model)))->getShortName() . "\n\r"
                    );
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Return a valid name for property
     *
     * @return string
     *
     */

    private function getKeyName($model, $property) {
        return ($this->isDefault($model, $property))
            ? $this->toQueryProperty($model, $property)
            : $property;
    }

    /**
     *
     * Convert a property to query parameter
     *
     * @return string
     *
     */
    private function toQueryProperty($model, $property) {
        $model = strtolower((new ReflectionClass($model))->getShortName());
        if($property == 'id') {
            if ($model == 'customer') {
                $property = 'user' . $this->delimeter . $property;
            } else {
                $property = (in_array($model, ['merchant', 'order', 'payment', 'card']))
                    ? $model . $this->delimeter . $property
                    : $property;
            }
        }

        return $this->toProperty(
            implode(
                $this->delimeter,
                array_filter(
                    str_replace('is', null,
                        array_map(
                            'strtolower',
                            preg_split(
                                '/(?=[A-Z])/',
                                $property
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     *
     * Add a valid param syntax
     *
     * @return string
     *
     */

    private function toProperty($property) {
        return $this->prefix . $this->delimeter . $property;
    }

    /**
     * @return bool
     */

    private function isDefault($model, $property) {
        $ref = new ReflectionClass($model);
        if($ref->hasProperty($property)) {
            return $ref->getProperty($property)->isDefault();
        }
        return false;
    }

    /**
     *
     * Generate a random string for salt
     *
     * @return string
     *
     */

    private function getSalt($size) {
        return substr(md5(microtime() . rand()), 0, $size);
    }


    protected function toXML() {
        $this->xml = new \DOMDocument();
        $this->xml->preserveWhiteSpace = true;
        $this->xml->formatOutput = true;
        $this->xml->validationOnParse = false;
        $this->xml->appendChild($this->xml->createElement('response'));
        $this->xml = new \SimpleXMLElement($this->xml->saveXML());
        foreach($this->query as $k=>$v) {
            $this->xml->addChild($k, $v);
        }
    }

    public function __get($property) {
        $property = strtolower($property);
        $path = explode('\\', get_called_class());
        array_pop($path);
        $class = implode('\\', $path) . '\Models\\' . ucfirst($property);
        $this->$property = new $class;
        return $this->$property;
    }

    public function __set($property, $value) {
        $this->$property = $value;
    }

    public function __debugInfo() {
        $properties = get_object_vars($this);
        if(array_key_exists('secretKey', $properties)) {
            $patterns = ['~[a-zA-Z]~', '~[0-9]~'];
            $replace = ['l', 'd'];
            $properties['secretKey'] = preg_replace($patterns, $replace, $properties['secretKey']);
            $properties['id'] = preg_replace($patterns, $replace, $properties['id']);
        }
        return $properties;
    }

    public function __call($method, $args) {
        $type = mb_substr($method, 0, 3);
        $property = lcfirst(mb_substr($method, 3));
        if($type == 'get') {
            return $this->$property;
        } elseif($type == 'set') {
            if(!empty($args[0])) {
                $this->$property = $args[0];
            } else {
                throw new PropertyException('Property ' . $property . ' can\'t be empty');
            }
            return $this;
        }
    }

}
