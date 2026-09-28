<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;

class CompanyDetailsController extends Controller
{
    public function index()
    {
        $data = CompanyDetails::cached();

        return view('admin.company.index', compact('data'));
    }

    public function update(Request $request)
    {
        $data = CompanyDetails::first();

        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_name' => 'nullable|max:255',
            'opening_cash_balance' => 'nullable|numeric|min:0',
            'email1' => 'nullable|email|max:255',
            'email2' => 'nullable|email|max:255',
            'phone1' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'phone3' => 'nullable|string|max:20',
            'phone4' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'address3' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'tawkto' => 'nullable|string|max:255',
            'vat_percent' => 'nullable|string|max:255',
            'google_appstore_link' => 'nullable|string|max:255',
            'google_play_link' => 'nullable|string|max:255',
            'footer_link' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'google_map' => 'nullable|string',
            'company_reg_number' => 'nullable|string',
            'vat_number' => 'nullable|string',
            'fav_icon' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'company_logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'footer_logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'meta_image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'google_site_verification' => 'nullable|string|max:255',
            'google_analytics_id' => 'nullable|string|max:255',
            'google_tag_manager_id' => 'nullable|string|max:255',
            'facebook_pixel_id' => 'nullable|string|max:255',
        ], [
            'company_name.required' => 'Company name is required',
            'email1.email' => 'Enter a valid email',
            'email2.email' => 'Enter a valid email',
            'opening_cash_balance.numeric' => 'Opening cash balance must be numeric',
            'opening_cash_balance.min' => 'Opening cash balance must be positive',
            'fav_icon.mimes' => 'Favicon must be an image',
            'fav_icon.max' => 'File size must not exceed maximum limit',
            'company_logo.mimes' => 'Company logo must be an image',
            'company_logo.max' => 'File size must not exceed maximum limit',
            'footer_logo.mimes' => 'Footer logo must be an image',
            'footer_logo.max' => 'File size must not exceed maximum limit',
        ]);

        if ($request->hasFile('fav_icon')) {
            if ($data->fav_icon && file_exists(public_path('uploads/company/'.$data->fav_icon))) {
                unlink(public_path('uploads/company/'.$data->fav_icon));
            }
            $favIconName = rand(100000, 999999).'_fav_icon.'.$request->fav_icon->extension();
            $request->fav_icon->move(public_path('uploads/company'), $favIconName);
            $data->fav_icon = $favIconName;
        } elseif ($request->boolean('remove_fav_icon')) {
            if ($data->fav_icon && file_exists(public_path('uploads/company/'.$data->fav_icon))) {
                unlink(public_path('uploads/company/'.$data->fav_icon));
            }
            $data->fav_icon = null;
        }

        if ($request->hasFile('company_logo')) {
            if ($data->company_logo && file_exists(public_path('uploads/company/'.$data->company_logo))) {
                unlink(public_path('uploads/company/'.$data->company_logo));
            }
            $companyLogoName = rand(100000, 999999).'_company_logo.'.$request->company_logo->extension();
            $request->company_logo->move(public_path('uploads/company'), $companyLogoName);
            $data->company_logo = $companyLogoName;
        } elseif ($request->boolean('remove_company_logo')) {
            if ($data->company_logo && file_exists(public_path('uploads/company/'.$data->company_logo))) {
                unlink(public_path('uploads/company/'.$data->company_logo));
            }
            $data->company_logo = null;
        }

        if ($request->hasFile('footer_logo')) {
            if ($data->footer_logo && file_exists(public_path('uploads/company/'.$data->footer_logo))) {
                unlink(public_path('uploads/company/'.$data->footer_logo));
            }
            $footerLogoName = rand(100000, 999999).'_footer_logo.'.$request->footer_logo->extension();
            $request->footer_logo->move(public_path('uploads/company'), $footerLogoName);
            $data->footer_logo = $footerLogoName;
        } elseif ($request->boolean('remove_footer_logo')) {
            if ($data->footer_logo && file_exists(public_path('uploads/company/'.$data->footer_logo))) {
                unlink(public_path('uploads/company/'.$data->footer_logo));
            }
            $data->footer_logo = null;
        }

        $data->company_name = $request->company_name;
        $data->business_name = $request->business_name;
        $data->opening_cash_balance = $request->opening_cash_balance;
        $data->email1 = $request->email1;
        $data->email2 = $request->email2;
        $data->phone1 = $request->phone1;
        $data->phone2 = $request->phone2;
        $data->phone3 = $request->phone3;
        $data->phone4 = $request->phone4;
        $data->whatsapp = $request->whatsapp;
        $data->address1 = $request->address1;
        $data->address2 = $request->address2;
        $data->address3 = $request->address3;
        $data->website = $request->website;
        $data->facebook = $request->facebook;
        $data->instagram = $request->instagram;
        $data->twitter = $request->twitter;
        $data->linkedin = $request->linkedin;
        $data->youtube = $request->youtube;
        $data->tiktok = $request->tiktok;
        $data->tawkto = $request->tawkto;
        $data->vat_percent = $request->vat_percent;
        $data->google_appstore_link = $request->google_appstore_link;
        $data->google_play_link = $request->google_play_link;
        $data->footer_link = $request->footer_link;
        $data->currency = $request->currency;
        $data->footer_content = $request->footer_content;
        $data->google_map = $request->google_map;
        $data->company_reg_number = $request->company_reg_number;
        $data->vat_number = $request->vat_number;
        $data->opening_time = $request->opening_time;
        $data->account_number = $request->account_number;
        $data->sort_code = $request->sort_code;
        $data->bank = $request->bank;

        // SEO & Analytics fields
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->meta_keywords = $request->meta_keywords;
        $data->google_site_verification = $request->google_site_verification;
        $data->google_analytics_id = $request->google_analytics_id;
        $data->google_tag_manager_id = $request->google_tag_manager_id;
        $data->facebook_pixel_id = $request->facebook_pixel_id;

        // Handle meta image upload
        if ($request->hasFile('meta_image')) {
            $path = public_path('uploads/company/');
            if (! file_exists($path)) {
                mkdir($path, 0755, true);
            }

            if ($data->meta_image && file_exists($path.$data->meta_image)) {
                unlink($path.$data->meta_image);
            }

            $metaImageName = 'meta_'.time().'.webp';
            Image::make($request->file('meta_image'))
                ->resize(1200, 630, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('webp', 80)
                ->save($path.$metaImageName);

            $data->meta_image = $metaImageName;
        } elseif ($request->boolean('remove_meta_image')) {
            $path = public_path('uploads/company/');
            if ($data->meta_image && file_exists($path.$data->meta_image)) {
                unlink($path.$data->meta_image);
            }
            $data->meta_image = null;
        }

        $data->save();

        return redirect()->back()->with('success', 'Company details updated successfully');
    }

    public function aboutUs()
    {
        $companyDetails = CompanyDetails::select('about_us')->first();

        return view('admin.company.about_us', compact('companyDetails'));
    }

    public function aboutUsUpdate(Request $request)
    {
        $request->validate([
            'about_us' => 'required|string',
        ]);

        $companyDetails = CompanyDetails::first();
        $companyDetails->about_us = $request->about_us;
        $companyDetails->save();

        return redirect()->back()->with('success', 'About us updated successfully.');
    }

    public function privacyPolicy()
    {
        $companyDetails = CompanyDetails::select('privacy_policy')->first();

        return view('admin.company.privacy', compact('companyDetails'));
    }

    public function privacyPolicyUpdate(Request $request)
    {
        $request->validate([
            'privacy_policy' => 'required|string',
        ]);

        $companyDetails = CompanyDetails::first();
        $companyDetails->privacy_policy = $request->privacy_policy;
        $companyDetails->save();

        Cache::forget('company_privacy');

        return redirect()->back()->with('success', 'Privacy policy updated successfully.');
    }

    public function termsAndConditions()
    {
        $companyDetails = CompanyDetails::select('terms_and_conditions')->first();

        return view('admin.company.terms', compact('companyDetails'));
    }

    public function termsAndConditionsUpdate(Request $request)
    {
        $request->validate([
            'terms_and_conditions' => 'required|string',
        ]);

        $companyDetails = CompanyDetails::first();
        $companyDetails->terms_and_conditions = $request->terms_and_conditions;
        $companyDetails->save();

        Cache::forget('company_terms');

        return redirect()->back()->with('success', 'Terms and conditions updated successfully.');
    }

    public function mailBody()
    {
        $companyDetails = CompanyDetails::select('mail_body')->first();

        return view('admin.company.mail_body', compact('companyDetails'));
    }

    public function mailBodyUpdate(Request $request)
    {
        $request->validate([
            'mail_body' => 'required',
        ]);

        $companyDetails = CompanyDetails::first();
        $companyDetails->mail_body = $request->mail_body;
        $companyDetails->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function homeFooter()
    {
        $companyDetails = CompanyDetails::select('footer_content')->first();

        return view('admin.company.home_footer', compact('companyDetails'));
    }

    public function homeFooterUpdate(Request $request)
    {
        $request->validate([
            'footer_content' => 'required',
        ]);

        $companyDetails = CompanyDetails::first();
        $companyDetails->footer_content = $request->footer_content;
        $companyDetails->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function copyright()
    {
        $companyDetails = CompanyDetails::select('copyright')->first();

        return view('admin.company.copyright', compact('companyDetails'));
    }

    public function copyrightUpdate(Request $request)
    {
        $request->validate([
            'copyright' => 'required',
        ]);

        $companyDetails = CompanyDetails::first();
        $companyDetails->copyright = $request->copyright;
        $companyDetails->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function seoMeta()
    {
        $companyDetails = CompanyDetails::first();

        return view('admin.company.seo-meta', compact('companyDetails'));
    }

    public function seoMetaUpdate(Request $request)
    {
        $request->validate([
            'google_site_verification' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $companyDetails = CompanyDetails::first();
        if (! $companyDetails) {
            $companyDetails = new CompanyDetails;
        }

        $companyDetails->google_site_verification = $request->google_site_verification;
        $companyDetails->meta_title = $request->meta_title;
        $companyDetails->meta_description = $request->meta_description;
        $companyDetails->meta_keywords = $request->meta_keywords;

        // Handle meta image upload
        if ($request->hasFile('meta_image')) {
            $metaImage = $request->file('meta_image');
            $metaImageName = 'meta_'.time().'.webp';
            $path = public_path('uploads/company/meta/');

            // Ensure directory exists
            if (! file_exists($path)) {
                mkdir($path, 0755, true);
            }

            // Only delete old image if it's not the placeholder
            if ($companyDetails->meta_image && $companyDetails->meta_image !== 'placeholder.webp' && file_exists($path.$companyDetails->meta_image)) {
                unlink($path.$companyDetails->meta_image);
            }

            // Process and save new image
            Image::make($metaImage)
                ->resize(1200, 630, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('webp', 80)
                ->save($path.$metaImageName);

            $companyDetails->meta_image = $metaImageName;
        }

        $companyDetails->save();

        return redirect()->back()->with('success', 'SEO Meta fields updated successfully.');
    }
}
