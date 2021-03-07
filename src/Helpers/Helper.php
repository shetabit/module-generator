<?php


namespace Shetabit\ModuleGenerator\Helpers;


use Illuminate\Support\Str;

class Helper
{
    public static function checkExistsKeyToGenerate($message, $model, $models, $module): bool
    {
        return ! key_exists($model, $models);
    }

    public static function configurationRelationsName($relationName , $typeRelation)
    {
        if ($typeRelation == ('HasMany' || 'hasManyThrough' || 'belongsToMany' || 'morphMany' || 'morphToMany')){
            $relationName = Str::plural($relationName);
        }
        if ($typeRelation == 'morphTo'){
            $relationName = $relationName.'able';
        }
        return $relationName;
    }

}
