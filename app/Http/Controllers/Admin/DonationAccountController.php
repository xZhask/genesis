<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DonationAccountRequest;
use App\Models\DonationAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DonationAccountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DonationAccount::class);

        return view('admin.support.accounts.index', ['accounts' => DonationAccount::ordered()->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', DonationAccount::class);

        return view('admin.support.accounts.form', ['account' => new DonationAccount(['is_visible' => true])]);
    }

    public function store(DonationAccountRequest $request): RedirectResponse
    {
        $account = DonationAccount::create($request->validated());

        return redirect()->route('admin.accounts.index')->with('status_message', "Se agregó la cuenta «{$account->label}».");
    }

    public function edit(DonationAccount $account): View
    {
        $this->authorize('update', $account);

        return view('admin.support.accounts.form', ['account' => $account]);
    }

    public function update(DonationAccountRequest $request, DonationAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return redirect()->route('admin.accounts.index')->with('status_message', "Se actualizó la cuenta «{$account->label}».");
    }

    public function destroy(DonationAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()->route('admin.accounts.index')->with('status_message', "Se eliminó la cuenta «{$account->label}».");
    }
}
