<?php

namespace Srustamov\FileManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $base = realpath(config('file-manager.paths.base'));

        return trim($base,DIRECTORY_SEPARATOR) !== trim($this->path,DIRECTORY_SEPARATOR);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'path' => 'required|string'
        ];
    }
}
