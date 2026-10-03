<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | แสดงผู้ใช้งานทั้งหมด
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $users = User::orderBy('id')->get();

        return view('admin.users.index', compact('users'));
    }

    /*
    |--------------------------------------------------------------------------
    | เปลี่ยนสิทธิ์ผู้ใช้งาน
    |--------------------------------------------------------------------------
    */

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => [
                'required',
                'in:customer,staff,admin',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | ป้องกัน Admin เปลี่ยนสิทธิ์ตัวเอง
        |--------------------------------------------------------------------------
        */

        if ($user->id === $request->user()->id) {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'ไม่สามารถเปลี่ยนสิทธิ์ของบัญชีที่กำลังใช้งานอยู่ได้'
                );
        }

        $user->update([
            'role' => $validated['role'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'เปลี่ยนสิทธิ์ผู้ใช้งานเรียบร้อยแล้ว'
            );
    }
}