<?php

abstract class JR_Output_Renderer
{
    static public function factory($name)
    {
        switch ($name) {
            case 'html':
            case 'apache':
            case 'apache2handler':
                $renderer = new JR_Output_Renderer_Html();
                break;
            case 'shell':
            case 'cli':
            default:
                $renderer = new JR_Output_Renderer_Cli();
        }

        return $renderer;
    }
}