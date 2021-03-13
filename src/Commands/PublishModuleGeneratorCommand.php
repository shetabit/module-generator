<?php

namespace Shetabit\ModuleGenerator\Commands;


use Illuminate\Console\Command;
use Shetabit\ModuleGenerator\Contracts\ModuleGenerator;
use Symfony\Component\Console\Input\InputArgument;

class PublishModuleGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generator:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'publish config file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("publishing ...");
        $this->callSilent('vendor:publish', ['--tag' => 'config' ,'--force' => true]);
        $this->info("publish successfully");

    }
}
