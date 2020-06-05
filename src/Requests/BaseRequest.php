<?php

namespace Srustamov\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * @property string path
 * @property string target
 * @property string parent
 * @property string name
 * @property string from
 * @property string to
 */

abstract class BaseRequest extends FormRequest
{

    protected function getClientBasePath()
    {
        return realpath(config('file-manager.paths.base'));
    }


    protected function validatePath($path)
    {
        $realpath = realpath($path) ?: realpath(dirname($path));

        return Str::of($realpath)->startsWith($this->getClientBasePath());
    }
}
