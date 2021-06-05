<?php

namespace Shetabit\ModuleGenerator\Classes\Generators;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Classes\ModelName;
use Shetabit\ModuleGenerator\Helpers\Helper;

class ControllerGenerator
{

    public string $message = '';
    protected $models;
    protected ModelName $modelName;
    protected $module;
    protected $pathOfController;
    protected $CRUD;
    /**
     * @var mixed|string
     */
    protected $relationName;
    /**
     * @var mixed|string
     */
    protected $baseRelationName;
    protected $attributes;
    protected $return;

    public function __construct($module, $models)
    {
        $this->models = $models['Models'];
        $this->module = $module;
        $config = \config('generator');
        $this->return = $config['return_statement'];
    }

    public function generate(): string
    {
        $message = '';
        foreach ($this->models as $model => $this->attributes) {
            $this->modelName = new ModelName($model);
            if (!key_exists('CRUD', $this->attributes)) {
                return '';
            }
            $this->CRUD = $this->attributes['CRUD'];

            $message .= $this->controllerGenerator($this->module);
        }

        return $message;
    }

    public function controllerGenerator($module): string
    {
        $message = '';
        foreach ($this->CRUD as $name => $option) {
            $this->pathOfController = module_path($module) . "/Http/Controllers/" . $name . '/';
            $template = $this->generateControllerTemplates($option[0], $name);
            $template = '<?php' . PHP_EOL . $template;
            $this->createDirectory();
            $this->touchAndPutContent($template);
            $message .= "|-- " . $this->modelName . "Controller ({$name}) successfully generated" . PHP_EOL;
        }

        return $message;
    }

    public function generateControllerTemplates($option, $name): PhpNamespace
    {
        $namespace = new PhpNamespace('Modules\\' . $this->module . '\Http\Controllers\\' . $name);
        $namespace->addUse(Controller::class)
            ->addUse(Request::class)
            ->addUse('Modules\\' . $this->module . '\Entities\\' . $this->modelName);
        if ($this->hasCreate($option)) {
            $namespace->addUse($this->getStoreRequestNamespace($name));
        }
        if ($this->hasUpdate($option)) {
            $namespace->addUse($this->getUpdateRequestNamespace($name));
        }
        $class = $namespace->addClass($this->modelName . "Controller");
        $class->setExtends(Controller::class);

        $this->setMethodToController($class, $option, $namespace, $name);

        return $namespace;
    }

    public function setMethodToController($class, $option, $namespace, $userName)
    {
            if (str_contains($option , 'R')) {
                $this->indexAndShowMethodGenerator($class);
            }
            if ($this->hasCreate($option)) {
                $this->storeMethodGenerator($class, $userName);
            }
            if ($this->hasUpdate($option)) {
                $this->updateMethodGenerator($class , $namespace, $userName);
            }
            if (str_contains($option, 'D')) {
                $this->destroyMethodGenerator($class);
            }
    }

    public function indexAndShowMethodGenerator(classType $class)
    {
        $method = $class->addMethod('index');
        if (key_exists('Relations', $this->attributes) && !empty($this->attributes['Relations'])) {
            $method->addBody('$' . $this->modelName->getPluralForController() . ' = ' . $this->modelName . '::withCommonRelations()->get();')
                ->addBody($this->getReturnStatement(true));
        } else {
            $method->addBody('$' . $this->modelName->getPluralForController() . ' = ' . $this->modelName . '::query()->get();')
                ->addBody($this->getReturnStatement(true));
        }
        $method = $class->addMethod('show');
        if (key_exists('Relations', $this->attributes) && !empty($this->attributes['Relations'])) {
            $method->addBody('$' . $this->modelName->getSingularForController() . ' = ' . $this->modelName . '::withCommonRelations()->findOrFail($id);');
        } else {
            $method->addBody('$' .  $this->modelName->getSingularForController() . ' = ' . $this->modelName . '::findOrFail($id);');
        }
        $method->addBody($this->getReturnStatement())
            ->addParameter('id');
    }


    public function storeMethodGenerator(ClassType $class, $userName): void
    {
        $method = $class->addMethod('store')
            ->addBody('$' . $this->modelName->getSingularForController() . ' = new ' . $this->modelName . '();')
            ->addBody('$' . $this->modelName->getSingularForController() . '->fill($request->all());');
        $this->associateInStore($method);
        $method->addBody('$' . $this->modelName->getSingularForController() . '->save();')
            ->addComment('Store a newly created resource in storage')
            ->addComment('@param Request $request')
            ->addBody($this->getReturnStatement())
            ->addParameter('request')
            ->setType($this->getStoreRequestNamespace($userName));
    }

    public function associateInStore($method): void
    {
        if (key_exists('Relations', $this->attributes)) {
            foreach ($this->attributes['Relations'] as $typeRelation => $relations) {
                if ((!is_array($relations) && Str::camel($relations) == 'morphTo') || $this->doesRelationHaveAssociate($relations)){
                    return;
                }
                foreach ($relations as $value) {
                    $this->baseRelationName = explode('::', $value)[1];
                    $this->relationName = Helper::configurationRelationsName($this->baseRelationName, $typeRelation);
                    $method->addBody('$' . $this->modelName->getSingularForController() . '->' . Str::camel($this->relationName) . '()->associate($request->' . strtolower($this->baseRelationName) . '_id);');
                }
            }
        }
    }


    public function updateMethodGenerator(ClassType $class , $namespace, $userName)
    {
        $method = $class->addMethod('update')
            ->addBody('$' . $this->modelName->getSingularForController() . ' = ' . ucfirst($this->modelName) . '::query()->findOrFail($id);');

        $this->UpdateMethodFindIntoRelation($method , $namespace);
        $this->associateInUpdate($method);
        $method->addBody('$' . $this->modelName->getSingularForController() . '->fill($request->all());')
            ->addBody('$' . $this->modelName->getSingularForController() . '->save();')
            ->addBody($this->getReturnStatement())
            ->addComment('Update the specified resource in storage.')
            ->addComment('@param Request $request')
            ->addComment('@param $id');
        $method->addParameter('request')
            ->setType($this->getUpdateRequestNamespace($userName));
        $method->addParameter('id');
    }


    public function UpdateMethodFindIntoRelation($method ,$namespace): void
    {
        if (key_exists('Relations', $this->attributes)) {
            foreach ($this->attributes['Relations'] as $typeRelation => $relations) {
                if (!is_array($relations) && Str::camel($relations) == 'morphTo'){
                    return;
                }
                foreach ($relations as $value) {
                    $this->baseRelationName = explode('::', $value)[1];
                    $method->addBody('$' . strtolower($this->baseRelationName) . ' = ' . ucfirst($this->baseRelationName) . '::query()->findOrFail($request->' . strtolower($this->baseRelationName) . '_id);');
                    $namespace->addUse('Modules\\' . $this->module . '\Entities\\' . ucfirst($this->baseRelationName));
                }
            }
        }
    }

    public function associateInUpdate($method): void
    {
        if (key_exists('Relations', $this->attributes)) {
            foreach ($this->attributes['Relations'] as $typeRelation => $relations) {
                if (!is_array($relations) && Str::camel($relations) == 'morphTo'){
                    return;
                }
                foreach ($relations as $value) {
                    $this->baseRelationName = explode('::', $value)[1];
                    $this->relationName = Helper::configurationRelationsName($this->baseRelationName, $typeRelation);
                    $method->addBody('$' . $this->modelName->getSingularForController() . '->' . strtolower($this->relationName) . '()->associate($' . strtolower($this->baseRelationName) . ');');
                }
            }
        }
    }

    public function destroyMethodGenerator(ClassType $class)
    {
        $class->addMethod('destroy')
            ->addBody('$' . $this->modelName->getSingularForController() . ' = ' . ucfirst($this->modelName) . '::findOrFail($id)->delete();')
                ->addBody($this->getReturnStatement())
            ->addParameter('id');
    }

    public function createDirectory()
    {
        if (!is_dir($this->pathOfController)) {
            mkdir($this->pathOfController, 0775, true);
        }
    }

    public function touchAndPutContent($template): bool
    {
        touch($this->pathOfController . $this->modelName . 'Controller.php');
        file_put_contents($this->pathOfController . $this->modelName . 'Controller.php', $template);

        return true;
    }

    public function getReturnStatement($plural = false): string
    {
        if (str_contains($this->return, ':data')) {
            $modelNameInReturn = $plural ? $this->modelName->getPluralForController() : $this->modelName->getSingularForController();

            return PHP_EOL . str_replace(':data', '$' . $modelNameInReturn, $this->return);
        }

        return $this->return;
    }

    public function getStoreRequestNamespace($userName)
    {
        return 'Modules\\' . $this->module . '\Http\Requests\\' . $userName . '\\' . "{$this->modelName}StoreRequest";
    }

    public function getUpdateRequestNamespace($userName)
    {
        return 'Modules\\' . $this->module . '\Http\Requests\\' . $userName . '\\'  . "{$this->modelName}UpdateRequest";
    }

    public function hasCreate($option)
    {
        return str_contains($option, 'C');
    }

    public function hasUpdate($option)
    {
        return str_contains($option, 'U');
    }

    public function doesRelationHaveAssociate($relation)
    {
        return !in_array(Str::camel($relation), ['hasOne', 'hasMany', 'morphTo']);
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
