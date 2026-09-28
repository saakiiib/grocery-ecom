@extends('admin.pages.master')
@section('title', 'Company Details')
@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-12">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0 flex-grow-1">{{ 'Company Details' }}</h3>
                    </div>

                    <form id="createThisForm" action="{{ route('admin.companyDetails') }}" method="POST"
                        enctype="multipart/form-data">
                        <div class="card-body">
                            @csrf
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <div class="row g-3">

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>{{ 'Company Name' }} <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('company_name') is-invalid @enderror"
                                            id="company_name" name="company_name" value="{{ $data->company_name }}"
                                            required>
                                        @error('company_name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>{{ 'Business Name' }}</label>
                                        <input type="text"
                                            class="form-control @error('business_name') is-invalid @enderror"
                                            id="business_name" name="business_name" value="{{ $data->business_name }}">
                                        @error('business_name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>{{ 'Email' }}</label>
                                        <input type="email" class="form-control @error('email1') is-invalid @enderror"
                                            id="email1" name="email1" value="{{ $data->email1 }}">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>{{ 'Email 2' }}</label>
                                        <input type="email" class="form-control @error('email2') is-invalid @enderror"
                                            id="email2" name="email2" value="{{ $data->email2 }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Phone' }}</label>
                                        <input type="text" inputmode="tel" class="form-control @error('phone1') is-invalid @enderror"
                                            id="phone1" name="phone1" value="{{ $data->phone1 }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Phone 2' }}</label>
                                        <input type="text" inputmode="tel" class="form-control @error('phone2') is-invalid @enderror"
                                            id="phone2" name="phone2" value="{{ $data->phone2 }}">
                                    </div>
                                </div>

                                <div class="col-6 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Phone 3' }}</label>
                                        <input type="text" inputmode="tel" class="form-control @error('phone3') is-invalid @enderror"
                                            id="phone3" name="phone3" value="{{ $data->phone3 }}">
                                    </div>
                                </div>

                                <div class="col-6 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Phone 4' }}</label>
                                        <input type="text" inputmode="tel" class="form-control @error('phone4') is-invalid @enderror"
                                            id="phone4" name="phone4" value="{{ $data->phone4 }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'WhatsApp' }}</label>
                                        <input type="text" inputmode="tel" class="form-control @error('whatsapp') is-invalid @enderror"
                                            id="whatsapp" name="whatsapp" value="{{ $data->whatsapp }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ 'Address' }}</label>
                                        <input type="text" class="form-control @error('address1') is-invalid @enderror"
                                            id="address1" name="address1" value="{{ $data->address1 }}">
                                    </div>
                                </div>
                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Opening Cash Balance' }}</label>
                                        <input type="number" class="form-control @error('opening_cash_balance') is-invalid @enderror"
                                            id="opening_cash_balance" name="opening_cash_balance" value="{{ $data->opening_cash_balance }}">
                                    </div>
                                </div>

                                <div class="col-6 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Address 2' }}</label>
                                        <input type="text"
                                            class="form-control @error('address2') is-invalid @enderror" id="address2"
                                            name="address2" value="{{ $data->address2 }}">
                                    </div>
                                </div>

                                <div class="col-6 d-none">
                                    <div class="form-group">
                                        <label>Address 3</label>
                                        <input type="text"
                                            class="form-control @error('address3') is-invalid @enderror" id="address3"
                                            name="address3" value="{{ $data->address3 }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Website' }}</label>
                                        <input type="url" class="form-control @error('website') is-invalid @enderror"
                                            id="website" name="website" value="{{ $data->website }}">
                                    </div>
                                </div>

                                <div class="col-3">
                                    <div class="form-group">
                                        <label>{{ 'Facebook' }}</label>
                                        <input type="url"
                                            class="form-control @error('facebook') is-invalid @enderror" id="facebook"
                                            name="facebook" value="{{ $data->facebook }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Instagram' }}</label>
                                        <input type="url"
                                            class="form-control @error('instagram') is-invalid @enderror" id="instagram"
                                            name="instagram" value="{{ $data->instagram }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Twitter' }}</label>
                                        <input type="url" class="form-control @error('twitter') is-invalid @enderror"
                                            id="twitter" name="twitter" value="{{ $data->twitter }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'LinkedIn' }}</label>
                                        <input type="url"
                                            class="form-control @error('linkedin') is-invalid @enderror" id="linkedin"
                                            name="linkedin" value="{{ $data->linkedin }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'YouTube' }}</label>
                                        <input type="url" class="form-control @error('youtube') is-invalid @enderror"
                                            id="youtube" name="youtube" value="{{ $data->youtube }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>TikTok</label>
                                        <input type="url" class="form-control @error('tiktok') is-invalid @enderror"
                                            id="tiktok" name="tiktok" value="{{ $data->tiktok }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Tawk.to' }}</label>
                                        <input type="url" class="form-control @error('tawkto') is-invalid @enderror"
                                            id="tawkto" name="tawkto" value="{{ $data->tawkto }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'App Store Link' }}</label>
                                        <input type="url"
                                            class="form-control @error('google_appstore_link') is-invalid @enderror"
                                            id="google_appstore_link" name="google_appstore_link"
                                            value="{{ $data->google_appstore_link }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Google Play Store Link' }}</label>
                                        <input type="url"
                                            class="form-control @error('google_play_link') is-invalid @enderror"
                                            id="google_play_link" name="google_play_link"
                                            value="{{ $data->google_play_link }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Footer Link' }}</label>
                                        <input type="url"
                                            class="form-control @error('footer_link') is-invalid @enderror"
                                            id="footer_link" name="footer_link" value="{{ $data->footer_link }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Currency' }}</label>
                                        <select class="form-control select2" id="currency" name="currency">
                                            <option value="" selected>{{ 'Please choose currency' }}</option>
                                            <option value="$" @if (!empty($data->currency) && $data->currency == '$') selected @endif>$
                                            </option>
                                            <option value="£" @if (!empty($data->currency) && $data->currency == '£') selected @endif>£
                                            </option>
                                            <option value="€" @if (!empty($data->currency) && $data->currency == '€') selected @endif>€
                                            </option>
                                            <option value="৳" @if (!empty($data->currency) && $data->currency == '৳') selected @endif>৳
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'VAT Percent' }}</label>
                                        <input type="number"
                                            class="form-control @error('vat_percent') is-invalid @enderror"
                                            id="vat_percent" name="vat_percent" value="{{ $data->vat_percent }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Company Registration Number' }}</label>
                                        <input type="text"
                                            class="form-control @error('company_reg_number') is-invalid @enderror"
                                            id="company_reg_number" name="company_reg_number"
                                            value="{{ $data->company_reg_number }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Bank' }}</label>
                                        <input type="text" class="form-control @error('bank') is-invalid @enderror"
                                            id="bank" name="bank" value="{{ $data->bank }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Account Number' }}</label>
                                        <input type="text"
                                            class="form-control @error('account_number') is-invalid @enderror"
                                            id="account_number" name="account_number"
                                            value="{{ $data->account_number }}">
                                    </div>
                                </div>

                                <div class="col-4 d-none">
                                    <div class="form-group">
                                        <label>{{ 'Sort Code' }}</label>
                                        <input type="text"
                                            class="form-control @error('sort_code') is-invalid @enderror" id="sort_code"
                                            name="sort_code" value="{{ $data->sort_code }}">
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'VAT Number' }}</label>
                                        <input type="text"
                                            class="form-control @error('vat_number') is-invalid @enderror"
                                            id="vat_number" name="vat_number" value="{{ $data->vat_number }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ 'Footer Content' }}</label>
                                        <textarea name="footer_content" id="footer_content"
                                            class="form-control @error('footer_content') is-invalid @enderror" cols="30" rows="3">{{ $data->footer_content }}</textarea>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ 'Google Map Source Code' }}</label>
                                        <textarea name="google_map" id="google_map" class="form-control @error('google_map') is-invalid @enderror"
                                            cols="30" rows="3">{{ $data->google_map }}</textarea>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Favicon' }}</label>
                                        <input type="file"
                                            class="form-control @error('fav_icon') is-invalid @enderror" id="fav_icon"
                                            name="fav_icon" onchange="previewImage(event, '#fav_icon_preview')"
                                            accept="image/*">

                                        @error('fav_icon')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror

                                        @if (!empty($data->fav_icon))
                                            <div class="mt-2 d-flex align-items-center gap-2">
                                                <img src="{{ asset('uploads/company/' . $data->fav_icon) }}"
                                                    alt="Current favicon" width="32" height="32"
                                                    class="img-thumbnail">
                                                <a href="{{ asset('uploads/company/' . $data->fav_icon) }}"
                                                    target="_blank" class="small">View current file</a>
                                                <div class="form-check ms-2">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="remove_fav_icon" name="remove_fav_icon" value="1">
                                                    <label class="form-check-label small" for="remove_fav_icon">Remove
                                                        current file</label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <img class="img-thumbnail mt-2" id="fav_icon_preview"
                                        src="{{ isset($data->fav_icon) ? asset('uploads/company/' . $data->fav_icon) : '' }}"
                                        alt="">
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Company Logo' }}</label>
                                        <input type="file"
                                            class="form-control @error('company_logo') is-invalid @enderror"
                                            id="company_logo" name="company_logo"
                                            onchange="previewImage(event, '#company_logo_preview')" accept="image/*">

                                        @error('company_logo')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror

                                        @if (!empty($data->company_logo))
                                            <div class="mt-2 d-flex align-items-center gap-2">
                                                <img src="{{ asset('uploads/company/' . $data->company_logo) }}"
                                                    alt="Current company logo" width="100" class="img-thumbnail">
                                                <a href="{{ asset('uploads/company/' . $data->company_logo) }}"
                                                    target="_blank" class="small">View current file</a>
                                                <div class="form-check ms-2">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="remove_company_logo" name="remove_company_logo"
                                                        value="1">
                                                    <label class="form-check-label small"
                                                        for="remove_company_logo">Remove current file</label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <img class="img-thumbnail mt-2" id="company_logo_preview"
                                        src="{{ isset($data->company_logo) ? asset('uploads/company/' . $data->company_logo) : '' }}"
                                        alt="">
                                </div>

                                <div class="col-4">
                                    <div class="form-group">
                                        <label>{{ 'Footer Logo' }}</label>
                                        <input type="file"
                                            class="form-control @error('footer_logo') is-invalid @enderror"
                                            id="footer_logo" name="footer_logo"
                                            onchange="previewImage(event, '#footer_logo_preview')" accept="image/*">

                                        @error('footer_logo')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror

                                        @if (!empty($data->footer_logo))
                                            <div class="mt-2 d-flex align-items-center gap-2">
                                                <img src="{{ asset('uploads/company/' . $data->footer_logo) }}"
                                                    alt="Current footer logo" width="100" class="img-thumbnail">
                                                <a href="{{ asset('uploads/company/' . $data->footer_logo) }}"
                                                    target="_blank" class="small">View current file</a>
                                                <div class="form-check ms-2">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="remove_footer_logo" name="remove_footer_logo"
                                                        value="1">
                                                    <label class="form-check-label small"
                                                        for="remove_footer_logo">Remove current file</label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <img class="img-thumbnail mt-2" id="footer_logo_preview"
                                        src="{{ isset($data->footer_logo) ? asset('uploads/company/' . $data->footer_logo) : '' }}"
                                        alt="">
                                </div>

                                {{-- SEO / Meta Section --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted border-bottom pb-1">SEO / Meta</h6>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Meta Title</label>
                                        <input type="text" class="form-control" name="meta_title" value="{{ $data->meta_title }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Meta Description</label>
                                        <textarea class="form-control" name="meta_description" rows="3">{{ $data->meta_description }}</textarea>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Meta Keywords <small class="text-muted">(comma separated)</small></label>
                                        <input type="text" class="form-control" name="meta_keywords" value="{{ $data->meta_keywords }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Meta Image</label>
                                        <input type="file" class="form-control" name="meta_image" accept="image/*"
                                            onchange="previewImage(event, '#meta_image_preview')">
                                        @if (!empty($data->meta_image))
                                            <div class="mt-2 d-flex align-items-center gap-2">
                                                <a href="{{ asset('uploads/company/' . $data->meta_image) }}"
                                                    target="_blank" class="small">View current file</a>
                                                <div class="form-check ms-2">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="remove_meta_image" name="remove_meta_image"
                                                        value="1">
                                                    <label class="form-check-label small"
                                                        for="remove_meta_image">Remove current file</label>
                                                </div>
                                            </div>
                                        @endif
                                        <img width="100" class="img-thumbnail mt-2" id="meta_image_preview"
                                            src="{{ $data->meta_image ? asset('uploads/company/' . $data->meta_image) : '' }}" alt="">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Google Site Verification</label>
                                        <input type="text" class="form-control" name="google_site_verification" value="{{ $data->google_site_verification }}">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Google Analytics ID</label>
                                        <input type="text" class="form-control" name="google_analytics_id" value="{{ $data->google_analytics_id }}" placeholder="G-XXXXXXXXXX">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Google Tag Manager ID</label>
                                        <input type="text" class="form-control" name="google_tag_manager_id"
                                            value="{{ $data->google_tag_manager_id ?? '' }}" placeholder="GTM-XXXXXXX">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Facebook Pixel ID</label>
                                        <input type="text" class="form-control" name="facebook_pixel_id"
                                            value="{{ $data->facebook_pixel_id ?? '' }}" placeholder="1234567890">
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" value="Update">{{ 'Update' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(function() {
            $('.select2').select2({
                width: '100%'
            });
        });
    </script>
@endsection
