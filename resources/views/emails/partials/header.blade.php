@php
    // Self-contained so the partial works standalone in any mail view.
    $company = $company ?? \App\Models\CompanyDetails::cached();
    $brand = trim((string) $company->company_name);
    if ($siteBrand = trim((string) ($siteBrand ?? ''))) {
        $brand = $siteBrand;
    }
    if ($brand === '' || strcasecmp($brand, 'Evergreen') === 0) {
        $brand = 'Alam Mini Market';
    }
    $logoUrl = $company->company_logo ? asset('uploads/company/'.$company->company_logo) : null;
@endphp

<!-- Outer wrapper -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;">
  <tr>
    <td align="center" style="padding-top:20px;padding-bottom:20px;">
      <!-- Card -->
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;color:#111827;">

        <!-- Header row -->
        <tr>
          <td align="center" style="background:#1A2E22;padding:24px 24px 16px;">
            @if ($logoUrl)
              <img src="{{ $logoUrl }}" alt="{{ $brand }}" width="120" style="display:block;width:120px;height:auto;border-radius:4px;">
            @endif
            <div style="color:#ffffff;font-size:20px;font-weight:bold;margin-top:12px;">{{ $brand }}</div>
          </td>
        </tr>

        <!-- Body row -->
        <tr>
          <td style="padding:24px;">
