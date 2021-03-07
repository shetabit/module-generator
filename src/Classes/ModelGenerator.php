<?php

namespace Shetabit\ModuleGenerator\Classes;

use Illuminate\Database\Eloquent\Model;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Helpers\Helper;


class ModelGenerator
{

    public string $message = '';
    protected $models;
    protected $module;
    protected $pathOfModel;

    public function __construct($module , $models)
    {
        $this->models = $models;
        $this->module = $module;
    }

    public function generate(): string
    {
        if (!key_exists('Models', $this->models)) return '';
        return $this->generateModels($this->models['Models']);
    }

    public function generateModels($models): string
    {
        foreach ($models as $model => $attribute) {
            $this->pathOfModel = module_path($this->module) . "/Entities/" . $model . '.php';

            $template = $this->generateModelTemplates($model, $attribute);
            $template = '<?php' . PHP_EOL . $template;
            $this->touchAndPutContent($template);
            $this->message .= "|-- Model " . $model . " successfully generate" . PHP_EOL;
        }

        return $this->message;
    }

    public function generateModelTemplates(string $model, array $attribute): string
    {
        $namespace = new PhpNamespace('Modules\\' . $this->module . '\Entities');
        $namespace->addUse('Illuminate\Database\Eloquent\Model');
        $class = $namespace->addClass($model);   //create your Model
        $class->setExtends(Model::class);

        //check exists Fields key in attribute array
        if (key_exists('Fields', $attribute)) {
            $namespace = $this->setFallibleInModel($class, $attribute, $namespace);
        }
        //check exists Relations key in attribute array
        if (key_exists('Relations', $attribute)) {
            $this->setRelationsInModel($namespace, $class, $attribute);
        }
        return $namespace;
    }

    public function setFallibleInModel($class, $attribute, $namespace)
    {

        foreach ($attribute['Fields'] as $key => $item) {
            $fallible[] = $key;
        }

        $class->addProperty('fallible', $fallible)->setProtected();
        $this->touchAndPutContent('<?php' . PHP_EOL . $namespace);
        return $namespace;
    }

    public function touchAndPutContent($template)
    {
        touch($this->pathOfModel);
        file_put_contents($this->pathOfModel, $template);
    }

    public function setRelationsInModel($namespace, $class , $attribute)
    {
        foreach ($attribute['Relations'] as $typeRelation => $relations) {
            foreach ($relations as $value) {
                /**
                 * @variable relationName Return name of relation example =>  Category
                 * @variable relationModel Return name of model for relation example =>  Blog
                 * @helper configurationRelationsName plural name of relation  example => Categories
                 */

                $relationModel = explode('::', $value)[0];
                $baseRelationName = explode('::', $value)[1];

                $relationName = Helper::configurationRelationsName($baseRelationName, $typeRelation);

                $namespace->addUse('Modules\\' . $relationModel . '\Entities\\' . $relationName);
                // Now Create Function Of Relation
                $method = $class->addMethod($relationName);
                $method->addBody('return $this->' . $typeRelation . '(' . $baseRelationName . '::class);')
                    ->setReturnType('Illuminate\Database\Eloquent\Relations\\' . $typeRelation);
                return $namespace;
            }
        }
        return $namespace;

    }

    public function __toString(): string
    {
        return $this->message;
    }
}
