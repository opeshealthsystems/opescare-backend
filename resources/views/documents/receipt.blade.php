@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Reçu de Paiement' : 'Payment Receipt' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Reçu officiel de paiement — REC' : 'Official Payment Receipt — REC' }}
@endsection

@section('content')
<style>
    /* PAID stamp */
    .paid-stamp {
        position: absolute;
        top: 0;
        right: 0;
        width: 64px;
        height: 64px;
        border: 3px dashed #059669;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #059669;
        font-weight: 800;
        font-size: 10px;
        line-height: 1.2;
        text-align: center;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        background: #ECFDF5;
    }
    .payment-summary-card {
        position: relative;
        background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        border: 1.5px solid #6EE7B7;
        border-radius: 8px;
        padding: 5mm 6mm;
        margin-bottom: 6mm;
    }
    .amount-figure {
        font-size: 28px;
        font-weight: 800;
        color: #059669;
        line-height: 1;
        letter-spacing: -0.5px;
    }
    .amount-currency {
        font-size: 14px;
        font-weight: 600;
        color: #047857;
    }
    .payment-meta-row {
        display: flex;
        gap: 8mm;
        margin-top: 3mm;
        flex-wrap: wrap;
    }
    .payment-meta-item { display: flex; flex-direction: column; }
    .payment-meta-label {
        font-size: 8.5px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .payment-meta-value { font-size: 11px; font-weight: 700; color: #0F172A; margin-top: 0.5mm; }
    .method-badge {
        display: inline-flex;
        align-items: center;
        gap: 1.5mm;
        background-color: #FFEDD5;
        border: 1px solid #FED7AA;
        color: #C2410C;
        padding: 0.5mm 2mm;
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 700;
    }
    .reference-mono {
        font-family: 'Courier New', monospace;
        font-size: 10.5px;
        font-weight: 700;
        color: #0F172A;
        background-color: #F1F5F9;
        padding: 0.5mm 2mm;
        border-radius: 3px;
        display: inline-block;
        letter-spacing: 0.5px;
    }
    .amount-words-block {
        background-color: #F8FAFC;
        border-left: 4px solid #059669;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 6mm;
        font-style: italic;
        font-size: 10.5px;
        color: #334155;
    }
    .amount-words-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        margin-bottom: 1mm;
        font-style: normal;
    }
    .balance-confirmed {
        display: flex;
        align-items: center;
        gap: 3mm;
        background-color: #ECFDF5;
        border: 1.5px solid #6EE7B7;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 6mm;
        font-weight: 700;
        color: #065F46;
        font-size: 11px;
    }
    .balance-pending {
        background-color: #FEF3C7;
        border-color: #FCD34D;
        color: #92400E;
    }
    .receipt-note {
        text-align: center;
        font-size: 9px;
        color: #64748B;
        font-style: italic;
        border-top: 1px dashed #CBD5E1;
        padding-top: 3mm;
        margin-top: 2mm;
    }
</style>

<div class="payment-summary-card">
    <div class="paid-stamp">PAID<br>PAYÉ</div>
    <div style="margin-bottom: 2mm;">
        <span style="font-size: 8.5px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">
            {{ $language === 'fr' ? 'MONTANT PAYÉ' : 'AMOUNT PAID' }}
        </span>
    </div>
    <div>
        <span class="amount-figure">{{ number_format($payload['amount_paid']) }}</span>
        <span class="amount-currency"> XAF</span>
    </div>
    <div class="payment-meta-row">
        <div class="payment-meta-item">
            <span class="payment-meta-label">{{ $language === 'fr' ? 'MODE DE PAIEMENT' : 'PAYMENT METHOD' }}</span>
            <span class="payment-meta-value">
                <span class="method-badge">📱 {{ $payload['payment_method'] }}</span>
            </span>
        </div>
        @if(!empty($payload['mtn_reference']))
        <div class="payment-meta-item">
            <span class="payment-meta-label">{{ $language === 'fr' ? 'RÉFÉRENCE' : 'REFERENCE' }}</span>
            <span class="payment-meta-value">
                <span class="reference-mono">{{ $payload['mtn_reference'] }}</span>
            </span>
        </div>
        @endif
        <div class="payment-meta-item">
            <span class="payment-meta-label">{{ $language === 'fr' ? 'DATE DE PAIEMENT' : 'PAYMENT DATE' }}</span>
            <span class="payment-meta-value">{{ $payload['payment_date'] }}</span>
        </div>
        <div class="payment-meta-item">
            <span class="payment-meta-label">{{ $language === 'fr' ? 'FACTURE N°' : 'INVOICE NO.' }}</span>
            <span class="payment-meta-value">{{ $payload['invoice_number'] }}</span>
        </div>
    </div>
</div>

<!-- Amount in words -->
<div class="amount-words-block">
    <div class="amount-words-label">
        {{ $language === 'fr' ? 'Montant en lettres / Amount in words' : 'Amount in words / Montant en lettres' }}
    </div>
    {{ strtoupper($payload['amount_words']) }}
</div>

<!-- Items Paid Table -->
<div class="content-card">
    <div class="card-header" style="background-color: #ECFDF5; color: #065F46;">
        {{ $language === 'fr' ? 'DÉTAIL DES PRESTATIONS RÉGLÉES' : 'ITEMS PAID — SERVICE BREAKDOWN' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'DESCRIPTION DU SERVICE' : 'SERVICE / DESCRIPTION' }}</th>
                    <th style="text-align: right; width: 35mm;">{{ $language === 'fr' ? 'MONTANT (XAF)' : 'AMOUNT (XAF)' }}</th>
                </tr>
            </thead>
            <tbody>
                @php $subtotal = 0; @endphp
                @foreach($payload['items_paid'] ?? [] as $item)
                    @php $subtotal += (int)($item['amount'] ?? 0); @endphp
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ number_format($item['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #F8FAFC;">
                    <td style="font-weight: 700; color: #0F172A; text-transform: uppercase; font-size: 10.5px;">
                        {{ $language === 'fr' ? 'SOUS-TOTAL' : 'SUBTOTAL' }}
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 12px; color: #059669;">
                        {{ number_format($subtotal) }} XAF
                    </td>
                </tr>
                @if(($payload['change_given'] ?? 0) > 0)
                <tr>
                    <td style="color: #64748B;">{{ $language === 'fr' ? 'Monnaie rendue' : 'Change Given' }}</td>
                    <td style="text-align: right; color: #64748B;">{{ number_format($payload['change_given']) }} XAF</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>

<!-- Transaction Details -->
<div class="content-card">
    <div class="card-header" style="background-color: #ECFDF5; color: #065F46;">
        {{ $language === 'fr' ? 'INFORMATIONS DE TRANSACTION' : 'TRANSACTION DETAILS' }}
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4mm;">
            <div>
                <div style="font-size: 8.5px; color: #64748B; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 1mm;">
                    {{ $language === 'fr' ? 'Reçu pour' : 'Receipt For' }}
                </div>
                <div style="font-weight: 600; color: #0F172A;">{{ $payload['receipt_for'] }}</div>
            </div>
            <div>
                <div style="font-size: 8.5px; color: #64748B; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 1mm;">
                    {{ $language === 'fr' ? 'Caissier' : 'Cashier' }}
                </div>
                <div style="font-weight: 600; color: #0F172A;">{{ $payload['cashier_name'] }}</div>
                <div style="font-size: 9px; color: #64748B; font-family: monospace;">ID: {{ $payload['cashier_id'] }}</div>
            </div>
            <div>
                <div style="font-size: 8.5px; color: #64748B; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 1mm;">
                    {{ $language === 'fr' ? 'N° Facture' : 'Invoice Reference' }}
                </div>
                <div style="font-weight: 700; color: #0F172A; font-family: monospace;">{{ $payload['invoice_number'] }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Balance Confirmation -->
@if($payload['payment_complete'] ?? false)
<div class="balance-confirmed">
    <span style="font-size: 18px;">✅</span>
    <div>
        <div>
            {{ $language === 'fr' ? 'SOLDE DÛ : 0 XAF' : 'BALANCE DUE: 0 XAF' }}
            <span style="margin-left: 3mm; background-color: #059669; color: #FFFFFF; padding: 0.5mm 2.5mm; border-radius: 9999px; font-size: 9px; letter-spacing: 0.5px; text-transform: uppercase;">
                {{ $language === 'fr' ? 'COMPTE SOLDÉ' : 'ACCOUNT SETTLED' }}
            </span>
        </div>
        <div style="font-size: 9px; font-weight: 400; margin-top: 0.5mm; color: #047857;">
            {{ $language === 'fr' ? 'La totalité du montant dû a été réglée.' : 'The full outstanding balance has been cleared.' }}
        </div>
    </div>
</div>
@else
<div class="balance-confirmed balance-pending">
    <span style="font-size: 18px;">⚠️</span>
    <div>
        <div>
            {{ $language === 'fr' ? 'SOLDE RESTANT' : 'REMAINING BALANCE' }}:
            <strong style="margin-left: 2mm;">{{ number_format($payload['balance_due'] ?? 0) }} XAF</strong>
        </div>
        <div style="font-size: 9px; font-weight: 400; margin-top: 0.5mm;">
            {{ $language === 'fr' ? 'Un solde est encore dû sur ce compte.' : 'An outstanding balance remains on this account.' }}
        </div>
    </div>
</div>
@endif

<div class="receipt-note">
    {{ $language === 'fr'
        ? 'Ce reçu est votre preuve de paiement officielle. Conservez-le précieusement pour toute réclamation future.'
        : 'This receipt is your official proof of payment. Keep for your records and present for any future claims or refund requests.' }}
</div>
@endsection
