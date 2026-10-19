<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentReport::query();
        if (! $request->user()->hasRole('Manager')) {
            $query->where('employee_id', $request->user()->employee_id);
        }
        return view('incidents.index', ['incidents' => $query->latest('id')->paginate(20)]);
    }

    public function create()
    {
        return view('incidents.create', ['products' => Product::orderBy('product_name')->get(['product_id', 'product_name', 'barcode'])]);
    }

    public function store(Request $request, IncidentService $service)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:product,product_id',
            'quantity' => 'required|numeric|min:0.001|max:999999999|decimal:0,3',
            'description' => 'required|string|max:3000', 'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date_format:Y-m-d', 'receipt_number' => 'nullable|string|max:100',
            'evidence' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        foreach (['description', 'batch_number', 'receipt_number'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }
        if ($data['description'] === '') {
            return back()->withErrors(['description' => 'Describe the product safety concern.'])->withInput();
        }
        unset($data['evidence']);
        $path = $request->file('evidence')?->store('incident-evidence', 'local');
        try {
            $incident = $service->reportSafety($data + ['evidence_path' => $path]);
        } catch (\Throwable $e) {
            if ($path) { Storage::disk('local')->delete($path); }
            throw $e;
        }
        return redirect()->route('incidents.show', $incident)->with('status', 'Concern reported. This product is now on safety hold.');
    }

    private function authorizeRead(Request $request, IncidentReport $incident): void
    {
        abort_unless($request->user()->hasRole('Manager') || (int) $incident->employee_id === (int) $request->user()->employee_id, 403);
    }

    public function show(Request $request, IncidentReport $incident)
    {
        $this->authorizeRead($request, $incident);
        return view('incidents.show', compact('incident'));
    }

    public function evidence(Request $request, IncidentReport $incident)
    {
        $this->authorizeRead($request, $incident);
        abort_unless($incident->evidence_path && Storage::disk('local')->exists($incident->evidence_path), 404);
        return Storage::disk('local')->response($incident->evidence_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, IncidentReport $incident, IncidentService $service)
    {
        $data = $request->validate(['status' => 'required|in:under_investigation,resolved', 'findings' => 'required|string|max:3000']);
        $findings = trim(strip_tags($data['findings']));
        abort_if($findings === '', 422);
        $service->review($incident, $data['status'], $findings);
        return back()->with('status', 'Review saved. Resolution releases the sale hold only when no other open safety case remains.');
    }

    public function acknowledge(Request $request, IncidentReport $incident, IncidentService $service)
    {
        $data = $request->validate(['external_reference' => 'required|string|max:150']);
        $reference = trim(strip_tags($data['external_reference']));
        abort_if($reference === '', 422);
        $service->acknowledge($incident, $reference);
        return back()->with('status', 'Manual external acknowledgement recorded.');
    }

    public function export(IncidentReport $incident)
    {
        AuditLogger::record('incident_exported', 'incident_reports', $incident->id, 'Exported incident report for manual external handoff.');
        return response()->json($incident->makeHidden('evidence_path'))->header('Content-Disposition', 'attachment; filename="incident-'.$incident->id.'.json"')->header('Cache-Control', 'private, no-store');
    }
}
