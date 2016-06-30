<?php

namespace CassandraBundle\Dto;

use CassandraBundle\Exception\BadTypeDtoException;
use CassandraBundle\Exception\BadValueDtoException;
use CassandraBundle\Exception\NotFoundDtoException;

abstract class AbstractDto implements DtoInterface
{
    const TYPE_OBJECT = 'object';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_STRING = 'string';
    const TYPE_FLOAT = 'float';
    const TYPE_ARRAY = 'array';
    const TYPE_ASSOC = 'assoc';
    const TYPE_BOOLEAN = 'string';
    
    protected $fields        = [];
    protected $required      = [];
    protected $optional      = [];
    protected $defaults      = [];
    protected $allowedTypes  = [];
    protected $allowedValues = [];

    protected $validated     = false;
    
    protected static $clone  = null; // prototype
    
    protected $customTypes   = [];
    protected $data          = [];
    
    /**
     * AbstractDto constructor.
     * @param array $data
     * @param bool  $validate
     */
    public function __construct(array $data = [], bool $validate = false)
    {
        static::$clone = clone $this;
        
        $this->init();
        
        $this->setData($data);
        
        if (!$this->validated) { // if not validated
            $this->validate();
        }
    }

    /**
     * @param  array $data
     * @param  bool $validate
     * @return AbstractDto
     */
    public static function create(array $data = [], bool $validate = false) : AbstractDto
    {
        if (!(static::$clone instanceof static)) {
            static::$clone = new static($data, $validate);
            return static::$clone;
        }
        
        $dto = clone static::$clone;
        $dto->init();
        $dto->setData($data);
        if (!$dto->validated) { // if not validated
            $dto->validate();
        }
        return $dto;
    }

    /**
     * Initialize DTO
     * @return void
     */
    abstract protected function init();

    /**
     * @throws BadTypeDtoException
     * @throws BadValueDtoException
     * @throws NotFoundDtoException
     */
    public function validate()
    {
        $this->validateRequired();
        
        $this->validateAllowedTypes();
        
        $this->validateAllowedValues();
    }

    /**
     * @throws NotFoundDtoException
     */
    public function validateRequired()
    {
        $missing = [];
        foreach ($this->required as $field) {
            if (isset($this->data[$field]) === false) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new NotFoundDtoException("Missing required fields: ". implode(', ', $missing) ."!");
        }
    }

    /**
     * @throws BadTypeDtoException
     */
    public function validateAllowedTypes()
    {
        foreach ($this->allowedTypes as $field => $allowed) {
            $value = $this->data[$field];
            if (is_null($value)) {
                continue;
            }

            $type = gettype($value);

            /**
             * @todo implement multiple data types > $allowed = explode('|', $allowed)
             * @todo implement subconditions > ex: array of integers
             */
            $frags = explode(':', $allowed);
            $allowed = $frags[0];
            //$subcond = $frags[1];
            switch ($allowed) {
                case 'object':
                    /** @todo validata data type also */
                    if (!is_object($value)) {
                        throw new BadTypeDtoException("Field [{$field}] is not an object!");
                    }
                break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        throw new BadTypeDtoException("Field [{$field}] must an integer, {$type} [{$value}] given!");
                    }
                    $this->data[$field] = intval($value);
                break;
                case 'float':
                    if (!is_float($value)) {
                        throw new BadTypeDtoException("Field [{$field}] must a float, {$type} [{$value}] given!");
                    }
                    $this->data[$field] = floatval($value);
                break;
                case 'string':
                    if (!is_string($value)) {
                        throw new BadTypeDtoException("Field [{$field}] must a string, {$type} [{$value}] given!");
                    }
                    $this->data[$field] = strval($value);
                break;
                case 'array':
                    if ($value[0] != '[' || $value[count($value)-1] != ']') {
                       // throw new BadTypeDtoException("Field [{$field}] must an array, {$type} [{$value}] given!");
                    }
                    //$value = trim($value, '[ ]');
                    if (empty($value)) {
                        throw new BadTypeDtoException("Field [{$field}] is an empty array!");
                    }
                  //  $value = explode(',', $value);
                    if (empty($value)) {
                        throw new BadTypeDtoException("Field [{$field}] is an empty array!");
                    }
                break;
                case 'assoc':
                    if ((is_array($value) === false) || (array_keys($value) === range(0, count($value) - 1))) {
                        throw new BadTypeDtoException("Field [{$field}] must an assoc array, {$type} [". print_r($value, 1) ."] given!");
                    }
                break;
                case 'boolean':
                    $this->data[$field] = !!$value;
                break;
                default:
                    foreach ($this->customTypes as $type => $condition) {
                        if ($allowed === $type) {
                            $condition($type, $field, $value);
                            break;
                        }
                    }
            }
        }
    }

    /**
     * @throws BadValueDtoException
     */
    public function validateAllowedValues()
    {
        foreach ($this->allowedValues as $field => $allowed) {
            $value = $this->data[$field];
            if (is_null($value)) {
                continue;
            }

            if (!in_array($value, $allowed)) {
                throw new BadValueDtoException("Field [{$field}] has a value [{$value}] that is not allowed. Only [". implode(', ', $allowed) ."] are allowed.");
            }
        }
    }
    
    /**
     * @param array $data
     */
    protected function setData(array $data = [])
    {
        $this->data = $data;

        foreach ($this->optional as $field) {
            if (!isset($this->data[$field])) {
                $this->data[$field] = null;
            }
        }

        foreach ($this->defaults as $field => $value) {
            if (is_null($this->data[$field])) {
                $this->data[$field] = $value;
            } else if (is_array($value)) {
                $this->data[$field] = array_merge($value, $this->data[$field]);
            }
        }
        
        foreach ($this->data as $key => $value) {
            $this->fields[] = $key;
        }
        
        $this->fields = array_unique($this->fields);
    }
    
    /**
     * Set required fields
     * @param array $array
     */
    protected function setRequired(array $array)
    {
        $this->required = $array;
    }

    /**
     * Set optional fields
     * @param array $array
     */
    protected function setOptional(array $array)
    {
        $this->optional = $array;
    }

    /**
     * Set default values
     * @param array $array
     */
    protected function setDefaults(array $array)
    {
        $this->defaults = $array;
    }

    /**
     * Set allowed data types
     * @param array $array
     */
    protected function setAllowedTypes(array $array)
    {
        $this->allowedTypes = $array;
    }

    /**
     * Set allowed values
     * @param array $array
     */
    protected function setAllowedValues(array $array)
    {
        $this->allowedValues = $array;
    }

    /**
     * @param string $key
     * @param null $value
     */
    public function addData(string $key, $value = null)
    {
        $this->data[$key] = $value;
        $this->fields[] = $key;
        $this->fields = array_unique($this->fields);
    }
    
    /**
     * @param string $key
     */
    public function addRequired(string $key) 
    {
        $this->required[] = $key;
        $this->validated = false;
    }

    /**
     * @param string $key
     */
    public function addOptional(string $key) 
    {
        $this->optional[] = $key;
        $this->validated = false;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function addDefault(string $key, $value)
    {
        $this->defaults[$key] = $value;
        $this->validated = false;
    }

    /**
     * @param string $key
     * @param $condition
     */
    public function addType(string $key, $condition) 
    {
        $this->customTypes[$key] = $condition;
    }
    
    /**
     * @param string $name
     * @param null $params
     * @return mixed|null
     * @throws \Exception
     */
    public function __call(string $name, $params = null)
    {
        $field = lcfirst(substr($name, 3));
        if (!in_array($field, $this->data)) {
            throw new \Exception("Field [{$field}] not found!");
        }
        return $this->get($field);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->data;
    }
    
    /**
     * @return mixed|object
     */
    public function toObject() : object
    {
        return json_decode(json_encode($this->data), false); // for reccursion
    }
}
