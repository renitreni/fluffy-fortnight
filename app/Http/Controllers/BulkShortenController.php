<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBulkShortening;
use App\Models\BulkJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BulkShortenController extends Controller
{
    public function index(Request $request)
    {
        $bulkJobs = BulkJob::where('user_id', $request->user()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Links/BulkShorten', [
            'bulkJobs' => $bulkJobs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // max 5MB
        ]);

        $file = $request->file('csv_file');

        // Count rows to show accurate progress. Skip header.
        $totalRows = max(0, count(file($file->getRealPath())) - 1);
        if ($totalRows === 0) {
            return back()->withErrors(['csv_file' => 'The CSV file is empty or missing a header row.']);
        }

        $path = $file->store('bulk_imports', 'local');

        $bulkJob = BulkJob::create([
            'user_id' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'total_rows' => $totalRows,
        ]);

        ProcessBulkShortening::dispatch($bulkJob, $path);

        return redirect()->route('bulk.index')->with('flash', [
            'type' => 'success',
            'message' => 'Bulk shortening job started! We will notify you when it is complete.',
        ]);
    }

    public function download(Request $request, BulkJob $bulkJob)
    {
        if ($bulkJob->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($bulkJob->status !== 'completed' || ! $bulkJob->result_file_path) {
            abort(404, 'Result file not found or job not completed.');
        }

        return Storage::disk('local')->download($bulkJob->result_file_path, 'bulk_results_'.$bulkJob->id.'.csv');
    }
}
