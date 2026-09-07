<?php

namespace App\Http\Requests\Club;

use App\Enums\MatchStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMatchRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'opponent' => ['required', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'is_home' => ['sometimes', 'boolean'],
            'kickoff_at' => ['required', 'date'],
            'score_for' => ['nullable', 'integer', 'min:0', 'max:99'],
            'score_against' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['required', Rule::enum(MatchStatus::class)],
            'mvp_member_id' => ['nullable', 'integer', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tiêu đề',
            'opponent' => 'đối thủ',
            'venue' => 'sân',
            'is_home' => 'sân nhà',
            'kickoff_at' => 'giờ bóng lăn',
            'score_for' => 'bàn thắng',
            'score_against' => 'bàn thua',
            'status' => 'trạng thái',
            'mvp_member_id' => 'MVP',
            'notes' => 'ghi chú',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_home' => $this->boolean('is_home'),
        ]);
    }
}
