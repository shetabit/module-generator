<?php

namespace Shetabit\ModuleGenerator\Commands;


use Illuminate\Console\Command;
use Shetabit\ModuleGenerator\Contracts\ModuleGenerator;
use Symfony\Component\Console\Input\InputArgument;

class GenerateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generator:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create your module';

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
        $moduleGenerator = app(ModuleGenerator::class);
        $moduleGenerator->generate();

        $this->info('Generate successfully');
    }
}
