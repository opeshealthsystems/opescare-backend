@extends('layouts.portal')
@section('title', __('public.adm_bridge_batches_title') . $agent->name)
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', 'Bridge')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.bridge') }}">{{ __('public.adm_bridge_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $agent->name }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_bridge_batches_h2') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.bridge') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('public.adm_bridge_btn_all_agents') }}</a>
</div>
<p class="td-muted mb-6">{{ $agent->name }} · <span class="code-token">{{ $agent->displayKey() }}</span></p>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_bridge_col_batch_id') }}</th>
                    <th>{{ __('public.adm_bridge_col_sync_type') }}</th>
                    <th>{{ __('public.adm_bridge_col_status') }}</th>
                    <th>{{ __('public.adm_bridge_col_records') }}</th>
                    <th>{{ __('public.adm_bridge_col_inserted') }}</th>
                    <th>{{ __('public.adm_bridge_col_updated') }}</th>
                    <th>{{ __('public.adm_bridge_col_errors') }}</th>
                    <th>{{ __('public.adm_bridge_col_started') }}</th>
                    <th>{{ __('public.adm_bridge_col_completed') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td data-label="Batch ID"><span class="code-token">{{ substr($batch->id, 0, 8) }}…</span></td>
                        <td data-label="Sync Type"><span class="badge badge-primary">{{ str_replace('_',' ', $batch->sync_type) }}</span></td>
                        <td data-label="Status">
                            <span class="badge badge-{{ match($batch->status) {
                                'completed'  => 'success',
                                'failed'     => 'danger',
                                'processing' => 'warning',
                                default      => 'neutral'
                            } }}">{{ $batch->status }}</span>
                        </td>
                        <td data-label="Records"><span class="td-strong">{{ $batch->record_count }}</span></td>
                        <td data-label="Inserted">{{ $batch->inserted_count }}</td>
                        <td data-label="Updated">{{ $batch->updated_count }}</td>
                        <td data-label="Errors">
                            @if($batch->error_count > 0)
                                <span class="badge badge-danger">{{ $batch->error_count }}</span>
                            @else
                                <span class="td-muted">0</span>
                            @endif
                        </td>
                        <td data-label="Started">{{ $batch->created_at->format('d M H:i') }}</td>
                        <td data-label="Completed">{{ $batch->completed_at ? $batch->completed_at->format('d M H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="td-muted empty-cell">{{ __('public.adm_bridge_batches_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())<div class="panel-body">{{ $batches->links() }}</div>@endif
</div>

@endsection
