<?php

namespace Shetabit\ModuleGenerator\Classes;


use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ControllerGenerator
{

    public string $message = '';
    protected $models;
    protected $module;
    protected $pathOfController;
    protected $CRUD;
    protected $nameController;

    public function __construct($module , $models)
    {
        $this->models = $models['Models'];
        $this->module = $module;
    }

    public function generate(): string
    {
        foreach ($this->models as $model => $attribute) {
            if (!key_exists('CRUD', $attribute)) return '';
            $this->CRUD = $attribute['CRUD'];
            return $this->ControllerGenerator($this->module);
        }
    }



    public function ControllerGenerator($module): string
    {
        foreach ($this->CRUD as $name => $option) {
            $this->nameController = $name;
            $this->pathOfController = module_path($module) . "/Http/Controllers/".$this->nameController."/";
            $template =  $this->ControllerTemplateGenerator($option[0]);
            $template = '<?php' . PHP_EOL . $template;
            $this->createDirectory();
            $this->touchAndPutContent($template);
            $this->message .=  "|-- ".$this->nameController."Controller successfully generate" . PHP_EOL;
        }
        return $this->message;
    }

    public function ControllerTemplateGenerator($option): PhpNamespace
    {
        $namespace = new PhpNamespace('Modules\\' . $this->module . '\Http\Controllers\\'.$this->nameController);
        $namespace->addUse(Controller::class)
                    ->addUse(Request::class);
        $class = $namespace->addClass($this->nameController."Controller");
        $class->setExtends(Controller::class);

         $this->setMethodToController($class , $option);

        return $namespace;
    }

    public function setMethodToController($class , $option )
    {
        for ($i = 0; $i< strlen($option) ; $i++){
            if ($option[$i] == 'R'){
                $this->indexAndShowMethodGenerator($class);
            }
            if ($option[$i] == 'C'){
                $this->createAndStoreMethodGenerator($class);
            }
            if ($option[$i] == 'U'){
                $this->editAndUpdateMethodGenerator($class);
            }
            if ($option[$i] == 'D'){
                $this->destroyMethodGenerator($class);
            }
        }
    }

    public function indexAndShowMethodGenerator(classType $class)
    {
        $class->addMethod('index');
        $class->addMethod('show')
            ->addParameter('id')->setType('Int');
    }


    public function createAndStoreMethodGenerator(ClassType $class)
    {
        $class->addMethod('create');
        $method = $class->addMethod('store')
                ->addComment('Store a newly created resource in storage')
                ->addComment('@param Request $request');
        $method->addParameter('request')->setType(Request::class);
    }
    public function editAndUpdateMethodGenerator(ClassType $class)
    {
        $class->addMethod('edit');
        $method = $class->addMethod('update')
            ->addComment('Update the specified resource in storage.')
            ->addComment('@param Request $request')
            ->addComment('@param int $id');
        $method->addParameter('request')->setType('Request');
        $method->addParameter('id')->setType('Int');
    }
    public function destroyMethodGenerator(ClassType $class)
    {
        $class->addMethod('destroy')->addParameter('id')->setType('Int');
    }

    public function touchAndPutContent($template): bool
    {
        touch($this->pathOfController. $this->nameController.'Controller.php');
        file_put_contents($this->pathOfController. $this->nameController.'Controller.php', $template);
        return true;
    }

    public function createDirectory()
    {
        if (!is_dir($this->pathOfController))
        {
            mkdir($this->pathOfController, 0777, true);
        }
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
