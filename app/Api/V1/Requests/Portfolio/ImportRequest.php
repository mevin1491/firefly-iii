<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file'       => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:10240'],
            'import_type' => ['sometimes', 'string', 'in:holdings,transactions'],
        ];
    }
}
