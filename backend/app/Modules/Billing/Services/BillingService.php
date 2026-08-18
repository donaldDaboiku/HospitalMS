<?php

namespace App\Modules\Billing\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Billing\Models\InsuranceClaim;
use App\Modules\Billing\Models\InsuranceProvider;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Payment;
use App\Modules\Patients\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    // ── Invoices ──

    public function invoices(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Invoice::query()->with(['patient:id,mrn,first_name,middle_name,last_name', 'creator:id,first_name,last_name']);
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->latest()->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function createInvoice(User $actor, array $data): Invoice
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $this->hospitalId($actor, $data);
            $patient = Patient::query()->whereKey($data['patient_id'])->where('hospital_id', $hospitalId)->first();
            if (! $patient) {
                throw ValidationException::withMessages(['patient_id' => ['Patient not found in this hospital.']]);
            }

            $invoiceNumber = $this->nextInvoiceNumber($hospitalId);

            $invoice = Invoice::query()->create([
                'hospital_id' => $hospitalId,
                'patient_id' => $patient->id,
                'encounter_id' => $data['encounter_id'] ?? null,
                'created_by' => $actor->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'draft',
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $invoice->items()->create([
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                    'reference_type' => $item['reference_type'] ?? null,
                    'reference_id' => $item['reference_id'] ?? null,
                ]);
                $subtotal += $lineTotal;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal - $invoice->discount + $invoice->tax,
            ]);

            $this->auditLogger->record('billing.invoice_created', 'billing', $invoice, newValues: $invoice->fresh()->toArray(), user: $actor);

            return $invoice->load(['patient', 'items', 'creator']);
        });
    }

    public function issueInvoice(User $actor, Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => ['Only draft invoices can be issued.']]);
        }

        $invoice->update(['status' => 'issued', 'issued_at' => now()]);
        $this->auditLogger->record('billing.invoice_issued', 'billing', $invoice, newValues: ['status' => 'issued'], user: $actor);

        return $invoice->fresh(['patient', 'items', 'payments']);
    }

    // ── Payments ──

    public function recordPayment(User $actor, Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($actor, $invoice, $data) {
            if (in_array($invoice->status, ['cancelled', 'refunded'], true)) {
                throw ValidationException::withMessages(['status' => ['Cannot pay a cancelled or refunded invoice.']]);
            }
            if ($invoice->status === 'draft') {
                throw ValidationException::withMessages(['status' => ['Issue the invoice before recording payment.']]);
            }

            $remaining = (float) $invoice->total - (float) $invoice->amount_paid;
            if ((float) $data['amount'] > $remaining + 0.01) {
                throw ValidationException::withMessages(['amount' => ['Payment exceeds the outstanding balance.']]);
            }

            $payment = Payment::query()->create([
                'hospital_id' => $invoice->hospital_id,
                'invoice_id' => $invoice->id,
                'received_by' => $actor->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
            ]);

            $newPaid = (float) $invoice->amount_paid + (float) $data['amount'];
            $invoice->update([
                'amount_paid' => $newPaid,
                'status' => $newPaid >= (float) $invoice->total ? 'paid' : 'partial',
            ]);

            $this->auditLogger->record('billing.payment_received', 'billing', $payment, newValues: $payment->toArray(), user: $actor);

            return $payment->load('invoice.patient');
        });
    }

    // ── Insurance ──

    public function insuranceProviders(User $actor, array $filters): LengthAwarePaginator
    {
        $query = InsuranceProvider::query()->with('plans');
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);
        return $query->orderBy('name')->paginate(min((int) ($filters['per_page'] ?? 50), 100));
    }

    public function createInsuranceProvider(User $actor, array $data): InsuranceProvider
    {
        $hospitalId = $this->hospitalId($actor, $data);
        $provider = InsuranceProvider::query()->create([...$data, 'hospital_id' => $hospitalId]);
        $this->auditLogger->record('insurance.provider_created', 'billing', $provider, newValues: $provider->toArray(), user: $actor);
        return $provider;
    }

    public function submitClaim(User $actor, Invoice $invoice, array $data): InsuranceClaim
    {
        return DB::transaction(function () use ($actor, $invoice, $data) {
            if (! in_array($invoice->status, ['issued', 'partial', 'paid'], true)) {
                throw ValidationException::withMessages(['status' => ['Invoice must be issued before claiming.']]);
            }

            $claim = InsuranceClaim::query()->create([
                'hospital_id' => $invoice->hospital_id,
                'invoice_id' => $invoice->id,
                'patient_insurance_id' => $data['patient_insurance_id'],
                'submitted_by' => $actor->id,
                'claimed_amount' => $data['claimed_amount'],
                'status' => 'submitted',
                'claim_reference' => $data['claim_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            $this->auditLogger->record('insurance.claim_submitted', 'billing', $claim, newValues: $claim->toArray(), user: $actor);

            return $claim->load(['invoice.patient', 'patientInsurance.plan.provider']);
        });
    }

    // ── Helpers ──

    private function nextInvoiceNumber(string $hospitalId): string
    {
        $last = Invoice::query()->where('hospital_id', $hospitalId)->max('invoice_number');
        $seq = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'INV-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    private function hospitalId(User $actor, array $data): string
    {
        $id = $actor->isSuperAdmin() ? ($data['hospital_id'] ?? null) : $actor->hospital_id;
        abort_if($id === null, 422, 'A hospital is required.');
        return $id;
    }

    private function scopeHospital(Builder $query, User $actor, ?string $requested): void
    {
        if (! $actor->isSuperAdmin()) {
            $query->where('hospital_id', $actor->hospital_id);
        } elseif ($requested) {
            $query->where('hospital_id', $requested);
        }
    }
}
