<?php

class JR_Output_Renderer_Html extends JR_Output_Renderer_Abstract
{
    protected $_eol = '<br />';

    protected $_indentString = '&nbsp;';

    protected $_successColor = 'green';

    protected $_errorColor = 'red';

    protected $_prefix = '<span>';

    protected $_suffix = '</span>';

    public function __construct()
    {
        echo <<< EOF
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body>
    <style type="text/css">
        body {
            font: 14px/1.5em Monospace;
        }
    </style>
EOF;
    }

    public function __destruct()
    {
        echo <<< EOF
    <body>
</html>
EOF;

    }

    public function bold($str)
    {
        return sprintf('<strong>%s</strong>', $str);
    }

    public function colorize($str, $color)
    {
        return sprintf('<span style="color: %s;">%s</span>', $color, $str);
    }

    public function pad($str, $length, $type = STR_PAD_RIGHT)
    {
        $strlen = strlen($str);
        $length = max(0, $length - $strlen);

        if ($type == STR_PAD_RIGHT) {
            $str = $str . str_repeat('&nbsp;', $length);
        } else {
            $str = str_repeat('&nbsp;', $length) . $str;
        }

        return $str;
    }

    protected function _afterEcho()
    {
        // 1024 padding is required for Safari, while 256 padding is required
        // for Internet Explorer.
        echo str_pad('', 1024, ' ', STR_PAD_RIGHT) . "\n";
        @flush();
        @ob_flush();
    }
}