<?php


namespace Shetabit\ModuleGenerator\Helpers;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Helper
{
    public static $indent = '    ';

    public static function checkExistsKeyToGenerate($message, $model, $models, $module): bool
    {
        return ! key_exists($model, $models);
    }

    public static function configurationRelationsName($relationName , $typeRelation): string
    {
        if ($typeRelation == 'morphTo'){
            return $relationName = $relationName.'able';
        }
        if ($typeRelation == ('HasMany' || 'hasManyThrough' || 'belongsToMany' || 'morphMany' || 'morphToMany')){
            return $relationName = Str::plural($relationName);
        }

        return $relationName;
    }

    public static function pivotTableName($first , $second, $separator = '_'): string
    {
        $segments = [
            Str::snake($first),
            Str::snake($second)
        ];
        sort($segments);

        return strtolower(implode($separator, $segments));
    }
}
