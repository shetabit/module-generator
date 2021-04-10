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
        if (!file_exists(config_path()."/generator.php")){
            $this->error("Create your module generator config file ");
            $this->warn("Path for creating =>". config_path());
            die();
        }

        $this->info("
       _____  _            _          _      _  _
      / ____|| |          | |        | |    (_)| |
     | (___  | |__    ___ | |_  __ _ | |__   _ | |_
      \___ \ | '_ \  / _ \| __|/ _` || '_ \ | || __|
      ____) || | | ||  __/| |_| (_| || |_) || || |_
     |_____/ |_| |_| \___| \__|\__,_||_.__/ |_| \__|
        ");
        $this->info('Generating ... ');
        $processes = count(\config('generator') , COUNT_RECURSIVE);
        $Progress = $this->getOutput()->createProgressBar($processes);
        $Progress->setBarCharacter("/");
        $moduleGenerator = app(ModuleGenerator::class);
        $moduleGenerator->generate();
        $Progress->finish();
        $this->info("");
        $this->info("Generate Successfully");
    }
}
