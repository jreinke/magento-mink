<?php

require_once 'abstract.php';

class Mage_Shell_Mink extends Mage_Shell_Abstract
{
    public function run()
    {
        $pattern = array('app', 'code', 'local', '*', '*', 'Test', 'Mink', '*.php');
        $pattern = Mage::getBaseDir() . DS . implode(DS, $pattern);
        $files = @glob($pattern);
        $renderer = $this->_getOutputRenderer();
        if (empty($files)) {
            $renderer->output($renderer->bold('No test class found'));
        } else {
            require_once 'mink.phar';
            $driver = $this->_getMinkDriver();
            Mage::getSingleton('core/session', array('name' => 'frontend'))->start();
            $renderer->section('SCRIPT START');
            $renderer->output(sprintf('Found %d file%s', count($files), count($files) > 1 ? 's' : ''));
            foreach ($files as $file) {
                $start = strrpos($file, 'local');
                $classPath = substr($file, $start + 6);
                $className = basename(implode('_', explode(DS, $classPath)), '.php');
                $object = new $className($driver, $renderer);
                $reflection = new ReflectionClass($className);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    if (substr($method->getName(), 0, 4) !== 'test') {
                        continue;
                    }
                    $renderer->output($renderer->bold(sprintf('Running %s::%s()', $className, $method->getName())));
                    $object->{$method->getName()}();
                }
            }
            $renderer->section('SCRIPT END');
        }
    }

    protected function _getOutputRenderer()
    {
        $name = $this->getArg('r') ? $this->getArg('r') : php_sapi_name();

        return JR_Output_Renderer::factory($name);
    }

    protected function _getMinkDriver()
    {
        $name = $this->getArg('d') ? $this->getArg('d') : 'goutte';
        switch ($name) {
            case 'goutte':
                $driver = new \Behat\Mink\Driver\GoutteDriver();
                break;
            case 'selenium':
                $driver = new \Behat\Mink\Driver\SeleniumDriver();
                break;
            case 'selenium2':
                $driver = new \Behat\Mink\Driver\Selenium2Driver();
                break;
            case 'zombie':
                $driver = new \Behat\Mink\Driver\ZombieDriver();
                break;
            case 'sahi':
                $driver = new \Behat\Mink\Driver\SahiDriver();
                break;
            default:
                exit(sprintf("Driver '%s' is not supported."));
        }

        return $driver;
    }

    protected function _validate()
    {
        if (!Mage::helper('core')->isDevAllowed()) {
            exit('You are not allowed to run this script.');
        }

        return true;
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f shell/mink.php -- [options]

  -d            Mink driver (default is goutte)
  -r            Output renderer (default is php_sapi_name())
  -h            Short alias for help
  help          This help

USAGE;
    }
}

$shell = new Mage_Shell_Mink();
$shell->run();
