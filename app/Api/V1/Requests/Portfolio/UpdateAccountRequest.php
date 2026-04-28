<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Portfolio;

use FireflyIII\Enums\PortfolioPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'platform' => ['sometimes', new Enum(PortfolioPlatformEnum::class)],
            'active'   => ['sometimes', 'boolean'],
        ];
    }

    public function getAll(): array
    {
        $data = [];
        if ($this->has('name')) {
            $data['name'] = $this->string('name');
        }
        if ($this->has('platform')) {
            $data['platform'] = $this->string('platform');
        }
        if ($this->has('active')) {
            $data['active'] = $this->boolean('active');
        }

        return $data;
    }
}
