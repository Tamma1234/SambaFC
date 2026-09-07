<?php

namespace App\Actions\Club;

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StoreMemberPhoto
{
    public function handle(UploadedFile $file, ?Member $member = null): string
    {
        $directory = public_path('images/member');
        File::ensureDirectoryExists($directory);

        if ($member) {
            $this->deleteIfLocal($member->photo_path);
        }

        $extension = $this->extension($file);
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);

        return 'images/member/'.$filename;
    }

    public function deleteIfLocal(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (! str_starts_with(str_replace('\\', '/', $path), 'images/member/')) {
            return;
        }

        $fullPath = public_path($path);

        if (is_file($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function extension(UploadedFile $file): string
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');

        return in_array($extension, $allowed, true) ? $extension : 'jpg';
    }
}
