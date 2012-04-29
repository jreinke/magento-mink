<?php

require_once 'abstract.php';

class Mage_Shell_Mink extends Mage_Shell_Abstract
{
    public function run()
    {
        $pattern = array('app', 'code', 'local', '*', '*', 'Test', 'Mink');
        $pattern = Mage::getBaseDir() . DS . implode(DS, $pattern);
        $files = $this->_getTestClasses($pattern);
        $renderer = $this->_getOutputRenderer();
        if (empty($files)) {
            $renderer->output($renderer->bold('No test class found'));
        } else {
            try {
                require_once 'mink.phar';
                $driver = $this->_getMinkDriver();
                $driver->start();
                Mage::getSingleton('core/session', array('name' => 'frontend'))->start();
                $renderer->section('SCRIPT START');
                $renderer->output(sprintf('Found %d file%s', count($files), count($files) > 1 ? 's' : ''));
                foreach ($files as $file) {
                    $start = strrpos($file, 'local');
                    $classPath = substr($file, $start + 6);
                    $className = basename(implode('_', explode(DS, $classPath)), '.php');
                    if (!class_exists($className)) {
                        $renderer->error(sprintf('Class %s does not exist', $className));
                        continue;
                    }
                    $object = new $className($driver, $renderer);
                    $reflection = new ReflectionClass($className);
                    if (!$reflection->getParentClass() || $reflection->getParentClass()->getName() !== 'JR_Mink_Test_Mink') {
                        $renderer->error(sprintf('Class %s must extend JR_Mink_Test_Mink class', $className));
                        continue;
                    }
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($methods as $method) {
                        if (substr($method->getName(), 0, 4) !== 'test') {
                            continue;
                        }
                        $renderer->output($renderer->bold(sprintf('Running %s::%s()', $className, $method->getName())));
                        try {
                            $object->{$method->getName()}();
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $renderer->error($e->getMessage());
                        }
                    }
                }
                $driver->stop();
                $renderer->section('SCRIPT END');
            } catch (Exception $e) {
                Mage::logException($e);
                $renderer->error($e->getMessage(), false, true);
            }
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

    protected function _getTestClasses($dir)
    {
        $files = array();
        $items = glob($dir . '*', GLOB_MARK | GLOB_NOSORT);

        foreach ($items as $item) {
            if (substr($item, -1) != DS) {
                if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                    $files[] = $item;
                }
            } else {
                $files = array_merge($files, $this->_getTestClasses($item));
            }
        }

        return $files;
    }

    protected function _validate()
    {
        if (!Mage::helper('core')->isDevAllowed()) {
            exit('You are not allowed to run this script.');
        }

        return true;
    }

    public function getArg($name)
    {
        $arg = parent::getArg($name);
        if (false === $arg && isset($_GET[$name])) {
            $arg = $_GET[$name];
        }

        return $arg;
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
