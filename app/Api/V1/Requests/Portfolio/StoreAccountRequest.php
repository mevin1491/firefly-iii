<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Portfolio;

use FireflyIII\Enums\PortfolioPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'platform' => ['required', new Enum(PortfolioPlatformEnum::class)],
            'active'   => ['sometimes', 'boolean'],
        ];
    }

    public function getAll(): array
    {
        return [
            'name'     => $this->string('name'),
            'platform' => $this->string('platform'),
            'active'   => $this->boolean('active', true),
        ];
    }
}
