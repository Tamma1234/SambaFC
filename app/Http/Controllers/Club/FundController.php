<?php

namespace App\Http\Controllers\Club;

use App\Enums\FundStatus;
use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreFundRequest;
use App\Http\Requests\Club\UpdateFundRequest;
use App\Models\FundContribution;
use App\Models\Member;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FundController extends Controller
{
    public function index(): Response
    {
        Member::syncActiveMembersIntoFunds();

        $contributions = FundContribution::query()
            ->with('member')
            ->latest()
            ->get();

        return Inertia::render('club/funds/index', [
            'groups' => ClubAdminPresenter::fundGroups($contributions),
            'statuses' => ClubAdminPresenter::fundStatuses(),
            'amounts' => ClubAdminPresenter::fundAmounts(),
            'summary' => ClubAdminPresenter::fundSummary(),
        ]);
    }

    public function store(StoreFundRequest $request, string $current_team): RedirectResponse
    {
        $members = Member::query()
            ->where('status', MemberStatus::Active)
            ->orderBy('name')
            ->get();

        if ($members->isEmpty()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Chưa có thành viên đang hoạt động để thêm vào quỹ.',
            ]);

            return back();
        }

        $groupKey = (string) Str::uuid();
        $data = $request->validated();

        foreach ($members as $member) {
            FundContribution::query()->create([
                'group_key' => $groupKey,
                'member_id' => $member->id,
                'title' => $data['title'],
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'status' => FundStatus::Unpaid,
                'paid_at' => null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã tạo quỹ cho toàn đội.']);

        return back();
    }

    public function update(UpdateFundRequest $request, string $current_team, FundContribution $fundContribution): RedirectResponse
    {
        $data = $request->validated();

        $payload = [];

        if (isset($data['status'])) {
            $status = FundStatus::from($data['status']);
            $payload['status'] = $status;
            $payload['paid_at'] = $status->isPaid()
                ? ($fundContribution->paid_at ?? now())
                : null;
        }

        if (array_key_exists('amount', $data)) {
            $payload['amount'] = $data['amount'];
        }

        if (array_key_exists('note', $data)) {
            $payload['note'] = $data['note'];
        }

        if ($payload !== []) {
            $fundContribution->update($payload);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật đóng quỹ.']);

        return back();
    }

    public function destroy(string $current_team, FundContribution $fundContribution): RedirectResponse
    {
        $fundContribution->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa khoản đóng của thành viên.']);

        return back();
    }

    public function destroyGroup(string $current_team, string $groupKey): RedirectResponse
    {
        FundContribution::query()
            ->where('group_key', $groupKey)
            ->get()
            ->each
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa quỹ của toàn đội.']);

        return back();
    }
}
