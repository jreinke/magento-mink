<?php

abstract class JR_Output_Renderer_Abstract
{
    protected $_eol = PHP_EOL;

    protected $_indentString = ' ';

    protected $_successColor = '';

    protected $_errorColor = '';

    protected $_prefix = '';

    protected $_suffix = '';

    protected $_sectionLength = 80;

    abstract public function bold($str);

    abstract public function colorize($str, $type);

    public function output($str, $indent = false, $exit = false, $eol = true)
    {
        return $this->_echo($str, $indent, $exit, $eol);
    }

    public function success($str, $indent = false, $exit = false, $eol = true)
    {
        return $this->output($this->colorize($str, $this->getSuccessColor()), $indent, $exit, $eol);
    }

    public function error($str, $indent = false, $exit = false, $eol = true)
    {
        return $this->output($this->colorize($str, $this->getErrorColor()), $indent, $exit, $eol);
    }

    public function start($str)
    {
        return $this->output($str, false, false, false);
    }

    public function pad($str, $length, $type = STR_PAD_RIGHT)
    {
        return str_pad($str, $length, ' ', $type);
    }

    public function green($str)
    {
        return $this->colorize($str, $this->_successColor);
    }

    public function red($str)
    {
        return $this->colorize($str, $this->_errorColor);
    }

    public function br($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->output('');
        }

        return $this;
    }

    public function section($str = '')
    {
        $repeat = $this->_sectionLength;
        $length = strlen($str);
        if ($length) {
            $str = ' ' . $str . ' ';
            $repeat = max(0, $repeat - $length - 2);
        }
        $output = str_repeat('-', floor($repeat / 2))
                . $str
                . str_repeat('-', ceil($repeat / 2));

        return $this->output($output);
    }

    final protected function _echo($str, $indent = false, $exit = false, $eol = true)
    {
        $this->_beforeEcho();

        echo $this->_prefix
           . str_repeat($this->_indentString, (int) $indent)
           . $str
           . $this->_suffix
           . ($eol ? $this->_eol : '');
        if ($exit) {
            exit;
        }

        $this->_afterEcho();

        return $this;
    }

    public function getEol()
    {
        return $this->_eol;
    }

    public function setEol($str)
    {
        $this->_eol = $str;

        return $this;
    }

    public function getIndentString()
    {
        return $this->_indentString;
    }

    public function setIndentString($str)
    {
        $this->_indentString = $str;

        return $this;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function setPrefix($str)
    {
        $this->_prefix = $str;

        return $this;
    }

    public function getSuffix()
    {
        return $this->_suffix;
    }

    public function setSuffix($str)
    {
        $this->_suffix = $str;

        return $this;
    }

    public function getSuccessColor()
    {
        return $this->_successColor;
    }

    public function setSuccessColor($str)
    {
        $this->_successColor = $str;

        return $this;
    }

    public function getErrorColor()
    {
        return $this->_errorColor;
    }

    public function setErrorColor($str)
    {
        $this->_errorColor = $str;

        return $this;
    }

    protected function _beforeEcho()
    {
        return $this;
    }

    protected function _afterEcho()
    {
        return $this;
    }
}