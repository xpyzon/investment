@extends('layouts.admin')

@section('title', 'Wallets')
@section('heading', 'Wallets')

@section('content')
  <div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.wallets.create') }}" class="border border-black px-4 py-2 font-semibold">New Wallet</a>
  </div>
  <div class="grid grid-cols-1 gap-4">
    @forelse ($wallets as $w)
      <div class="border border-black p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xl font-bold">{{ $w->name }}</div>
            <div class="text-sm">{{ $w->currency }} · {{ $w->network }}</div>
          </div>
          <div class="text-sm">{{ $w->is_enabled ? 'ENABLED' : 'DISABLED' }}</div>
        </div>
        <div class="mt-3 text-sm">Confirmations: {{ $w->confirmations }}</div>
        <div class="mt-3 text-xs break-all">Template: {{ $w->address_template ?? '—' }}</div>
      </div>
    @empty
      <div class="border border-black p-6">No wallets yet.</div>
    @endforelse
  </div>
@endsection