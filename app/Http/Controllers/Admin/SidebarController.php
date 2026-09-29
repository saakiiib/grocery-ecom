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
                'type' => 'item',
                'icon' => 'ri-shopping-basket-line',
                'label' => 'Products',
                'route' => 'products.*',
                'href' => route('products.index'),
            ],
            [
                'type' => 'item',
                'icon' => 'ri-folder-line',
                'label' => 'Category',
                'route' => '*category*',
                'href' => route('allcategory'),
            ],
            [
                'type' => 'item',
                'icon' => 'ri-list-check',
                'label' => 'Option Groups',
                'route' => 'option-groups.*',
                'href' => route('option-groups.index'),
            ],
            [
                'type' => 'group',
                'icon' => 'ri-slideshow-line',
                'label' => 'Content',
                'id' => 'sidebarContent',
                'children' => [
                    ['label' => 'Sliders', 'route' => 'slider.*', 'href' => route('slider.index')],
                    ['label' => 'Testimonials', 'route' => 'testimonial.index', 'href' => route('testimonial.index')],
                    ['label' => 'FAQ Categories', 'route' => 'faq-categories.*', 'href' => route('faq-categories.index')],
                    ['label' => 'FAQs', 'route' => 'faqs.*', 'href' => route('faqs.index')],
                    ['label' => 'Gallery Categories', 'route' => 'gallery-categories.*', 'href' => route('gallery-categories.index')],
                    ['label' => 'Galleries', 'route' => 'galleries.*', 'href' => route('galleries.index')],
                ],
            ],
            [
                'type' => 'item',
                'icon' => 'ri-shopping-bag-line',
                'label' => 'Orders',
                'route' => 'orders.*',
                'href' => route('orders.index'),
            ],
            [
                'type' => 'item',
                'icon' => 'ri-time-line',
                'label' => 'Delivery Slots',
                'route' => 'delivery-slots.*',
                'href' => route('delivery-slots.index'),
            ],
            [
                'type' => 'item',
                'icon' => 'ri-mail-line',
                'label' => 'Contacts',
                'route' => 'admin.contacts.*',
                'href' => route('admin.contacts.index'),
            ],
            [
                'type' => 'group',
                'icon' => 'ri-settings-3-line',
                'label' => 'Settings',
                'id' => 'sidebarSettings',
                'children' => [
                    ['label' => 'Company Details', 'route' => 'admin.companyDetails', 'href' => route('admin.companyDetails')],
                    ['label' => 'Shop Settings', 'route' => 'shop-settings.*', 'href' => route('shop-settings.edit')],
                    ['label' => 'Order Statuses', 'route' => 'order-statuses.*', 'href' => route('order-statuses.index')],
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
