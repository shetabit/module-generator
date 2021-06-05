<?php


namespace Shetabit\ModuleGenerator\Classes\Generators;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpNamespace;
use Shetabit\ModuleGenerator\Classes\ModelName;

class RouteGenerator
{

    protected  $nameRoute;
    /**
     * @var mixed
     */
    protected $models;
    protected $module;
    /**
     * @var mixed
     */
    protected $CRUD;
    protected string $pathOfRoute;
    protected string $message = '';
    protected string $finalTemplate ='';
    protected $model;
    protected ModelName $modelName;

    public function __construct($module , $models)
    {
        $this->models = $models['Models'];
        $this->module = $module;
    }

    public function generate(): string
    {
        foreach ($this->models as $model => $attribute) {
            if (!key_exists('CRUD', $attribute)) return '';
            $this->model = $model;
            $this->modelName = new ModelName($model);

            $this->CRUD = $attribute['CRUD'];
            return $this->RouteGenerator($this->module);
        }
    }

    public function RouteGenerator($module): string
    {
        $i = 0;
        foreach ($this->CRUD as $name => $option) {
            $this->nameRoute = $name;
            $this->pathOfRoute = module_path($module) . "/Routes/api.php";
            $namespace = new PhpNamespace('');
            if($i == 0) { $namespace->addUse(Route::class);  $this->finalTemplate .='<?php' . PHP_EOL;}
            $this->finalTemplate .=  $this->GenerateRouteTemplates($option[0] , $namespace);
            $this->touchAndPutContent($this->finalTemplate);
            $this->message .=  "|-- ".$this->nameRoute." Route successfully generated" . PHP_EOL;
            $i++;
        }
        return $this->message;
    }

    public function GenerateRouteTemplates($option , $namespace): string
    {
        $namespace .= "Route::name('".Str::snake($this->nameRoute).".')";
        $namespace .= "->namespace('".$this->nameRoute."')";
        $namespace .= "->prefix('".Str::snake($this->nameRoute)."')";
        $namespace .= "->group(function(){".PHP_EOL;
        $namespace = $this->setBody($namespace , $option);
        $namespace .= "});".PHP_EOL;
        return $namespace;
    }

    public function setBody($route , $option)
    {
        if ($option == "CRUD"){
             $route .= "    Route::apiResource('".$this->modelName->getPluralForRoute()."' , '".$this->modelName."Controller');".PHP_EOL;
            return $route;
        }
        for ($i = 0; $i< strlen($option) ; $i++){

            if ($option[$i] == 'R'){
               $route .= "    Route::name('index')->get('/', '".$this->nameRoute."Controller@index');".PHP_EOL;
               $route .= "    Route::name('show')->get('/{id?}', '".$this->nameRoute."Controller@show');".PHP_EOL;
            }
            if ($option[$i] == 'C'){
                $route .= "    Route::name('create')->get('/create', '".$this->nameRoute."Controller@create');".PHP_EOL;
                $route .= "    Route::name('store')->post('/', '".$this->nameRoute."Controller@show');".PHP_EOL;
            }
            if ($option[$i] == 'U'){
                $route .= "    Route::name('edit')->get('/{id}/edit', '".$this->nameRoute."Controller@create');".PHP_EOL;
                $route .= "    Route::name('update')->put('/', '".$this->nameRoute."Controller@show');".PHP_EOL;
            }
            if ($option[$i] == 'D'){
                $route .="    Route::name('destroy')->delete('/', '".$this->nameRoute."Controller@destroy');".PHP_EOL;
            }
        }
        return $route;
    }


    public function touchAndPutContent($template)
    {
        file_put_contents($this->pathOfRoute, $template);
    }
}
