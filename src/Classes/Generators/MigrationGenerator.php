<?php

namespace Shetabit\ModuleGenerator\Classes\Generators;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Finder\Finder;

class MigrationGenerator
{
    protected string $message = '';
    protected $models;
    protected $module;
    protected $fields;
    protected $migrationName;
    protected $migrationPath;

    public function __construct($module , $models)
    {
        $this->models = $models;
        $this->module = $module;
    }

    public function generate(): string
    {
        if (!key_exists('Models', $this->models)) {
            return '';
        }
        foreach ($this->models as $key => $model) {
            $this->migrationPath = module_path($this->module) . "/Database/Migrations/";

            return $this->migrationsGenerator($model);
        }

        return '';
    }

    public function migrationsGenerator($model): string
    {
        foreach ($model as $key => $fields) {
            $relation = null;
            $relations = $fields['Relations'] ?? [];
            $this->migrationName = $key;
            $namespace = new PhpNamespace('');
            $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
            $class = $namespace->addClass('Create' . Str::plural($this->migrationName) . 'Table');
            $class->setExtends(Migration::class);
            $this->addMethodsInMigration($class, $fields['Fields'] , $relations);
            $template = '<?php' . PHP_EOL . $namespace;
            $this->touchAndPutContent($template);
            $this->message .= "|-- Migration " . $this->migrationName . " successfully generated" . PHP_EOL;
        }

        return $this->message;
    }

    public function addMethodsInMigration(ClassType $class , $fields , $relation)
    {

        $methodUp = $class->addMethod('up')
         ->addBody("Schema::create('".Str::plural(Str::snake($this->migrationName))."', function (Blueprint \$table) {".PHP_EOL."    \$table->id();");
        $methodUp->addBody($this->addFieldsInMethod($fields));
        if ($relation != null) {
            foreach ($relation as $key => $item) {
                if (!is_array($item) && Str::camel($item) == 'morphTo') {
                    $methodUp->addBody("    \$table->integer('" . strtolower($this->migrationName) . "able_id');");
                    $methodUp->addBody("    \$table->string('" . strtolower($this->migrationName) . "able_type');");

                }
            }
        }
        $methodUp->addBody("    \$table->timestamps();".PHP_EOL."});");
        $class->addMethod('down')
            ->addBody("Schema::dropIfExists('".Str::plural(Str::snake($this->migrationName))."');");
        return $class;

    }

    public function addFieldsInMethod($fields)
    {
        $fieldsString = '';
        foreach($fields as $key => $infoField){
            $field = "    \$table->".$infoField['type']."('".$key."')";
            if (!key_exists('options', $infoField)) {
                $field .= ";";
            } else {
                $field =  $this->addOptionsInFields($field  ,$infoField['options']);
            }
            $fieldsString .= $field . PHP_EOL;
        }
        return $fieldsString;
    }

    public function addOptionsInFields($field , $options)
    {
        foreach ($options as $key => $value) {
            if (!is_numeric($key)){
                $field .="->".$key."(".$value.")";
            }elseif(is_numeric($key)){
                $field .="->".$value."()";
            }
        }
        return $field.";";
    }

    public function touchAndPutContent($template): bool
    {
        foreach (Finder::create()->files()
            ->name("*create_".Str::plural(Str::snake($this->migrationName))."_table.php")
            ->in($this->migrationPath) as $file) {
            unlink($file->getPathname());
        }
        $tableFileName = date('Y_m_d_His_') ."create_".Str::plural(Str::snake($this->migrationName))."_table.php";
        $pathOfFile = $this->migrationPath.$tableFileName;
        touch($pathOfFile);
        file_put_contents($pathOfFile, $template);
        return true;
    }

    public function __toString(): string
    {
       return $this->message;
    }
}
