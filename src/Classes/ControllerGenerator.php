<?php

namespace Shetabit\ModuleGenerator\Classes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Helpers\Helper;

class ControllerGenerator
{

    public string $message = '';
    protected $models;
    protected $modelName;
    protected $module;
    protected $pathOfController;
    protected $CRUD;
    protected $nameController;
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
        foreach ($this->models as $model => $this->attributes) {
            $this->modelName = $model;
            if (!key_exists('CRUD', $this->attributes)) {
                return '';
            }
            $this->CRUD = $this->attributes['CRUD'];

            return $this->controllerGenerator($this->module);
        }

        return '';
    }

    public function controllerGenerator($module): string
    {
        foreach ($this->CRUD as $name => $option) {
            $this->nameController = $name;
            $this->pathOfController = module_path($module) . "/Http/Controllers/" . $this->nameController . "/";
            $template = $this->generateControllerTemplates($option[0]);
            $template = '<?php' . PHP_EOL . $template;
            $this->createDirectory();
            $this->touchAndPutContent($template);
            $this->message .= "|-- " . $this->nameController . "Controller successfully generated" . PHP_EOL;
        }

        return $this->message;
    }

    public function generateControllerTemplates($option): PhpNamespace
    {
        $namespace = new PhpNamespace('Modules\\' . $this->module . '\Http\Controllers\\' . $this->nameController);
        $namespace->addUse(Controller::class)
            ->addUse(Request::class)
            ->addUse('Modules\\' . $this->module . '\Entities\\' . $this->modelName);
        $class = $namespace->addClass($this->nameController . "Controller");
        $class->setExtends(Controller::class);

        $this->setMethodToController($class, $option, $namespace);

        return $namespace;
    }

    public function setMethodToController($class, $option, $namespace)
    {
            if (str_contains($option , 'R')) {
                $this->indexAndShowMethodGenerator($class);
            }
            if (str_contains($option, 'C')) {
                $this->createAndStoreMethodGenerator($class);
            }
            if (str_contains($option, 'U')) {
                $this->editAndUpdateMethodGenerator($class , $namespace);
            }
            if (str_contains($option, 'D')) {
                $this->destroyMethodGenerator($class);
            }
    }

    public function indexAndShowMethodGenerator(classType $class)
    {
        $method = $class->addMethod('index');
        if (key_exists('Relations', $this->attributes)) {
            $method->addBody('$' . strtolower($this->modelName) . 's = ' . ucfirst($this->modelName) . '::withCommonRelations()->get();')
                ->addBody($this->getReturnStatement(true));
        } else {
            $method->addBody('$' . strtolower($this->modelName) . 's = ' . ucfirst($this->modelName) . '::query()->get();')
                ->addBody($this->getReturnStatement(true));
        }
        $class->addMethod('show')
            ->addBody('$' . strtolower($this->modelName) . ' = ' . ucfirst($this->modelName) . '::query()->findOrFail($id);')
            ->addBody($this->getReturnStatement())
            ->addParameter('id')->setType('Int');
    }


    public function createAndStoreMethodGenerator(ClassType $class): void
    {
        $class->addMethod('create');
        if (!key_exists('Relations', $this->attributes)) {
            $method = $class->addMethod('store')
                ->addComment('Store a newly created resource in storage')
                ->addComment('@param Request $request')
                ->addBody($this->getReturnStatement())
                ->addParameter('request')->setType(Request::class);
            return;
        }
        $method = $class->addMethod('store')
            ->addBody('$' . strtolower($this->modelName) . ' = new ' . ucfirst($this->modelName) . '();')
            ->addBody('$' . strtolower($this->modelName) . '->fill($request->all());');
        $this->associateInStore($method);
        $method->addBody('$' . strtolower($this->modelName) . '->save();')
            ->addComment('Store a newly created resource in storage')
            ->addComment('@param Request $request')
            ->addBody($this->getReturnStatement())
            ->addParameter('request')->setType(Request::class);
    }

    public function associateInStore($method): void
    {
        if (key_exists('Relations', $this->attributes)) {
            foreach ($this->attributes['Relations'] as $typeRelation => $relations) {
                if (!is_array($relations) && Str::camel($relations) == 'morphTo'){
                    return;
                }
                foreach ($relations as $value) {
                    $this->baseRelationName = explode('::', $value)[1];
                    $this->relationName = Helper::configurationRelationsName($this->baseRelationName, $typeRelation);
                    $method->addBody('$' . strtolower($this->modelName) . '->' . strtolower($this->relationName) . '()->associate($request->' . strtolower($this->baseRelationName) . '_id);');
                }
            }
        }
    }


    public function editAndUpdateMethodGenerator(ClassType $class , $namespace)
    {
        $method = $class->addMethod('edit');
        if (key_exists('Relations', $this->attributes)) {
            $method->addBody('$' . strtolower($this->modelName) . ' = ' . ucfirst($this->modelName) . '::withCommonRelations()->findOrFail($id);')
                ->addBody($this->getReturnStatement());
        } else {
            $method->addBody('$' . strtolower($this->modelName) . ' = ' . ucfirst($this->modelName) . '::query()->findOrFail($id);')
                ->addBody($this->getReturnStatement());
        };
        $method->addParameter('id')->setType('Int');

        $method = $class->addMethod('update')
            ->addBody('$' . strtolower($this->modelName) . ' = ' . ucfirst($this->modelName) . '::query()->findOrFail($id);');

        $this->UpdateMethodFindIntoRelation($method , $namespace);
        $this->associateInUpdate($method);
        $method->addBody('$' . strtolower($this->modelName) . '->fill($request->all());')
            ->addBody('$' . strtolower($this->modelName) . '->save();')
            ->addBody($this->getReturnStatement())
            ->addComment('Update the specified resource in storage.')
            ->addComment('@param Request $request')
            ->addComment('@param int $id');
        $method->addParameter('request')->setType(Request::class);
        $method->addParameter('id')->setType('Int');
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
                    $method->addBody('$' . strtolower($this->modelName) . '->' . strtolower($this->relationName) . '()->associate($' . strtolower($this->baseRelationName) . ');');
                }
            }
        }
    }

    public function destroyMethodGenerator(ClassType $class)
    {
        $class->addMethod('destroy')
            ->addBody('$' . strtolower($this->modelName) . ' = ' . ucfirst($this->modelName) . '::destroy($id);')
                ->addBody($this->getReturnStatement())
            ->addParameter('id')->setType('Int');
    }

    public function createDirectory()
    {
        if (!is_dir($this->pathOfController)) {
            mkdir($this->pathOfController, 0775, true);
        }
    }

    public function touchAndPutContent($template): bool
    {
        touch($this->pathOfController . $this->nameController . 'Controller.php');
        file_put_contents($this->pathOfController . $this->nameController . 'Controller.php', $template);
        return true;
    }

    public function getReturnStatement($plural = false): string
    {
        if (str_contains($this->return, ':data')) {
            $modelNameInReturn = $plural ? Str::plural(Str::camel($this->modelName)) : Str::camel($this->modelName);

            return PHP_EOL . str_replace(':data', '$' . $modelNameInReturn, $this->return);
        }

        return $this->return;
    }

    // It comes before return statement to initialize $data
    public function getDataStatement(): string
    {
        return '$data = $' . $this->modelName . ';';
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
