<?php
namespace Shetabit\ModuleGenerator\Classes\Generators;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use League\Flysystem\Config;
use function PHPUnit\Framework\isEmpty;


class ModuleGenerator
{
    protected $config;

    /**
     * @var array $modules
     */
    protected $modules;

    protected $result = '';

    public function __construct()
    {
        $this->config = \config()->get('generator');
        $this->modules = $this->config['Modules'];
    }


    public function generate()
    {
        $this->ModulesGenerator($this->modules);
    }

    public function ModulesGenerator($modules)
    {
        foreach ($modules as $module => $model){
            Artisan::call('module:make ' . $module);  // ModulesGenerator
            $this->result .= "* Modules ".$module." successfully generated".PHP_EOL;

            if (key_exists('Models', $model)) {

                $this->result .= app(\Shetabit\ModuleGenerator\Contracts\ModelGenerator::class, [$module, $model])
                    ->generate();

                $this->result .= app(\Shetabit\ModuleGenerator\Contracts\ControllerGenerator::class, [$module, $model])
                    ->generate();

                $this->result .= app(\Shetabit\ModuleGenerator\Contracts\MigrationGenerator::class, [$module, $model])
                    ->generate();

                $this->result .= app(\Shetabit\ModuleGenerator\Contracts\RouteGenerator::class, [$module, $model])
                    ->generate();
                $this->result .= app(\Shetabit\ModuleGenerator\Contracts\RequestGenerator::class, [$module, $model])
                    ->generate();

                $this->result .= app(\Shetabit\ModuleGenerator\Contracts\ForeignKeyGenerator::class, [$module, $model])
                    ->generate();
            }
        }
        print($this->result);
    }
}
