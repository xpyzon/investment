@extends('layouts.admin')

@section('title', 'New Wallet')
@section('heading', 'Create Wallet')

@section('content')
  <form id="walletForm" class="space-y-5 max-w-2xl" onsubmit="return submitWallet(event)">
    <div>
      <label class="block text-sm font-semibold">Name</label>
      <input type="text" name="name" class="w-full border border-black px-3 py-2" required />
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold">Currency</label>
        <input type="text" name="currency" class="w-full border border-black px-3 py-2" placeholder="USDT" required />
      </div>
      <div>
        <label class="block text-sm font-semibold">Network</label>
        <input type="text" name="network" class="w-full border border-black px-3 py-2" placeholder="erc20" required />
      </div>
    </div>
    <div>
      <label class="block text-sm font-semibold">Address template (global address or xpub/custodial template)</label>
      <input type="text" name="address_template" class="w-full border border-black px-3 py-2" />
    </div>
    <div class="grid grid-cols-3 gap-4 items-end">
      <div>
        <label class="block text-sm font-semibold">Requires Tag</label>
        <select name="requires_tag" class="w-full border border-black px-3 py-2">
          <option value="0">No</option>
          <option value="1">Yes</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold">Tag Label</label>
        <input type="text" name="tag_label" class="w-full border border-black px-3 py-2" placeholder="memo" />
      </div>
      <div>
        <label class="block text-sm font-semibold">Confirmations</label>
        <input type="number" name="confirmations" class="w-full border border-black px-3 py-2" value="12" min="0" />
      </div>
    </div>
    <div>
      <label class="block text-sm font-semibold">Icon URL</label>
      <input type="url" name="icon_url" class="w-full border border-black px-3 py-2" />
    </div>
    <div>
      <label class="inline-flex items-center space-x-2">
        <input type="checkbox" name="is_enabled" value="1" checked />
        <span class="text-sm">Enabled</span>
      </label>
    </div>
    <div class="flex items-center gap-3">
      <button type="submit" class="border border-black px-5 py-2 font-semibold">Create</button>
      <a href="{{ route('admin.wallets.index') }}" class="underline">Cancel</a>
    </div>
  </form>

  <script>
    async function submitWallet(e) {
      e.preventDefault();
      const form = document.getElementById('walletForm');
      const data = Object.fromEntries(new FormData(form).entries());
      data.requires_tag = data.requires_tag === '1';
      data.is_enabled = !!data.is_enabled;

      const res = await fetch('/api/admin/wallets', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(data)
      });
      if (res.ok) {
        window.location.href = '{{ route('admin.wallets.index') }}';
      } else {
        const j = await res.json().catch(()=>({message:'Error'}));
        alert(j.message || 'Failed');
      }
      return false;
    }
  </script>
@endsection