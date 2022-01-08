<?php

namespace Crane\Router\Traits;

trait CanHandleErrors
{

    public function logErrors(bool $bool, $file = null)
    {
        error_reporting(E_ALL);
        if ($bool) {
            ini_set('log_errors', 'on');
            if ($file) {
                ini_set('error_log', $file);
            } else {
                ini_set('error_log', 'error.log');
            }
        } else {
            ini_set('log_errors', 'off');
        }
    }

    public function displayErrors(bool $bool)
    {
        if ($bool) {
            ini_set('display_errors', 'on');
        } else {
            ini_set('display_errors', 'off');
        }
    }
}
