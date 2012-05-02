<?php

class JR_Mink_Test_Mink
{
    /**
     * @var array of \Behat\Mink\Session
     */
    protected $_sessions = null;

    /**
     * @var JR_Output_Renderer_Abstract
     */
    protected $_renderer = null;

    /**
     * @param JR_Output_Renderer_Abstract $renderer
     */
    public function __construct(JR_Output_Renderer_Abstract $renderer)
    {
        $this->_renderer = $renderer;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_renderer, $method), $args);
    }

    /**
     * @param bool $withCaches
     * @return JR_Mink_Test_Mink
     */
    public function context($withCaches = true)
    {
        $renderer = $this->getRenderer();
        $store = Mage::app()->getStore();
        $website = $store->getWebsite();
        $renderer->section('CONTEXT');
        $renderer->output(sprintf('website: %s, store: %s', $website->getCode(), $store->getCode()));
        if ($withCaches) {
            $renderer->output($renderer->bold('Cache info:'));
            $this->cacheInfo();
        }

        return $this;
    }

    /**
     * @return JR_Mink_Test_Mink
     */
    public function cacheInfo()
    {
        $invalidTypes = Mage::getModel('core/cache')->getInvalidatedTypes();
        $cacheTypes = Mage::getModel('core/cache')->getTypes();
        $renderer = $this->getRenderer();
        foreach ($cacheTypes as $cache) {
            $enabled = $cache->getStatus() ? 'Enabled' : 'Disabled';
            $enabled = $renderer->pad($enabled, 10);
            if ($cache->getStatus()) {
                $enabled = $renderer->green($enabled);
                $valid = !array_key_exists($cache, $invalidTypes);
                if ($valid) {
                    $valid = $renderer->pad('Valid', 10);
                    $valid = $renderer->green($valid);
                } else {
                    $valid = $renderer->pad('Invalid', 10);
                    $valid = $renderer->red($valid);
                }
            } else {
                $enabled = $renderer->red($enabled);
                $valid = $renderer->pad('N/A', 10);
            }
            $renderer->start($renderer->pad($cache->getId(), 18));
            $renderer->start($enabled);
            $renderer->start($valid);
            $renderer->output($cache->getCacheType());
        }

        return $this;
    }

    /**
     * @param $str
     */
    public function abort($str)
    {
        $this->getRenderer()->error($str, false, true);
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession()
    {
        return Mage::getModel('customer/session');
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getModel('checkout/session');
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckoutSession()->getQuote();
    }

    /**
     * @return JR_Mink_Test_Mink
     */
    public function clearQuote()
    {
        $quote = $this->getQuote();
        $quote->getItemsCollection()->walk('delete');
        $quote->save();

        return $this;
    }

    /**
     * @param string $url
     * @param bool $quiet
     * @return \Behat\Mink\Session
     */
    public function visit($url, $quiet = false)
    {
        $session = $this->getSession();
        $session->visit($url);
        if (false === $quiet) {
            $status = $session->getStatusCode();
            $renderer = $this->getRenderer();
            $renderer->start('Visiting ' . $url);
            if ($status == 200) {
                $renderer->success(' [OK]');
            } else {
                Mage::log($session->getPage()->getContent());
                $renderer->error(sprintf(' [FAILED] (status: %s)', $status));
            }
        }

        return $session;
    }

    /**
     * @param string $selector
     * @param string $locator
     * @param bool $quiet
     * @return mixed
     */
    public function find($selector, $locator, $quiet = false)
    {
        return $this->_find($selector, $locator, false, $quiet);
    }

    /**
     * @param string $selector
     * @param string $locator
     * @param bool $quiet
     * @return mixed
     */
    public function findAll($selector, $locator, $quiet = false)
    {
        return $this->_find($selector, $locator, true, $quiet);
    }

    /**
     * @param string $email
     * @param null $store
     * @param bool $quiet
     * @return JR_Mink_Test_Mink
     */
    public function authenticate($email, $store = null, $quiet = false)
    {
        $store = Mage::app()->getStore($store);
        if (!$this->getCustomerSession()->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId($store->getWebsiteId())
                ->loadByEmail($email);
            if (!$customer->getId() && false === $quiet) {
                // Error and abort
                $this->abort(sprintf(
                    "Failed retrieving customer with email '%s' in store '%s'",
                    $email,
                    $store->getCode()
                ));
            }
            $this->getCustomerSession()->loginById($customer->getId());
        }
        session_write_close();

        if (false === $quiet) {
            $renderer = $this->getRenderer();
            $renderer->start(sprintf(
                "Authenticating customer with email '%s' in store '%s'",
                $email,
                Mage::app()->getStore($store)->getCode()
            ));
            if ($this->getCustomerSession()->isLoggedIn()) {
                $session = $this->getSession();
                $session->setCookie(session_name(), $this->getCustomerSession()->getSessionId());
                $renderer->success(' [OK]');
            } else {
                $renderer->error(' [FAILED]');
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @return \Behat\Mink\Session
     */
    public function getSession($name = 'goutte')
    {
        return $this->_getSession($name);
    }

    /**
     * @return JR_Output_Renderer_Abstract
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * @param JR_Output_Renderer_Abstract $renderer
     * @return JR_Mink_Test_Mink
     */
    public function setRenderer($renderer)
    {
        $this->_renderer = $renderer;

        return $this;
    }

    /**
     * @param string $selector
     * @param string $locator
     * @param bool $all
     * @param bool $quiet
     * @return mixed
     */
    protected function _find($selector, $locator, $all = false, $quiet = false)
    {
        $page = $this->getSession()->getPage();
        if ($all) {
            $result = $page->findAll($selector, $locator);
        } else {
            $result = $page->find($selector, $locator);
        }

        if (false === $quiet) {
            $renderer = $this->getRenderer();
            $renderer->start(sprintf("Finding%s '%s' with selector '%s'", $all ? ' all' : '', $locator, $selector));
            if ($result) {
                $renderer->success(' [OK]');
            } else {
                $renderer->error(' [FAILED]');
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @return \Behat\Mink\Session
     * @throws Exception
     */
    protected function _getSession($name)
    {
        if (!isset($this->_sessions[$name])) {
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
                    throw new Exception(sprintf('Could not find Mink driver with name %s', $name));
            }
            $this->_sessions[$name] = new \Behat\Mink\Session($driver);
            $this->_initSession($this->_sessions[$name]);
        }

        return $this->_sessions[$name];
    }

    /**
     * @param \Behat\Mink\Session $session
     * @return JR_Mink_Test_Mink
     */
    protected function _initSession($session)
    {
        switch (php_sapi_name()) {
            case 'cli':
                $lang = $_SERVER['LANG'];
                if ($lang) {
                    $pieces = explode('.', $lang);
                    $lang = $pieces[0];
                }
                break;
            case 'cgi':
            case 'cgi-fcgi':
            case 'apache':
            case 'apache2handler':
            case 'phttpd':
            case 'thttpd':
                $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                break;
            default:
                $lang = null;
        }
        if ($lang) {
            try {
                $session->setRequestHeader('Accept-Language', $lang);
            } catch (\Behat\Mink\Exception\UnsupportedDriverActionException $e) {
                $this->getRenderer()->error($e->getMessage());
            }
        }

        return $this;
    }
}