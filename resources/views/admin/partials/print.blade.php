{{-- $company shared globally via AppServiceProvider (cached) --}}

{{-- This will only show when printing --}}
<div class="print-header" style="display: none;">
    <div style="margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px;">
        <table style="width: 100%;">
            <tr>
                {{-- Left: Company Info --}}
                <td style="width: 60%; vertical-align: top; text-align: left;">
                    @if($company)
                        {{-- Logo --}}
                        @if($company->company_logo && file_exists(public_path('uploads/company/' . $company->company_logo)))
                            <img src="{{ asset('uploads/company/' . $company->company_logo) }}" 
                                 style="height: 150px; margin-bottom: 5px; max-width: 150px;">
                        @endif
                        
                        {{-- Company Name --}}
                        <h2 style="margin: 0 0 5px 0; color: #000; font-size: 20px;">
                            {{ $company->company_name ?? config('app.name', 'Company Name') }}
                        </h2>
                        
                        {{-- Contact Info --}}
                        <div style="color: #666; font-size: 12px; line-height: 1.4;">
                            @if($company->phone1)
                                <div><strong>Phone:</strong> {{ $company->phone1 }}
                                @if($company->phone2)
                                    , {{ $company->phone2 }}
                                @endif
                                </div>
                            @endif
                            
                            @if($company->email1)
                                <div><strong>Email:</strong> {{ $company->email1 }}</div>
                            @endif
                            
                            @if($company->address1)
                                <div><strong>Address:</strong> {{ $company->address1 }}
                                @if($company->address2)
                                    , {{ $company->address2 }}
                                @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </td>
                
                {{-- Right: Report Info --}}
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <h3 style="margin: 0 0 10px 0; color: #000; font-size: 18px;">
                        {{ $title ?? $slot ?? 'Report' }}
                    </h3>
                    
                    <div style="color: #666; font-size: 12px; line-height: 1.4;">
                        @if(isset($fromDate) && $fromDate && isset($toDate) && $toDate)
                        <div><strong>From:</strong><br>
                            {{ date('d M, Y', strtotime($fromDate)) }}
                            to
                            {{ date('d M, Y', strtotime($toDate)) }}
                        </div>
                        @endif
                        
                        <div style="margin-top: 10px;">
                            <strong>Printed:</strong><br>
                            {{ date('d M, Y h:i A') }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- Print button --}}
<button type="button" onclick="printReport()" class="btn btn-warning no-print mb-2">
    <i class="ri-printer-line me-1"></i> Print
</button>

<script>
function printReport() {
    window.print();
}
</script>

<style>
/* Print styles */
@media print {
    /* Show print header */
    .print-header {
        display: block !important;
        page-break-before: always;
    }
    
    .print-header table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    .print-header td {
        padding: 0 10px !important;
        border: none !important;
    }
    
    /* Hide all no-print elements */
    body * {
        visibility: hidden;
    }
    
    /* Only show printable content */
    .printable-content, .printable-content * {
        visibility: visible;
    }
    
    /* Position the printable content */
    .printable-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    
    /* Hide unnecessary elements */
    .no-print, button, .select2, form, .form-label, 
    .dataTables_wrapper, .dataTables_length, .dataTables_filter,
    .dataTables_info, .dataTables_paginate, .card-header .text-end,
    .print-hide {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Logo styling for print */
    .print-header img {
        max-height: 50px !important;
        margin-bottom: 5px !important;
    }
    
    /* Card styling */
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        margin-top: 0 !important;
    }
    
    .card-header {
        background: #f8f9fa !important;
        border-bottom: 1px solid #ddd !important;
        padding: 10px !important;
        color: #000 !important;
    }
    
    .card-title {
        color: #000 !important;
        margin: 0 !important;
        font-size: 16px !important;
    }
    
    /* Table styling */
    .table {
        border-collapse: collapse !important;
        width: 100% !important;
        font-size: 11px !important;
    }
    
    .table th, .table td {
        border: 1px solid #ddd !important;
        padding: 4px !important;
    }
    
    .table-light {
        background: #f8f9fa !important;
        color: #000 !important;
    }
    
    /* Remove colors from rows during print */
    .table-success, .table-danger, .table-warning {
        background: #fff !important;
    }
    
    .text-success, .text-danger, .text-primary, .text-info {
        color: #000 !important;
    }
    
    /* Hide badges background during print */
    .badge {
        background: transparent !important;
        border: 1px solid #000 !important;
        color: #000 !important;
    }
    
    /* Footer styling */
    .table-light.fw-semibold {
        font-weight: bold !important;
    }
    
    /* Summary cards */
    .bg-light {
        background: #f8f9fa !important;
    }
    
    .text-primary {
        color: #000 !important;
    }
}
</style>