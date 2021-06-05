<?php


namespace Shetabit\ModuleGenerator\Classes\Generators;


use Illuminate\Foundation\Http\FormRequest;
use Nette\PhpGenerator\GlobalFunction;
use Nette\PhpGenerator\PhpNamespace;

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
        $messages = '';
        foreach ($models as $model => $value) {
            if (!key_exists('Requests', $value)) continue;
                $messages .= $this->generateRequestTemplates($model, $value['Requests']);
        }

        return $messages;
    }

    public function generateRequestTemplates($model , $request)
    {
        foreach ($request as $dir => $value) {
            if (!isset($value['store'])) {
                $request[$dir]['store'] = [];
            }
            if (!isset($value['update'])) {
                $request[$dir]['update'] = [];
            }
            if (isset($value['both'])) {
                $request[$dir]['store'] = array_merge($request[$dir]['store'], $value['both']);
                $request[$dir]['update'] = array_merge($request[$dir]['update'], $value['both']);
            }
        }

        foreach ($request as $dir => $value) {
            foreach ($value as $key => $item) {
                if ($key === 'both') {
                    continue;
                }
                $namespace = new PhpNamespace('Modules\\' . $this->module . '\Http\Requests\\'.ucwords($dir));
                $namespace->addUse(FormRequest::class);
                $this->nameOfRequest = ucwords($model).ucfirst($key).'Request';
                $this->fullPathOfRequest = module_path($this->module)."/Http/Requests/".ucfirst($dir);
                $class = $namespace->addClass(ucwords($model).ucfirst($key).'Request');
                $class->setExtends(FormRequest::class);
                $method = $class->addMethod('rules');
                $method->addBody('return  [');
                $this->addRules($item ,$method);
                $method->addBody('];');
                $template = '<?php' . PHP_EOL . $namespace;
                $this->touchAndPutContent($template);
                $this->message .= "|-- Requests " . ucfirst($key) . " successfully generated" . PHP_EOL;
            }

        }
        return $this->message;

    }


    public function addRules($items, $method)
    {
        /**
         * @var $method  GlobalFunction
         */
        $body = '';
        foreach ($items as $field => $rules) {
            $encodedRules = json_encode($rules);
            $body .= "    '".$field."' => ".$encodedRules."," . PHP_EOL;
        }
        $method->addBody($body);
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
            mkdir($this->fullPathOfRequest, 0755, true);
        }
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
