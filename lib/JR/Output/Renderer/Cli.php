<?php

class JR_Output_Renderer_Cli extends JR_Output_Renderer_Abstract
{
    protected $_successColor = '1;32m';

    protected $_errorColor = '1;31m';

    public function bold($str)
    {
        return sprintf("\033[1m%s\033[0m", $str);
    }

    public function colorize($str, $color)
    {
        return "\033" . '[' . $color . $str . "\033" . '[0m';
    }
}