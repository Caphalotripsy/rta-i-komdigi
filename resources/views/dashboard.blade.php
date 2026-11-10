@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard {{ auth()->user()->role->label() }}" subjudul="Selamat datang, {{ auth()->user()->name }}." />

    <div class="card p-6 text-sm text-slate-600">
        Halaman kerja untuk peran <strong>{{ auth()->user()->role->label() }}</strong> sedang disiapkan.
    </div>
@endsection
