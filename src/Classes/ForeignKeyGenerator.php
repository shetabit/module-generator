<?php

namespace Shetabit\ModuleGenerator\Classes;

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Traits\CreatePivotTable;
use Symfony\Component\Finder\Finder;

class ForeignKeyGenerator
{

    use CreatePivotTable;

    protected string $message = '';
    protected $models;
    protected $module;
    protected $fields;
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


        foreach ($this->models as $key => $model) {
            foreach ($model as $fields) {
                if (!key_exists('Relations', $fields)) return '';
            }
            $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
            $class = $namespace->addClass('CreateForeignKeysTable');
            $class->setExtends(Migration::class);
            $this->pathOfMigration = module_path($this->module) . "/Database/Migrations/";
            $this->foreignKeyGenerator($model , $class);
        }
        $template = '<?php' . PHP_EOL . $namespace;
        $this->touchAndPutContent($template);
        $this->message .= "|-- ForeignKey successfully generate" . PHP_EOL;
        return $this->message;

    }

    public function foreignKeyGenerator($model , $class)
    {

        $methodUp = $class->addMethod('up');
        foreach ($model as $key => $fields) {
            $this->nameOfModel = $key;
            if (!key_exists('Relations', $fields)) return '';
            $this->generateModelTemplates($methodUp , $fields['Relations']);

        }

    }

    public function generateModelTemplates($methodUp, $fields)
    {
        foreach ($fields as $key => $field) {
            if (!is_array($field) && Str::camel($field) == 'morphTo'){
                return '';
            }
            foreach ($field as $item) {
                $this->addMethodsInMigration($item , $methodUp);
                if(Str::camel($key) == 'belongsToMany'){
                    $this->createPivot($this->nameOfModel , $field);
                }
            }

//            if ($key == 'morphToMany'){
////pivot
//            }

        }

    }

    public function addMethodsInMigration($fields , $methodUp)
    {
        $model = explode('::', $fields)[0];
        $model2 = explode('::', $fields)[1];

        $methodUp->addBody("Schema::table('".Str::plural(Str::snake($this->nameOfModel))."', function (Blueprint \$table) {");
        $methodUp->addBody("\t \$table->foreignId('".strtolower($model2)."_id')->constrained('".Str::plural(Str::snake($model2))."');");
        $methodUp->addBody("});");
    }



    public function touchAndPutContent($template): bool
    {
        foreach (Finder::create()->files()
                     ->name("*create_foreign_keys_table.php")
                     ->in($this->pathOfMigration) as $file) {
            unlink($file->getPathname());
        }
        $tableFileName = Carbon::now()->addYears(1)->format('Y_m_d_His_') ."create_foreign_keys_table.php";

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
