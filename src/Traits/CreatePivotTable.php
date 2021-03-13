<?php


namespace Shetabit\ModuleGenerator\Traits;


use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Helpers\Helper;
use Symfony\Component\Finder\Finder;

Trait CreatePivotTable
{
    protected $tableName;

    public function createPivot($modelName, $fields)
    {
        $firstTable = explode('::', $fields[0])[1];
        $this->tableName = (new Helper())->pivotTableName($modelName, $firstTable);
        $className = Str::studly((new Helper())->pivotTableName($modelName, $firstTable));
        $namespace = new PhpNamespace('');
        $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
        $class = $namespace->addClass('Create' . $className . 'Table');
        $class->setExtends(Migration::class);

        $this->setMethods($class, $firstTable);
        $template = '<?php' . PHP_EOL . $namespace;
        $this->touchPivotTable($template);
        $this->message .= "|-- Pivot table successfully generate" . PHP_EOL;
    }

    public function setMethods(ClassType $class, $firstTable)
    {
        $methodUp = $class->addMethod('up');
        $methodUp->addBody("Schema::create('" . $this->tableName . "', function (Blueprint \$table) {");
        $methodUp->addBody("\t \$table->primary(['" . strtolower($this->nameOfModel) . "_id','" . strtolower($firstTable) . "_id']);");
        $methodUp->addBody("\t \$table->foreignId('" . strtolower($this->nameOfModel) . "_id')->constrained('" . Str::plural(Str::snake($this->nameOfModel)) . "');");
        $methodUp->addBody("\t \$table->foreignId('" . strtolower($firstTable) . "_id')->constrained('" . Str::plural(Str::snake($firstTable)) . "');");
        $methodUp->addBody("});");

        $methodDown = $class->addMethod('down');
        $methodDown->addBody("Schema::dropIfExists('$this->tableName');");
    }

    public function touchPivotTable($template): bool
    {
        foreach (Finder::create()->files()
                     ->name("*create_".$this->tableName."_table.php")
                     ->in($this->pathOfMigration) as $file) {
            unlink($file->getPathname());
        }
        $tableFileName = Carbon::now()->addYears(1)->format('Y_m_d_His_') . "create_".$this->tableName."_table.php";

        $pathOfFile = $this->pathOfMigration . $tableFileName;
        touch($pathOfFile);
        file_put_contents($pathOfFile, $template);
        return true;
    }
}
