<?php


namespace Shetabit\ModuleGenerator\Helpers;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Helper
{
    const alphabet = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    public int $firstAlpha = 0 ;
    public int $secondAlpha = 0 ;
    public static function checkExistsKeyToGenerate($message, $model, $models, $module): bool
    {
        return ! key_exists($model, $models);
    }

    public static function configurationRelationsName($relationName , $typeRelation)
    {
        if ($typeRelation == 'morphTo'){
            return $relationName = $relationName.'able';
        }
        if ($typeRelation == ('HasMany' || 'hasManyThrough' || 'belongsToMany' || 'morphMany' || 'morphToMany')){
            return $relationName = Str::plural($relationName);
        }

        return $relationName;
    }

    public function pivotTableName($first , $second, $separator = '_'): string
    {
        $this->firstAlpha = array_search(strtoupper($first[0]) , self::alphabet);
        $this->secondAlpha = array_search(strtoupper($second[0]) , self::alphabet);

        if ($this->firstAlpha > $this->secondAlpha) return strtolower($second).$separator.strtolower($first);
        if ($this->firstAlpha < $this->secondAlpha) return strtolower($first).$separator.strtolower($second);
        if ($this->firstAlpha == $this->secondAlpha) return strtolower($first).$separator.strtolower($second);
    }

}
