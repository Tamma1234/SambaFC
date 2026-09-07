<?php

namespace App\Http\Requests\Club;

use App\Enums\PitchSlot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMatchStatsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lineups' => ['present', 'array'],
            'lineups.*.slot' => ['required', Rule::enum(PitchSlot::class), 'distinct'],
            'lineups.*.member_id' => ['nullable', 'integer', 'exists:members,id'],
            'lineups.*.rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'goals' => ['present', 'array'],
            'goals.*.member_id' => ['required', 'integer', 'exists:members,id'],
            'goals.*.minute' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lineups' => 'đội hình',
            'lineups.*.slot' => 'vị trí',
            'lineups.*.member_id' => 'cầu thủ',
            'lineups.*.rating' => 'điểm',
            'goals' => 'bàn thắng',
            'goals.*.member_id' => 'cầu thủ ghi bàn',
            'goals.*.minute' => 'phút ghi bàn',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $memberIds = collect($this->input('lineups', []))
                ->pluck('member_id')
                ->filter()
                ->map(fn (mixed $id): int => (int) $id);

            if ($memberIds->count() !== $memberIds->unique()->count()) {
                $validator->errors()->add('lineups', 'Mỗi thành viên chỉ được xếp một vị trí.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $lineups = collect($this->input('lineups', []))
            ->map(function (mixed $row): mixed {
                if (! is_array($row)) {
                    return $row;
                }

                return [
                    ...$row,
                    'member_id' => blank($row['member_id'] ?? null) ? null : $row['member_id'],
                    'rating' => blank($row['rating'] ?? null) ? null : $row['rating'],
                ];
            })
            ->all();

        $goals = collect($this->input('goals', []))
            ->filter(fn (mixed $row): bool => is_array($row) && ! blank($row['member_id'] ?? null))
            ->map(function (array $row): array {
                return [
                    ...$row,
                    'minute' => blank($row['minute'] ?? null) ? null : $row['minute'],
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'lineups' => $lineups,
            'goals' => $goals,
        ]);
    }
}
