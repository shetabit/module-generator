<?php


namespace Shetabit\ModuleGenerator\Classes;


use App\Http\Controllers\Controller;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
    protected $nameOfMigration;
    protected $pathOfMigration;

    public function __construct($module , $models)
    {
        $this->models = $models;
        $this->module = $module;
    }

    public function generate(): string
    {
        if (!key_exists('Models', $this->models)) return '';
        foreach ($this->models as $key => $model) {
            $this->pathOfMigration = module_path($this->module) . "/Database/Migrations/";
            return $this->MigrationsGenerator($model);
        }
    }

    public function MigrationsGenerator($model): string
    {
        foreach ($model as $key => $fields) {
            $this->nameOfMigration = $key;
            $namespace = new PhpNamespace('');
            $namespace->addUse(Migration::class)->addUse(Blueprint::class)->addUse(Schema::class);
            $class = $namespace->addClass('Create' . Str::plural($this->nameOfMigration) . 'Table');
            $class->setExtends(Migration::class);
            $this->addMethodsInMigration($class, $fields['Fields']);
            $template = '<?php' . PHP_EOL . $namespace;
            $this->touchAndPutContent($template);
            $this->message .= "|-- Migration " . $this->nameOfMigration . " successfully generate" . PHP_EOL;
        }
        return $this->message;
    }

    public function addMethodsInMigration(ClassType $class , $fields)
    {
        $methodUp = $class->addMethod('up')
            ->addBody("Schema::create('".Str::plural(Str::snake($this->nameOfMigration))."', function (Blueprint \$table) {".PHP_EOL."\t \$table->id();");
        $methodUp->addBody($this->addFieldsInMethod($fields));
        $methodUp->addBody("\t \$table->timestamps();".PHP_EOL."});");

        $class->addMethod('down')
            ->addBody("Schema::dropIfExists('".Str::plural(Str::snake($this->nameOfMigration))."');");
        return $class;

    }

    public function addFieldsInMethod($fields)
    {
        foreach($fields as $key => $infoField){
            $field = "\t \$table->".$infoField['type']."('".$key."')";
            if (!key_exists('options', $infoField)) return $field.";";
            return $this->AddOptionsInFields($field  ,$infoField['options']);
        }
        return $fields;
    }

    public function AddOptionsInFields($field , $options)
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
            ->name("*create_".Str::plural(Str::snake($this->nameOfMigration))."_table.php")
            ->in($this->pathOfMigration) as $file) {
            unlink($file->getPathname());
        }
        $tableFileName = date('Y_m_d_His_') ."create_".Str::plural(Str::snake($this->nameOfMigration))."_table.php";
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
