<?php

namespace ActiveRedis;

use ActiveRedis\Contracts\hasManyContract;
use ActiveRedis\Exceptions\ScoreTypeNotValidException;
use Illuminate\Support\Facades\Redis;

class HasManyOrdered extends hasMany implements hasManyContract
{
    protected $scoreType;

    public function forceScoreToBeInstanceOf($scoreType)
    {
        $this->scoreType = $scoreType;
        return $this;
    }

    public function add($element, $score, $hasMany = null)
    {
        if (isset($this->scoreType) && !($score instanceof $this->scoreType)) {
            throw new ScoreTypeNotValidException("Wrong type provided for score. Expected: $this->scoreType");
        }

        $hasMany = $hasMany ?? $this->hasMany;
        return Redis::zadd("{$this->who}:{$hasMany}", $score, $element);
    }
    //NOTE: should be called: last (only one).
    public function latest()
    {
        $result = Redis::zrevrangebyscore("{$this->who}:{$this->hasMany}", '+inf', '-inf', 'LIMIT', '0', '1');
        if (!empty($result)) {
            return $result[0];
        }
    }

    public function exists($element)
    {
        return Redis::zrank("{$this->who}:{$this->hasMany}", $element) === null ? false : true;
    }

    public function count()
    {
        return Redis::zcard("{$this->who}:{$this->hasMany}");
    }
    /**
     * remove one element from many.
     */
    public function remove($element)
    {
        return Redis::zrem("{$this->who}:{$this->hasMany}", $element);
    }

    public function index($element)
    {
        return $this->exists($element);
    }

    public function score($element)
    {
        return Redis::zscore("{$this->who}:{$this->hasMany}", $element);
    }

    public function with($relation, $onlyLatest = null)
    {
        // $this->hasMany = str_singular($this->hasMany);

        // foreach ($instanceIds as $instanceId) {

        //     $instance = $this->find($instanceId);
        //     // user:1:thread:
        //     $relatedModelInstance = call_user_func([$relationClass, $relation]);

        // }

    }

    /**
     * set the limit value of the query
     * @param int
     */
    public function take($value)
    {
        if ($value >= 0) {
            $this->take = $value;
        }
        return $this;
    }

    /**
     * set the offset value of the query
     * @param int
     */
    public function skip($value)
    {
        if ($value >= 0) {
            $this->skip = $value;
        }
        return $this;
    }

    /**
     * All the available elements in the sorted set ordered by the lastest first by default.
     * @param  boolean $latestFirst
     * @return array
     */
    public function all($latestFirst = true)
    {
        $method             = $latestFirst ? 'zrevrangebyscore' : 'zrangebyscore';
        list($first, $last) = $latestFirst ? ['+inf', '-inf'] : ['-inf', '+inf'];
        $args               = ["{$this->who}:{$this->hasMany}", $first, $last];
        return call_user_func_array(['Redis', $method], $args);
    }

    //TODO
    //should be specific for every implementation.
    //e.g: ThreadManyOrderedRedis
    //this could result in integrity violation
    //e.g: move('sent') when it should had been move('sent-threads');
    public function move($element, $to, $score = null)
    {
        $score = $score ?? $this->score($element);
        $this->remove($element);
        return $this->add($element, $score, "{$to}");
    }

}
