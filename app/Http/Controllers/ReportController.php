<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports', ['user' => $request->user()]);
    }

    // Pro feature: every transaction as a CSV file for the accountant.
    public function export(Request $request)
    {
        $rows = $request->user()->transactions()->orderBy('booked_on')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'label', 'amount']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->booked_on->toDateString(), $row->label, number_format($row->amount_cents / 100, 2, '.', '')]);
            }
            fclose($out);
        }, 'transactions.csv', ['Content-Type' => 'text/csv']);
    }

    // Pro feature: next month's balance from the average of the last three months.
    public function forecast(Request $request)
    {
        $since = now()->subMonths(3)->startOfDay();
        $total = $request->user()->transactions()->where('booked_on', '>=', $since)->sum('amount_cents');

        return response()->json(['next_month_cents' => intdiv((int) $total, 3)]);
    }
}
