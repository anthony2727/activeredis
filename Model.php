<?php

namespace ActiveRedis;

use ActiveRedis\HasManyOrdered;
use ActiveRedis\HasManyUnordered;
use Illuminate\Support\Facades\Redis;

abstract class Model
{
    protected $id;
    protected $key; // the resulted chainned key reference
    protected $prefixKey; // the passed key from parent
    protected $attributes = [];
    protected $className;
    protected $exists = false;

    public function __construct($id = null, $prefixKey = null)
    {
        $this->id        = $id ?? null;
        $this->prefixKey = $prefixKey ?? null;
        $this->className = $this->className();
        $this->key       = $this->getRelationalKey();

        //Make the attributes available in the model if found in cache.
        $this->fresh();
    }

    public static function init($id)
    {
        return new static($id);
    }

    protected function className()
    {
        $fqn        = get_class($this);
        $reflection = new \ReflectionClass($fqn);
        return strtolower($reflection->getShortName());
    }

    protected function getRelationalKey()
    {
        // if (isset($this->id) && isset($this->prefixKey)) {
        $key = $this->id ? "{$this->className}:{$this->id}" : $this->className;
        return isset($this->prefixKey) ? ($this->prefixKey . ':' . $key) : $key;
        // }

        // $key = $this->id ? "{$this->className}:{$this->id}" : $this->className;
        // //if $key, then e.g: user:1:thread:20 otherwise, thread:20
        // return isset($this->parentkey) ? $this->parentkey . ":{$key}" : $key;
    }

    protected function hasManyOrdered($hasMany, $relationClass)
    {
        return new HasManyOrdered($this->getRelationalKey(), $hasMany, $relationClass);
    }

    protected function hasManyUnordered($hasMany, $relationClass)
    {
        return new HasManyUnordered($this->getRelationalKey(), $hasMany, $relationClass);
    }

    public function getAttributeValue($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    public function setAttributes($attributes)
    {
        if (is_array($this->attributes)) {
            $this->attributes = $attributes;
        }
    }

    /**
     * TYPE: HASH
     * Save the attributes in the cache. Hash data type is used.
     * If the hashed object's key exists, itd gets updated.
     * @return 1 when it's first created, 0 when it's updated.
     */
    public function save()
    {
        foreach ($this->attributes as $name => $value) {
            try {

                Redis::hset($this->key, $name, $value);
                $this->exists = true;

            } catch (\Exception $e) {
                throw new \Exception('Error while caching attributes');
            }
        }
    }

    /**
     * a model exists
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Completely delete all the data associated with the model in storage.
     * @return boolean
     */
    public function delete()
    {
        $response     = Redis::del($this->key);
        $this->exists = false;
        return $response;
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function toJson()
    {
        return json_encode($this->attributes);
    }

    /**
     * Update the model's attributes with values in the cache
     * @return ActiveRedis\Model
     */
    public function fresh()
    {
        $attributes = Redis::hgetall($this->key);

        if (!empty($attributes)) {
            $this->exists = true;
            $this->setAttributes($attributes);
            return $attributes;
        }

    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * set the attribute value in the instance of the model.
     */
    public function __set($key, $value)
    {
        $this->attributes = array_merge($this->attributes, [$key => $value]);
    }
    /**
     * get the attribute value from the model
     * @param  mixed
     */
    public function __get($key)
    {
        return $this->getAttributeValue($key);
    }

    public static function __callstatic($method, $parameters)
    {
        $instance = new static;
        return call_user_func_array([$instance, $method], $parameters);
    }

}
