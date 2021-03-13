<?php

namespace Shetabit\ModuleGenerator\Classes;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Helpers\Helper;
use Symfony\Component\Finder\Finder;

class ForeignKeyGenerator
{
    protected string $message = '';
    protected $models;
    protected $module;
    protected $fields;
    protected $nameOfMigration;
    protected $pathOfMigration;
    protected  $nameOfModel;

    public function __construct($module, $models)
    {
        $this->models = $models;
        $this->module = $module;
    }

    public function generate(): string
    {
        if (!key_exists('Models', $this->models)) return '';
        $namespace = new PhpNamespace('');
        $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
        $class = $namespace->addClass('CreateForeignKeyTable');
        $class->setExtends(Migration::class);

        foreach ($this->models as $key => $model) {
            $this->pathOfMigration = module_path($this->module) . "/Database/Migrations/";
            $this->nameOfMigration .= $this->foreignKeyGenerator($model , $class);
        }
        echo  $this->nameOfMigration;
        die();
        $template = '<?php' . PHP_EOL . $namespace;
        $this->touchAndPutContent($template);
        $this->message .= "|-- ForeignKey successfully generate" . PHP_EOL;
        return $this->message;

    }

    public function foreignKeyGenerator($model , $class)
    {
        foreach ($model as $key => $fields) {
            if (!key_exists('Relations', $fields)) return '';
           return $this->generateModelTemplates($class , $fields['Relations']);
        }

    }

    public function generateModelTemplates($class, $fields)
    {
        foreach ($fields as $key => $field) {
            foreach ($field as $item) {
               return $this->addMethodsInMigration($class , $item , $key);
            }
//            if($key == 'belongsToMany'){
////povit
//            }
//            if ($key == 'morphToMany'){
//povit
//            }
//
//            (new Helper)->manyToManyTableName('blog' , 'category');
        }
    }

    public function addMethodsInMigration(ClassType $class , $fields , $model)
    {
        $methodUp = $class->addMethod('up')
            ->addBody("Schema::create('foreign_key', function (Blueprint \$table) {".PHP_EOL."\t \$table->id();");
        $methodUp->addBody($this->addFieldsInMethod($fields , $model));
        $methodUp->addBody(PHP_EOL."});");

        $class->addMethod('down')
            ->addBody("Schema::dropIfExists('foreign_key');");

        return $class;

    }

    public function addFieldsInMethod($fields , $model)
    {
        $model = explode('::', $fields)[0];
        $model2 = explode('::', $fields)[1];
//       $table->foreignId('user_id')->constrained('users');
        $field = "\t \$table->foreignId('".strtolower($model)."_id')->constrained('".Str::plural(Str::snake($model2))."');";
        return $field;
    }


    public function touchAndPutContent($template): bool
    {
//        foreach (Finder::create()->files()
//                     ->name("*create_".Str::plural(Str::snake($this->nameOfMigration))."_table.php")
//                     ->in($this->pathOfMigration) as $file) {
//            unlink($file->getPathname());
//        }
        $tableFileName = "create_foreign_key_table.php";
        $pathOfFile = $this->pathOfMigration.$tableFileName;
        touch($pathOfFile);
        file_put_contents($pathOfFile, $template);
        return true;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
