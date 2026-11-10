@extends('layouts.sementara')

@section('title', 'Dashboard - RTA-I Komdigi')

@section('content')
    <div class="bg-white p-8 rounded-lg shadow space-y-2">
        <h1 class="text-xl font-bold text-primary">Dashboard</h1>
        <p>Nama: <strong>{{ auth()->user()->name }}</strong></p>
        <p>Role: <strong>{{ auth()->user()->role->label() }}</strong></p>
        <p>Divisi: <strong>{{ auth()->user()->divisi ?? '-' }}</strong></p>
    </div>
@endsection
