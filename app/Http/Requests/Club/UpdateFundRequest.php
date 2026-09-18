<?php

namespace App\Http\Requests\Club;

use App\Enums\FundStatus;
use App\Models\FundContribution;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(FundStatus::class)],
            'amount' => ['sometimes', 'integer', Rule::in(FundContribution::AMOUNTS)],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'trạng thái',
            'amount' => 'số tiền',
            'note' => 'ghi chú',
        ];
    }
}
