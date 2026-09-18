<?php

namespace App\Http\Controllers\Club;

use App\Enums\FundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\UpdateFundRequest;
use App\Models\KitPurchase;
use App\Models\Member;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class KitPurchaseController extends Controller
{
    public function index(): Response
    {
        Member::syncActiveMembersIntoKits();

        $purchases = KitPurchase::query()
            ->with('member')
            ->get();

        return Inertia::render('club/kits/index', [
            'purchases' => ClubAdminPresenter::kitPurchases($purchases),
            'statuses' => ClubAdminPresenter::fundStatuses(),
            'summary' => ClubAdminPresenter::kitSummary(),
            'amount' => KitPurchase::AMOUNT,
            'title' => KitPurchase::TITLE,
        ]);
    }

    public function update(UpdateFundRequest $request, string $current_team, KitPurchase $kitPurchase): RedirectResponse
    {
        $data = $request->validated();
        $status = FundStatus::from($data['status']);

        $payload = [
            'status' => $status,
            'paid_at' => $status->isPaid()
                ? ($kitPurchase->paid_at ?? now())
                : null,
        ];

        if (array_key_exists('note', $data)) {
            $payload['note'] = $data['note'];
        }

        $kitPurchase->update($payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật trạng thái mua đồ.']);

        return back();
    }
}
