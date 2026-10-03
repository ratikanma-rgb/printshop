<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('id')->get();

        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:services,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'unit' => [
                'required',
                'string',
                'max:50',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        Service::create($validated);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'เพิ่มบริการเรียบร้อยแล้ว');
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')->ignore($service->id),
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'unit' => [
                'required',
                'string',
                'max:50',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $service->update($validated);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'แก้ไขบริการเรียบร้อยแล้ว');
    }

    public function toggle(Service $service)
    {
        $service->update([
            'is_active' => ! $service->is_active,
        ]);

        return redirect()
            ->route('admin.services.index')
            ->with(
                'success',
                $service->is_active
                    ? 'เปิดใช้งานบริการแล้ว'
                    : 'ปิดใช้งานบริการแล้ว'
            );
    }

    public function destroy(Service $service)
    {
        if ($service->orderItems()->exists()) {
            return redirect()
                ->route('admin.services.index')
                ->with(
                    'error',
                    'ลบบริการนี้ไม่ได้ เพราะมีงานที่ใช้บริการนี้อยู่ กรุณาปิดใช้งานแทน'
                );
        }

        $service->delete();

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'ลบบริการเรียบร้อยแล้ว');
    }
}