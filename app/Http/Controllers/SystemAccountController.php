<?php

namespace App\Http\Controllers;

use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SystemAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     * 管理系统账号api
     *
     * @return Response
     */
    public function index()
    {
        return SystemAccount::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'system_name' => 'required|string|max:255',
            'account' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'cookie' => 'nullable|string'
        ]);

        $systemAccount = SystemAccount::create($validatedData);

        return response()->json($systemAccount, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  SystemAccount  $systemAccount
     * @return Response
     */
    public function update(Request $request, SystemAccount $systemAccount)
    {
        $validatedData = $request->validate([
            'system_name' => 'string|max:255',
            'account' => 'string|max:255',
            'password' => 'string|max:255',
            'cookie' => 'nullable|string'
        ]);

        $systemAccount->update($validatedData);

        return response()->json($systemAccount, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  SystemAccount  $systemAccount
     * @return Response
     */
    public function destroy(SystemAccount $systemAccount)
    {
        $systemAccount->delete();

        return response()->json(null, 204);
    }
}
