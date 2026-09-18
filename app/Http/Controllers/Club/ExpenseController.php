<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\SaveExpenseRequest;
use App\Models\FundExpense;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        $expenses = FundExpense::query()
            ->latest('spent_at')
            ->latest('id')
            ->get();

        return Inertia::render('club/expenses/index', [
            'groups' => ClubAdminPresenter::expenseGroups($expenses),
            'categories' => ClubAdminPresenter::expenseCategories(),
            'summary' => ClubAdminPresenter::fundSummary(),
        ]);
    }

    public function store(SaveExpenseRequest $request, string $current_team): RedirectResponse
    {
        FundExpense::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã thêm khoản chi.']);

        return back();
    }

    public function update(SaveExpenseRequest $request, string $current_team, FundExpense $fundExpense): RedirectResponse
    {
        $fundExpense->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật khoản chi.']);

        return back();
    }

    public function destroy(string $current_team, FundExpense $fundExpense): RedirectResponse
    {
        $fundExpense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa khoản chi.']);

        return back();
    }
}
