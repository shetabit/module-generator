<?php


namespace Shetabit\ModuleGenerator\Classes;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\GlobalFunction;
use Nette\PhpGenerator\PhpNamespace;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use function MongoDB\BSON\toJSON;

class RequestGenerator
{
    public string $message = '';
    protected $models;
    protected $module;
    protected $fullPathOfRequest;
    protected string $nameOfRequest;

    public function __construct($module , $models)
    {
        $this->models = $models;
        $this->module = $module;
    }

    public function generate(): string
    {
        if (!key_exists('Models', $this->models)) return '';
            return $this->generateRequests($this->models['Models']);
    }

    public function generateRequests($models)
    {
        foreach ($models as $model => $value) {
            if (!key_exists('Requests', $value)) return '';
                return $this->generateRequestTemplates($model, $value['Requests']);

        }
    }

    public function generateRequestTemplates($model , $request)
    {
        foreach ($request as $dir => $value) {
            foreach ($value as $key => $item) {
                $namespace = new PhpNamespace('Modules\\' . $this->module . '\Http\Requests\\'.ucwords($dir));
                $namespace->addUse(FormRequest::class);
                $this->nameOfRequest = ucwords($dir).ucfirst($key).'Request';
                $this->fullPathOfRequest = module_path($this->module)."/Http/Requests/".ucfirst($dir);
                $class = $namespace->addClass(ucwords($dir).ucfirst($key).'Request');
                $class->setExtends(FormRequest::class);
                $method = $class->addMethod('roles');
                $method->addBody('return  [');
                $this->addRoles($item ,$method);
                $method->addBody('];');
                $template = '<?php' . PHP_EOL . $namespace;
                $this->touchAndPutContent($template);
                $this->message .= "|-- Requests " . ucfirst($key) . " successfully generate" . PHP_EOL;
            }

        }
        return $this->message;

    }


    public function addRoles($items ,  $method)
    {
        /**
         * @var $method  GlobalFunction
         */
        foreach ($items as $field => $roles) {
            $encodedRoles = json_encode($roles);
            return $method->addBody("'".$field."' => ".$encodedRoles.",");
        }
    }

    public function touchAndPutContent($template)
    {
        $this->createDirectory();
        touch($this->fullPathOfRequest."/".$this->nameOfRequest.".php");
        file_put_contents($this->fullPathOfRequest."/".$this->nameOfRequest.".php", $template);
    }

    public function createDirectory()
    {
        if (!is_dir($this->fullPathOfRequest))
        {
            mkdir($this->fullPathOfRequest, 0777, true);
        }
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
