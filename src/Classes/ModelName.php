<?php


namespace Shetabit\ModuleGenerator\Classes;


use Illuminate\Support\Str;

class ModelName
{
    public $originalModelName;

    public function __construct($modelName)
    {
        $this->originalModelName = Str::studly($modelName);
    }

    public function getPluralForRoute()
    {
        return Str::snake(Str::plural($this->originalModelName));
    }

    public function getPluralForController()
    {
        return Str::plural(Str::camel($this->originalModelName));
    }

    public function getSingularForController()
    {
        return Str::singular(Str::camel($this->originalModelName));
    }

    public function __toString()
    {
        return $this->originalModelName;
    }
}
