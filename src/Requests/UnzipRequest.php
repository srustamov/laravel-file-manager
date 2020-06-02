<?php

namespace Srustamov\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UnzipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $base = realpath(config('file-manager.paths.base'));

        $target = $this->target;

        if(rtrim($target,DIRECTORY_SEPARATOR) !== rtrim($base,DIRECTORY_SEPARATOR)) {
          $target = dirname($this->target);
        }
        
        return Str::of(realpath(dirname($this->path)))->startsWith($base)
                  && Str::of(realpath($target))->startsWith($base);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'path' => 'required|string',
            'target' => 'required|string',
        ];
    }
}
