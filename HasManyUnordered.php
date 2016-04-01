<?php

namespace ActiveRedis;

use ActiveRedis\Contracts\hasManyContract;
use ActiveRedis\HasMany;
use Illuminate\Support\Facades\Redis;

class HasManyUnordered extends HasMany implements hasManyContract
{
    public function add($element, $hasMany = null)
    {
        $hasMany = $hasMany ?? $this->hasMany;
        return Redis::sadd("{$this->who}:{$hasMany}", $element);
    }

    public function exists($element)
    {

    }
    public function count()
    {

    }
    public function remove($element)
    {

    }
    public function all()
    {
        return Redis::smembers("$this->who:{$this->hasMany}");
    }
    public function move($element, $to)
    {

    }

}
