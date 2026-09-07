<?php

namespace App\Http\Requests\Club;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PitchSlot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMemberRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'preferred_position' => ['nullable', Rule::in(array_map(
                fn (PitchSlot $slot): string => $slot->value,
                PitchSlot::starters(),
            ))],
            'photo' => ['nullable', 'image', 'max:5120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(MemberStatus::class)],
            'role' => ['required', Rule::enum(MemberRole::class)],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'họ tên',
            'nickname' => 'biệt danh',
            'jersey_number' => 'số áo',
            'preferred_position' => 'vị trí',
            'photo' => 'ảnh thành viên',
            'phone' => 'số điện thoại',
            'date_of_birth' => 'ngày sinh',
            'joined_at' => 'ngày gia nhập',
            'status' => 'trạng thái',
            'role' => 'vai trò',
            'bio' => 'tiểu sử',
        ];
    }
}
