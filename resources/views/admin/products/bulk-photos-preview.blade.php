@extends('admin.pages.master')
@section('title', 'Bulk Photos — Review')
@section('content')

<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <a href="{{ route('products.bulkPhotos') }}" class="btn btn-light btn-sm">← Drop different photos</a>
            <h4 class="mt-2 mb-0">Review &amp; confirm <small class="text-muted">nothing is saved yet</small></h4>
            @php($matched = collect($matches)->filter(fn ($m) => $m['product_id'])->count())
            <p class="mb-0"><span class="badge bg-success">{{ $matched }} matched</span> <span class="badge bg-danger">{{ count($matches) - $matched }} need assigning</span></p>
        </div>
    </div>

    <form id="confirmForm" method="POST" action="{{ route('products.bulkPhotosConfirm') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="row g-3" id="cards">
            @foreach ($matches as $i => $m)
                <div class="col-md-6 col-xl-4 photo-card" data-i="{{ $i }}">
                    <div class="card h-100 {{ $m['product_id'] ? 'border-success' : 'border-danger' }}">
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <img src="{{ route('products.bulkPhotosFile', [$token, $m['file']]) }}" style="width:96px;height:96px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;" alt="">
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-break">{{ $m['file'] }}</div>
                                    <div class="assign-label small {{ $m['product_id'] ? 'text-success' : 'text-danger' }}">
                                        @if ($m['product_id'])
                                            → {{ $m['product'] }}@if ($m['sku']) <span class="text-muted">({{ $m['sku'] }})</span>@endif
                                        @else
                                            → No match — find the product below
                                        @endif
                                    </div>
                                    @if ($m['current_image'])
                                        <div class="small text-muted">Current: <img src="{{ url($m['current_image']) }}" style="width:32px;height:32px;object-fit:cover;border-radius:4px;" alt=""> will be replaced</div>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" name="items[{{ $i }}][file]" value="{{ $m['file'] }}">
                            <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $m['product_id'] }}">
                            <input type="hidden" name="items[{{ $i }}][variant_id]" value="{{ $m['variant_id'] }}">
                            <div class="row g-2 mt-2 align-items-end">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Save as</label>
                                    <select class="form-control form-control-sm target-sel" name="items[{{ $i }}][target]">
                                        <option value="variant" @selected($m['target'] === 'variant')>Size photo</option>
                                        <option value="hero" @selected($m['target'] === 'hero')>Main photo</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <div class="form-check also-wrap" style="{{ $m['target'] === 'variant' && $m['variant_id'] ? '' : 'display:none;' }}">
                                        <input type="checkbox" class="form-check-input" name="items[{{ $i }}][also_hero]" value="1" @checked($m['also_hero'])>
                                        <label class="form-check-label small">Also main photo</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary assign-btn">Find product</button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-btn">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="my-4">
            <button type="submit" class="btn btn-success btn-lg">Confirm — save <span id="countLbl">{{ $matched }}</span> photo(s)</button>
        </div>
    </form>
</div>

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Find product <small class="text-muted" id="assignFile"></small></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="assignSearch" class="form-control" placeholder="Type SKU or product name…" autocomplete="off">
                <div id="assignResults" class="list-group mt-2"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    let current = null;
    const modal = new bootstrap.Modal('#assignModal');

    function refreshCount() {
        const n = $('.photo-card:not(.removed)').filter(function () {
            return $(this).find('input[name$="[product_id]"]').val() !== '';
        }).length;
        $('#countLbl').text(n);
    }

    $('.target-sel').on('change', function () {
        const card = $(this).closest('.photo-card');
        const isVariant = $(this).val() === 'variant';
        const hasVariant = card.find('input[name$="[variant_id]"]').val() !== '';
        card.find('.also-wrap').toggle(isVariant && hasVariant);
    });

    $('.remove-btn').on('click', function () {
        const card = $(this).closest('.photo-card');
        card.addClass('removed').hide();
        card.find('input,select').prop('disabled', true);
        refreshCount();
    });

    $('.assign-btn').on('click', function () {
        current = $(this).closest('.photo-card');
        $('#assignFile').text('for ' + current.find('input[name$="[file]"]').val());
        $('#assignSearch').val('');
        $('#assignResults').empty();
        modal.show();
        setTimeout(() => $('#assignSearch').trigger('focus'), 400);
    });

    let timer = null;
    $('#assignSearch').on('input', function () {
        clearTimeout(timer);
        const q = $(this).val();
        if (q.length < 2) { $('#assignResults').empty(); return; }
        timer = setTimeout(() => {
            $.get("{{ route('products.bulkPhotosSearch') }}", { q }, function (rows) {
                const box = $('#assignResults').empty();
                if (!rows.length) box.append('<div class="list-group-item text-muted">No matches — try the SKU from the Products table.</div>');
                rows.forEach(r => {
                    box.append(`<button type="button" class="list-group-item list-group-item-action" data-v="${r.variant_id}" data-p="${r.product_id}" data-label="${r.label.replace(/"/g, '&quot;')}">${r.label}</button>`);
                });
            });
        }, 250);
    });

    $('#assignResults').on('click', 'button', function () {
        const card = current, i = card.data('i');
        card.find('input[name$="[product_id]"]').val($(this).data('p'));
        card.find('input[name$="[variant_id]"]').val($(this).data('v'));
        card.find('.assign-label').removeClass('text-danger').addClass('text-success').html('→ ' + $(this).text());
        card.find('.card').removeClass('border-danger').addClass('border-success');
        card.find('.target-sel').val('variant').trigger('change');
        modal.hide();
        refreshCount();
    });

    $('#confirmForm').on('submit', function (e) {
        let bad = 0;
        $('.photo-card:not(.removed)').each(function () {
            const p = $(this).find('input[name$="[product_id]"]').val();
            const t = $(this).find('.target-sel').val();
            const v = $(this).find('input[name$="[variant_id]"]').val();
            if (!p || (t === 'variant' && !v)) { bad++; $(this).find('.card').addClass('border-warning'); }
        });
        if (bad) { e.preventDefault(); alert(bad + ' photo(s) still need a product — use Find product or Remove.'); }
    });

    refreshCount();
});
</script>
@endsection
