<?php

namespace Shetabit\ModuleGenerator\Classes\Generators;

use Carbon\Carbon;
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
    protected $migrationPath;
    protected  $modelName;
    protected $tableName;

    public function __construct($module, $models)
    {
        $this->models = $models;
        $this->module = $module;
        $this->migrationPath = module_path($this->module) . "/Database/Migrations/";
    }

    public function generate(): string
    {
        if (!key_exists('Models', $this->models)) {
            return '';
        }
        $namespace = new PhpNamespace('');

        foreach ($this->models as $model) {
            $continue = false;
            foreach ($model as $key => $relation) {
                $this->modelName = $key;
                if (!key_exists('Relations', $relation) && empty($relation['Relations'])) {
                    $continue = true;
                }
            }
            if ($continue) {
                continue;
            }
            $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
            $class = $namespace->addClass('AddForeignKeys');
            $class->setExtends(Migration::class);
            $this->foreignKeyGenerator($model, $class);
        }
        if (count($namespace->getClasses()) !== 0) {
            $template = '<?php' . PHP_EOL . $namespace;
            $this->touchAndPutContent($template);
            $this->message .= "|-- Foreign keys successfully generated" . PHP_EOL;
        }

        return $this->message;
    }

    public function foreignKeyGenerator($model , $class)
    {

        $methodUp = $class->addMethod('up');
        foreach ($model as $key => $fields) {
            $this->generateModelTemplates($methodUp , $fields['Relations']);
        }

    }

    public function generateModelTemplates($methodUp, $relationships)
    {
        foreach ($relationships as $key => $models) {
            if (!is_array($models) && Str::camel($models) == 'morphTo'){
                return '';
            }
            foreach ($models as $model) {
                $this->addMethodsInMigration($model , $methodUp);
                if (Str::camel($key) == 'belongsToMany'){
                    $this->createPivot($this->modelName , $models);
                }
                if (Str::camel($key) == 'morphToMany') {
                    $this->createPivot($this->modelName, $models);
                }
            }

        }

    }

    public function addMethodsInMigration($fields , $methodUp)
    {
        $model = explode('::', $fields)[1];

        $methodUp->addBody("Schema::table('".Str::plural(Str::snake($this->modelName))."', function (Blueprint \$table) {");
        $methodUp->addBody("    \$table->foreignId('".strtolower($model)."_id')->constrained('".Str::plural(Str::snake($model))."');");
        $methodUp->addBody("});");
    }

    public function touchAndPutContent($template): bool
    {
        foreach (Finder::create()->files()
                     ->name("*add_foreign_keys.php")
                     ->in($this->migrationPath) as $file) {
            unlink($file->getPathname());
        }
        $tableFileName = Carbon::now()->addSeconds(50)->format('Y_m_d_His_') ."add_foreign_keys.php";

        $pathOfFile = $this->migrationPath.$tableFileName;
        touch($pathOfFile);
        file_put_contents($pathOfFile, $template);

        return true;
    }

    public function createPivot($modelName, $fields): void
    {
        $firstTable = explode('::', $fields[0])[1];
        $pivotTableName = Helper::pivotTableName($modelName, $firstTable);
        $this->tableName = $pivotTableName;
        $className = Str::studly($pivotTableName);
        $namespace = new PhpNamespace('');
        $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
        $class = $namespace->addClass('Create' . $className . 'Table');
        $class->setExtends(Migration::class);
        $this->setMethods($class, $firstTable);
        $template = '<?php' . PHP_EOL . $namespace;
        $this->touchPivotTable($template);
        $this->message .= "|-- Pivot table successfully generated" . PHP_EOL;
    }

    public function setMethods(ClassType $class, $firstTable)
    {
        $methodUp = $class->addMethod('up');
        $methodUp->addBody("Schema::create('" . $this->tableName . "', function (Blueprint \$table) {");
        $methodUp->addBody("    \$table->primary(['" . strtolower($this->modelName) . "_id','" . strtolower($firstTable) . "_id']);");
        $methodUp->addBody("    \$table->foreignId('" . strtolower($this->modelName) . "_id')->constrained('" . Str::plural(Str::snake($this->modelName)) . "');");
        $methodUp->addBody("    \$table->foreignId('" . strtolower($firstTable) . "_id')->constrained('" . Str::plural(Str::snake($firstTable)) . "');");
        $methodUp->addBody("});");

        $methodDown = $class->addMethod('down');
        $methodDown->addBody("Schema::dropIfExists('$this->tableName');");
    }

    public function touchPivotTable($template): bool
    {
        foreach (
            Finder::create()->files()
                ->name("*create_".$this->tableName."_table.php")
                ->in($this->migrationPath) as $file
        ) {
            unlink($file->getPathname());
        }
        $tableFileName = $this->getMigrationName($this->tableName);
        $pathOfFile = $this->migrationPath . $tableFileName;
        touch($pathOfFile);
        file_put_contents($pathOfFile, $template);

        return true;
    }

    public function getMigrationName($tableName): string
    {
        return Carbon::now()->addSeconds(5)->format('Y_m_d_His_') . "create_".$tableName."_table.php";
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
