<?php

namespace Shetabit\ModuleGenerator\Classes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Helpers\Helper;


class ModelGenerator
{

    public string $message = '';
    protected $models;
    protected $module;
    protected $pathOfModel;
    protected array $withRelation = [];

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
            $this->addWithCommonRelations($class);
            $this->setRelationsInModel($namespace, $class, $attribute);
        }
        $class->addProperty('commonRelations' , $this->withRelation)->setType('array')->setProtected()->setStatic();

        return $namespace;
    }

    public function setFallibleInModel($class, $attribute, $namespace)
    {

        foreach ($attribute['Fields'] as $key => $item) {
            $fallible[] = $key;
        }

        $class->addProperty('fallible', $fallible)->setType('array')->setProtected();
        $this->touchAndPutContent('<?php' . PHP_EOL . $namespace);
        return $namespace;
    }

    public function touchAndPutContent($template)
    {
        touch($this->pathOfModel);
        file_put_contents($this->pathOfModel, $template);
    }

    public function setRelationsInModel($namespace, ClassType $class , $attribute)
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

                $relationName = strtolower(Helper::configurationRelationsName($baseRelationName, $typeRelation));
                $this->withRelation[] = $relationName;
                $namespace->addUse('Modules\\' . Str::camel($relationModel) . '\Entities\\' . $relationName);
                $class->addMethod($relationName)
                    ->addBody('return $this->' . Str::camel($typeRelation) . '(' . $baseRelationName . '::class);')
                    ->setReturnType('Illuminate\Database\Eloquent\Relations\\' . $typeRelation);
            }
        }
        return $namespace;

    }

    public function __toString(): string
    {
        return $this->message;
    }

    public function addWithCommonRelations(ClassType $class)
    {
        $class->addMethod('scopeWithCommonRelations')
            ->addBody('if (isset(static::$commonRelations) && !empty(static::$commonRelations)) {')
            ->addBody("\t\$query->with(static::\$commonRelations);")
            ->addBody('}')
            ->addParameter('query');
    }
}
