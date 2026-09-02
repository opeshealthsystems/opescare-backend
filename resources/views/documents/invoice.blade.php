@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Facture Médicale' : 'Medical Invoice' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Relevé officiel des frais' : 'Official Statement of Charges' }}
@endsection

@section('content')
    <!-- Financial / Coverage Details -->
    <div class="content-card">
        <div class="card-header">
            {{ $language === 'fr' ? 'INFORMATIONS DE FACTURATION & ASSURANCE' : 'BILLING & INSURANCE CLAIM COVERAGE' }}
        </div>
        <div class="card-body" style="padding: 3mm 4mm;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4mm;">
                <div>
                    <span style="color: #64748B;">Payer / Tiers Payeur:</span>
                    <strong style="display: block; color: #0F172A; margin-top: 0.5mm;">{{ $payload['insurance_provider'] ?? 'Self-Pay / Aucun' }}</strong>
                </div>
                <div>
                    <span style="color: #64748B;">Policy Number / N° Police:</span>
                    <strong style="display: block; color: #0F172A; margin-top: 0.5mm;">{{ $payload['insurance_policy_number'] ?? 'N/A' }}</strong>
                </div>
                <div>
                    <span style="color: #64748B;">Payment Terms / Conditions:</span>
                    <strong style="display: block; color: #0F766E; margin-top: 0.5mm;">{{ $payload['payment_terms'] ?? 'Due Upon Receipt' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Items/Services Table -->
    <div class="content-card">
        <div class="card-header">
            {{ $language === 'fr' ? 'SERVICES ET PRODUITS FACTURÉS' : 'ITEMIZED SERVICES & CHARGES' }}
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>{{ $language === 'fr' ? 'SERVICE / PRODUIT' : 'ITEM / SERVICE DESCRIPTION' }}</th>
                        <th>CODE</th>
                        <th style="text-align: right;">QTY</th>
                        <th style="text-align: right;">UNIT PRICE</th>
                        <th style="text-align: right;">INSURANCE COV.</th>
                        <th style="text-align: right;">PATIENT RESP.</th>
                        <th style="text-align: right;">TOTAL AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['items'] ?? [] as $item)
                        <tr>
                            <td style="font-weight: 600; color: #0F4C81;">{{ $item['description'] }}</td>
                            <td style="font-family: monospace; font-size: 9.5px; color: #475569;">{{ $item['service_code'] ?? 'N/A' }}</td>
                            <td style="text-align: right;">{{ $item['quantity'] }}</td>
                            <td style="text-align: right;">{{ number_format($item['unit_price'], 2) }} XAF</td>
                            <td style="text-align: right; color: #166534; font-weight: 500;">-{{ number_format($item['insurance_covered'] ?? 0, 2) }} XAF</td>
                            <td style="text-align: right; font-weight: 600;">{{ number_format($item['patient_responsibility'], 2) }} XAF</td>
                            <td style="text-align: right; font-weight: 700; color: #0F172A;">{{ number_format($item['total_amount'], 2) }} XAF</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invoice Summary Calculation Box -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 6mm;">
        <div style="width: 80mm; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 4mm; box-sizing: border-box;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px; color: #475569;">
                <span>Subtotal / Sous-total:</span>
                <span>{{ number_format($payload['subtotal'] ?? 0, 2) }} XAF</span>
            </div>
            @if(!empty($payload['discount']))
                <div style="display: flex; justify-content: space-between; margin-bottom: 2px; color: #166534;">
                    <span>Discount / Remise:</span>
                    <span>-{{ number_format($payload['discount'], 2) }} XAF</span>
                </div>
            @endif
            @if(!empty($payload['tax']))
                <div style="display: flex; justify-content: space-between; margin-bottom: 2px; color: #475569;">
                    <span>Tax / TVA:</span>
                    <span>+{{ number_format($payload['tax'], 2) }} XAF</span>
                </div>
            @endif
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; padding-bottom: 2px; border-bottom: 1px solid #E2E8F0; color: #166534; font-weight: 500;">
                <span>Insurance Share / Assur.:</span>
                <span>-{{ number_format($payload['insurance_total'] ?? 0, 2) }} XAF</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: #0F4C81;">
                <span>{{ $language === 'fr' ? 'Net à payer (Patient) :' : 'Patient Balance Due :' }}</span>
                <span>{{ number_format($payload['patient_total'] ?? 0, 2) }} XAF</span>
            </div>
        </div>
    </div>
@endsection
