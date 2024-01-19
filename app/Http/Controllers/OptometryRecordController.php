<?php

namespace App\Http\Controllers;

use App\Models\OptometryRecord;
use Illuminate\Http\Request;

class OptometryRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     * Usage：http://opms.test/api/optometry-records?page=2&perPage=20
     */
    public function index(Request $request)
    {
        // 获取请求中的 'perPage' 参数，如果没有提供则默认为 10
        $perPage = $request->input('perPage', 10);

        // 确保 $perPage 是合法的数值
        $perPage = is_numeric($perPage) ? (int) $perPage : 10;
        $perPage = max(1, min($perPage, 100)); // 设置最小值为 1，最大值为 100

        $records = OptometryRecord::paginate($perPage);

        return response()->json($records);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'gender' => 'nullable|string',
            'phone' => 'required|string',
            'resident_id_number' => 'nullable|string',
            'medical_record_number' => 'nullable|string',
        ]);

        $record = OptometryRecord::create($validatedData);

        return response()->json($record, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $record = OptometryRecord::find($id);

        if (!$record) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($record);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $record = OptometryRecord::find($id);

        if (!$record) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validatedData = $request->validate([
            'name' => 'string',
            'gender' => 'string',
            'phone' => 'string',
            'resident_id_number' => 'string',
            'medical_record_number' => 'string',
        ]);

        $record->update($validatedData);

        return response()->json($record);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $record = OptometryRecord::find($id);

        if (!$record) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $record->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
