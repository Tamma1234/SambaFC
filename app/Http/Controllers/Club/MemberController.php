<?php

namespace App\Http\Controllers\Club;

use App\Actions\Club\StoreMemberPhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\SaveMemberRequest;
use App\Models\Member;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(): Response
    {
        $members = Member::query()
            ->orderByRaw('jersey_number is null')
            ->orderBy('jersey_number')
            ->orderBy('name')
            ->get();

        return Inertia::render('club/members/index', [
            'members' => ClubAdminPresenter::members($members),
            'positions' => ClubAdminPresenter::positions(),
            'statuses' => ClubAdminPresenter::memberStatuses(),
            'roles' => ClubAdminPresenter::memberRoles(),
        ]);
    }

    public function store(SaveMemberRequest $request, string $current_team, StoreMemberPhoto $storePhoto): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            $data['photo_path'] = $storePhoto->handle($photo);
        }

        $member = Member::query()->create($data);
        $member->attachToUpcomingMatches();
        $member->attachToOpenFunds();
        $member->attachToKitPurchases();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã thêm thành viên.']);

        return back();
    }

    public function update(SaveMemberRequest $request, string $current_team, Member $member, StoreMemberPhoto $storePhoto): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            $data['photo_path'] = $storePhoto->handle($photo, $member);
        }

        $member->update($data);
        $member->attachToUpcomingMatches();
        $member->attachToOpenFunds();
        $member->attachToKitPurchases();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật thành viên.']);

        return back();
    }

    public function destroy(string $current_team, Member $member, StoreMemberPhoto $storePhoto): RedirectResponse
    {
        $storePhoto->deleteIfLocal($member->photo_path);
        $member->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa thành viên.']);

        return back();
    }
}
