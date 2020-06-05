<?php

namespace Srustamov\FileManager\Requests;


class UnzipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->validatePath($this->path) && $this->validatePath($this->target);
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
