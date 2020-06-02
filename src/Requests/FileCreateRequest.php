<?php

namespace Srustamov\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class FileCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $base = realpath(config('file-manager.paths.base'));

        $fullpath = realpath(dirname($this->parent.DIRECTORY_SEPARATOR.$this->name));

        return Str::of(realpath($fullpath))->startsWith($base);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'parent' => 'required|string'
        ];
    }
}
