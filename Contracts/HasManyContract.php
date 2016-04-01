<?php
namespace ActiveRedis\Contracts;

interface hasManyContract
{
    public function exists($element);
    public function count();
    public function remove($element);
    public function move($element, $to);
    public function find($element);
}
