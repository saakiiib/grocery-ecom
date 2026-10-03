@php
    $company = \App\Models\CompanyDetails::cached();
    $brand = $company->company_name ?: config('app.name', 'Evergreen Foods');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#1A2E22;color:#ffffff;">
    <tr>
        <td style="padding:18px 24px;font-size:20px;font-weight:bold;">{{ $brand }}</td>
    </tr>
</table>
