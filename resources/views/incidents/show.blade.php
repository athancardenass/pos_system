@extends('layouts.app')
@section('title', 'Incident #'.$incident->id)
@section('content')
<div class="page-head"><h1>Incident #{{ $incident->id }}</h1><a class="btn btn-secondary" href="{{ route('incidents.index') }}">Back to incidents</a></div>
@include('incidents.errors')
<div class="incident-detail">
<p><strong>{{ $incident->kind === 'product_safety' ? 'Product safety' : 'Cash variance' }}</strong> · {{ ucfirst(str_replace('_', ' ', $incident->status)) }}</p>
<p>{{ $incident->description }}</p>
@if ($incident->kind === 'product_safety')
<dl><dt>Product</dt><dd>{{ $incident->snapshot['product_name'] }} — {{ $incident->snapshot['barcode'] }}</dd><dt>Inventory SKU</dt><dd>{{ $incident->snapshot['inventory_sku'] ?? 'Not mapped' }}</dd><dt>Affected quantity</dt><dd>{{ $incident->quantity }}</dd><dt>Batch / expiry</dt><dd>{{ $incident->batch_number ?? 'Not supplied' }} / {{ $incident->expiry_date ?? 'Not supplied' }}</dd><dt>Receipt reference (as reported)</dt><dd>{{ $incident->receipt_number ?? 'Not supplied' }}</dd></dl>
<p>Every unresolved safety report holds the entire product. Resolving this case releases its hold only; other open cases continue to block sales. Physically separate affected items and coordinate their disposition with Inventory.</p>
@else
<p><a href="{{ route('cash-drawer.report', $incident->drawer_id) }}">View original shift report #{{ $incident->drawer_id }}</a></p>
<dl><dt>HRMS employee number</dt><dd>{{ $incident->snapshot['hrms_employee_no'] ?? 'Not mapped — identify employee before HR handoff' }}</dd><dt>Expected cash</dt><dd>₱{{ number_format($incident->expected_cash, 2) }}</dd><dt>Actual cash</dt><dd>₱{{ number_format($incident->actual_cash, 2) }}</dd><dt>{{ $incident->difference < 0 ? 'Shortage' : 'Overage' }}</dt><dd>₱{{ number_format(abs($incident->difference), 2) }}</dd></dl>
<p>This case preserves the original count. Manager review does not change the shift balance or initiate payroll deductions.</p>
@endif
@if ($incident->evidence_path)<p><a href="{{ route('incidents.evidence', $incident) }}">View evidence photo</a></p>@endif
<h2>Manager findings</h2><p>{{ $incident->findings ?? 'Awaiting manager review.' }}</p>
@if (auth()->user()->hasRole('Manager') && $incident->status !== 'resolved')
<form class="incident-form" method="POST" action="{{ route('incidents.review', $incident) }}">@csrf
<label for="findings">Investigation, recount or disposition findings</label><textarea id="findings" name="findings" rows="4" maxlength="3000" required>{{ old('findings') }}</textarea>
<label for="status">Decision</label><select id="status" name="status"><option value="under_investigation">Under investigation — keep safety hold</option><option value="resolved">Resolved — release this case's safety hold</option></select>
<button type="submit" class="btn">Save manager review</button></form>
@endif
<h2>{{ $incident->kind === 'product_safety' ? 'Inventory' : 'HRMS' }} handoff</h2>
<p>Status: <strong>{{ ucfirst(str_replace('_', ' ', $incident->external_status)) }}</strong>. Automatic incident submission is not connected.</p>
@if ($incident->external_reference)<p>External acknowledgement reference: {{ $incident->external_reference }}</p>@endif
@if (auth()->user()->hasRole('Manager'))
<p><a class="btn btn-secondary" href="{{ route('incidents.export', $incident) }}">Download report (JSON)</a></p><p>Deliver this report through your agreed channel. Evidence photo is available separately. Record the reference only after the receiving team acknowledges it.</p>
@if ($incident->reviewed_by && ! $incident->external_reference)
<form class="incident-form" method="POST" action="{{ route('incidents.acknowledge', $incident) }}">@csrf<label for="external_reference">Receiving team's case / acknowledgement reference</label><input id="external_reference" name="external_reference" maxlength="150" required><button class="btn" type="submit">Record manual acknowledgement</button></form>
@endif
@endif
<h2>Case history</h2><ol>@foreach ($incident->history as $entry)<li><time>{{ \Carbon\Carbon::parse($entry['at'])->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time> · {{ str_replace('_', ' ', $entry['action']) }} · Employee #{{ $entry['employee_id'] }}<p>{{ $entry['notes'] }}</p></li>@endforeach</ol>
</div>
@endsection
@push('styles')
<style>.incident-detail { max-width:55rem; } .incident-detail h2 { margin-top:2rem; } .incident-detail dl { display:grid; grid-template-columns:minmax(8rem,1fr) 2fr; gap:.7rem; } .incident-detail dd { margin:0; overflow-wrap:anywhere; } .incident-detail p { overflow-wrap:anywhere; } .incident-form { display:grid; gap:.65rem; max-width:42rem; } .incident-form textarea { font:inherit; padding:.7rem; } .incident-form button { justify-self:start; } @media(max-width:560px) { .incident-detail dl { grid-template-columns:1fr; } }</style>
@endpush
