<?php

namespace Shetabit\ModuleGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Shetabit\ModuleGenerator\Commands\GenerateModuleCommand;
use Shetabit\ModuleGenerator\Commands\PublishModuleGeneratorCommand;
use Shetabit\ModuleGenerator\Contracts\ControllerGenerator;
use Shetabit\ModuleGenerator\Contracts\ForeignKeyGenerator;
use Shetabit\ModuleGenerator\Contracts\MigrationGenerator;
use Shetabit\ModuleGenerator\Contracts\ModelGenerator;
use Shetabit\ModuleGenerator\Contracts\ModuleGenerator;
use Shetabit\ModuleGenerator\Contracts\RequestGenerator;
use Shetabit\ModuleGenerator\Contracts\RouteGenerator;

class ModuleGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ModuleGenerator::class, function () {
            return new \Shetabit\ModuleGenerator\Classes\Generators\ModuleGenerator();
        });
        $this->app->singleton(ModelGenerator::class, function ($module, $models) {
            return new \Shetabit\ModuleGenerator\Classes\Generators\ModelGenerator($models[0] , $models[1]);
        });
        $this->app->singleton(MigrationGenerator::class, function ($module, $models) {
            return new \Shetabit\ModuleGenerator\Classes\Generators\MigrationGenerator($models[0] , $models[1]);
        });
        $this->app->singleton(ControllerGenerator::class, function ($module, $models) {
            return new \Shetabit\ModuleGenerator\Classes\Generators\ControllerGenerator($models[0] , $models[1]);
        });
        $this->app->singleton(RouteGenerator::class, function ($module, $models) {
            return new \Shetabit\ModuleGenerator\Classes\Generators\RouteGenerator($models[0] , $models[1]);
        });
        $this->app->singleton(ForeignKeyGenerator::class, function ($module, $models) {
            return new \Shetabit\ModuleGenerator\Classes\Generators\ForeignKeyGenerator($models[0] , $models[1]);
        });
        $this->app->singleton(RequestGenerator::class, function ($module, $models) {
            return new \Shetabit\ModuleGenerator\Classes\Generators\RequestGenerator($models[0] , $models[1]);
        });
    }

    public function boot()
    {
        $this->commands([
            GenerateModuleCommand::class,
            PublishModuleGeneratorCommand::class
        ]);

        $this->publishes([
            __DIR__.'/../Config/generator.php' => config_path('generator.php'),
        ],'config');

    }
}
