<?php

namespace ActiveRedis;

use Illuminate\Support\Facades\Redis;

abstract class HasMany
{
    public $who;
    protected $hasMany;
    protected $individualContainerClass;

    public function __construct($who, $hasMany, $individualContainerClass)
    {
        $this->who                      = $who;
        $this->hasMany                  = $hasMany;
        $this->individualContainerClass = $individualContainerClass;
    }

    public function find($element)
    {
        $class = $this->individualContainerClass;
        //create a new instance of the relational model
        $relationalModel = (new $class($element, $this->who));
        return $relationalModel;
    }
    /**
     * provide the chain of relation
     * @return [type] [description]
     */
    public function getRelationalReference()
    {
        return $this->who . ":" . $this->hasMany;
    }

    /**
     * Completely delete all the relational data associated with the model.
     * @return boolean
     */
    public function delete()
    {
        return Redis::del($this->getRelationalReference());
    }

}
