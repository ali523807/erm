<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class CustomerStatementController extends Controller
{
    public function show(Request $request, Customer $customer): View
    {
        return view('customers.statement', $this->statementData($request, $customer));
    }

    public function print(Request $request, Customer $customer): View
    {
        return view('customers.statement-print', $this->statementData($request, $customer));
    }

    public function download(Request $request, Customer $customer): Response
    {
        $data = $this->statementData($request, $customer);

        return Pdf::loadView('customers.statement-pdf', $data)
            ->setPaper('a4')
            ->download('statement-'.$customer->id.'-'.$data['asOfDate']->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function statementData(Request $request, Customer $customer): array
    {
        $asOfDate = Carbon::parse($request->input('as_of', now()->toDateString()))->endOfDay();
        $fromDate = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;

        $invoices = $customer->invoices()
            ->with(['payments', 'creditNotes'])
            ->when($fromDate, fn ($query) => $query->whereDate('invoice_date', '>=', $fromDate->toDateString()))
            ->whereDate('invoice_date', '<=', $asOfDate->toDateString())
            ->orderBy('invoice_date')
            ->get()
            ->each(fn (Invoice $invoice) => $invoice->recalculateTotals());

        return [
            'customer' => $customer,
            'invoices' => $invoices,
            'asOfDate' => $asOfDate,
            'fromDate' => $fromDate,
            'summary' => [
                'invoiceTotal' => (float) $invoices->sum('total_amount'),
                'paidTotal' => (float) $invoices->sum('paid_amount'),
                'creditTotal' => (float) $invoices->sum(fn (Invoice $invoice): float => (float) $invoice->creditNotes->where('status', '!=', 'voided')->sum('amount')),
                'balanceDue' => (float) $invoices->sum('balance_due'),
                'openInvoices' => $invoices->where('balance_due', '>', 0)->count(),
            ],
            'aging' => $this->agingBuckets($invoices, $asOfDate),
            'transactions' => $this->transactions($invoices),
        ];
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array{current: float, days_1_30: float, days_31_60: float, days_61_90: float, days_90_plus: float}
     */
    private function agingBuckets(Collection $invoices, Carbon $asOfDate): array
    {
        $buckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_plus' => 0.0,
        ];

        foreach ($invoices as $invoice) {
            $balance = (float) $invoice->balance_due;

            if ($balance <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date ?: $invoice->invoice_date;
            $daysPastDue = $dueDate ? (int) $dueDate->diffInDays($asOfDate, false) : 0;

            if ($daysPastDue <= 0) {
                $buckets['current'] += $balance;
            } elseif ($daysPastDue <= 30) {
                $buckets['days_1_30'] += $balance;
            } elseif ($daysPastDue <= 60) {
                $buckets['days_31_60'] += $balance;
            } elseif ($daysPastDue <= 90) {
                $buckets['days_61_90'] += $balance;
            } else {
                $buckets['days_90_plus'] += $balance;
            }
        }

        return $buckets;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, array{date: mixed, type: string, reference: string, description: string, debit: float, credit: float, balance: float, url: string}>
     */
    private function transactions(Collection $invoices): Collection
    {
        return $invoices
            ->flatMap(function (Invoice $invoice) {
                $invoiceRow = collect([[
                    'date' => $invoice->invoice_date,
                    'type' => 'Invoice',
                    'reference' => $invoice->invoice_number,
                    'description' => 'Rental invoice',
                    'debit' => (float) $invoice->total_amount,
                    'credit' => 0.0,
                    'balance' => (float) $invoice->balance_due,
                    'url' => route('invoices.show', $invoice),
                ]]);

                $payments = $invoice->payments->map(fn ($payment): array => [
                    'date' => $payment->payment_date,
                    'type' => 'Payment',
                    'reference' => $payment->receiptNumber(),
                    'description' => $payment->method ? str($payment->method)->headline()->toString() : 'Payment received',
                    'debit' => 0.0,
                    'credit' => (float) $payment->amount,
                    'balance' => 0.0,
                    'url' => route('payments.receipt.print', $payment),
                ]);

                $credits = $invoice->creditNotes
                    ->where('status', '!=', 'voided')
                    ->flatMap(function (CreditNote $creditNote): Collection {
                        $rows = collect([[
                            'date' => $creditNote->credit_date,
                            'type' => 'Credit Note',
                            'reference' => $creditNote->credit_note_number,
                            'description' => str($creditNote->reason)->headline()->toString(),
                            'debit' => 0.0,
                            'credit' => (float) $creditNote->amount,
                            'balance' => 0.0,
                            'url' => route('credit-notes.show', $creditNote),
                        ]]);

                        if ((float) $creditNote->refund_amount <= 0) {
                            return $rows;
                        }

                        return $rows->push([
                            'date' => $creditNote->credit_date,
                            'type' => 'Refund',
                            'reference' => $creditNote->refund_reference ?: $creditNote->credit_note_number,
                            'description' => $creditNote->refund_method ? str($creditNote->refund_method)->headline()->toString() : 'Refund issued',
                            'debit' => (float) $creditNote->refund_amount,
                            'credit' => 0.0,
                            'balance' => 0.0,
                            'url' => route('credit-notes.show', $creditNote),
                        ]);
                    });

                return $invoiceRow->merge($payments)->merge($credits);
            })
            ->sortBy(fn (array $row): string => optional($row['date'])->format('Y-m-d').$row['type'])
            ->values();
    }
}
