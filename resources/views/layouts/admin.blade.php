<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title','PrintShop')</title><link rel="stylesheet" href="{{ asset('css/portal-shell.css') }}">@stack('styles')</head><body>
<div class="portal"><aside class="sidebar"><div class="brand"><div class="brand-logo">🖨️</div><div class="brand-name">PrintShop</div></div><div class="side-label">ADMIN CONSOLE</div><nav class="nav">
<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="nav-ico">⌂</span><span>ภาพรวมร้าน</span></a>
<a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}"><span class="nav-ico">🖨️</span><span>บริการ</span></a>
<a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="nav-ico">👥</span><span>ผู้ใช้งาน</span></a>
<a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><span class="nav-ico">📊</span><span>รายงาน</span></a>
</nav><div class="side-spacer"></div><div class="side-user"><strong>{{ auth()->user()->name }}</strong><span>ผู้ดูแลระบบ</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">ออกจากระบบ</button></form></div></aside><main class="main"><div class="main-inner"><header class="page-head"><div><h1>@yield('page-title')</h1><p>@yield('page-subtitle')</p></div><div class="head-badge"><span class="dot"></span>ระบบหลังร้านพร้อมใช้งาน</div></header>
@if(session('success'))<div class="flash success">✅ {{ session('success') }}</div>@endif
@if(session('error'))<div class="flash error">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif
@yield('content')</div></main></div>
@stack('scripts')</body></html>