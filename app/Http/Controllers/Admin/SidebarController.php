<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Route;

class SidebarController
{
    public static function getMenu()
    {
        $menu = [
            [
                'type' => 'item',
                'icon' => 'ri-dashboard-line',
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'href' => route('admin.dashboard'),
            ],
            [
                'type' => 'group',
                'icon' => 'ri-folder-line',
                'label' => 'Master Setup',
                'id' => 'sidebarMasterSetup',
                'children' => [
                    ['label' => 'Category', 'route' => 'allcategory', 'href' => route('allcategory')],
                    ['label' => 'Products', 'route' => 'products.*', 'href' => route('products.index')],
                ],
            ],
            [
                'type' => 'group',
                'icon' => 'ri-slideshow-line',
                'label' => 'Content',
                'id' => 'sidebarContent',
                'children' => [
                    ['label' => 'Sliders', 'route' => 'slider.index', 'href' => route('slider.index')],
                    ['label' => 'Testimonials', 'route' => 'testimonial.index', 'href' => route('testimonial.index')],
                    ['label' => 'FAQ Categories', 'route' => 'faq-categories.*', 'href' => route('faq-categories.index')],
                    ['label' => 'FAQs', 'route' => 'faqs.*', 'href' => route('faqs.index')],
                    ['label' => 'Gallery Categories', 'route' => 'gallery-categories.*', 'href' => route('gallery-categories.index')],
                    ['label' => 'Galleries', 'route' => 'galleries.*', 'href' => route('galleries.index')],
                ],
            ],
            [
                'type' => 'group',
                'icon' => 'ri-mail-line',
                'label' => 'Leads',
                'id' => 'sidebarLeads',
                'children' => [
                    ['label' => 'Contacts', 'route' => 'admin.contacts.*', 'href' => route('admin.contacts.index')],
                ],
            ],
            [
                'type' => 'group',
                'icon' => 'ri-settings-3-line',
                'label' => 'Settings',
                'id' => 'sidebarSettings',
                'children' => [
                    ['label' => 'Company Details', 'route' => 'admin.companyDetails', 'href' => route('admin.companyDetails')],
                    ['label' => 'Page SEO', 'route' => 'page-seo.*', 'href' => route('page-seo.index')],
                ],
            ],
        ];

        return self::markActive($menu);
    }

    private static function markActive($menu)
    {
        foreach ($menu as &$item) {
            if ($item['type'] === 'item') {
                $item['active'] = Route::is($item['route']);
            } elseif ($item['type'] === 'group') {
                $groupActive = false;
                foreach ($item['children'] as &$child) {
                    $child['active'] = Route::is($child['route']);
                    if ($child['active']) {
                        $groupActive = true;
                    }
                }
                $item['active'] = $groupActive;
            }
        }

        return $menu;
    }
}
