<?php

namespace Srustamov\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ZipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $base = realpath(config('file-manager.paths.base'));

        return Str::of(realpath(dirname($this->path)))->startsWith($base) &&
                Str::of(realpath(dirname($this->name)))->startsWith($base);
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
            'name' => 'required|string',
        ];
    }
}
