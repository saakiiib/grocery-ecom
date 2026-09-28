@extends('admin.pages.master')
@section('title', 'Dashboard')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Welcome</h4>
                        <p class="text-muted mb-0">Here's a quick overview of your site.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm bg-primary-subtle text-primary rounded d-inline-flex align-items-center justify-content-center">
                            <x-icon name="package" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-muted text-uppercase fw-semibold fs-12 mb-1">Products</p>
                            <h4 class="mb-0">{{ $productCount ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm bg-success-subtle text-success rounded d-inline-flex align-items-center justify-content-center">
                            <x-icon name="layout" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-muted text-uppercase fw-semibold fs-12 mb-1">Categories</p>
                            <h4 class="mb-0">{{ $categoryCount ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm bg-info-subtle text-info rounded d-inline-flex align-items-center justify-content-center">
                            <x-icon name="phone" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-muted text-uppercase fw-semibold fs-12 mb-1">Contacts</p>
                            <h4 class="mb-0">{{ $contactCount ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm bg-secondary-subtle text-secondary rounded d-inline-flex align-items-center justify-content-center">
                            <x-icon name="image" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-muted text-uppercase fw-semibold fs-12 mb-1">Gallery Items</p>
                            <h4 class="mb-0">{{ $galleryCount ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm bg-warning-subtle text-warning rounded d-inline-flex align-items-center justify-content-center">
                            <x-icon name="message-circle" class="w-5 h-5" />
                        </span>
                        <div>
                            <p class="text-muted text-uppercase fw-semibold fs-12 mb-1">Contacts (7 days)</p>
                            <h4 class="mb-0">{{ $contactsThisWeek ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Recent Contacts</h5>
                    </div>
                    <div class="card-body">
                        @if (($recentContacts ?? collect())->isEmpty())
                            <p class="text-muted mb-0">No contacts yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentContacts as $contact)
                                            <tr>
                                                <td>{{ $contact->name }}</td>
                                                <td>{{ $contact->email }}</td>
                                                <td>{{ $contact->subject ?? '—' }}</td>
                                                <td>{{ $contact->created_at?->format('d M Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Products by Category</h5>
                    </div>
                    <div class="card-body">
                        @if (($productsByCategory ?? collect())->isEmpty())
                            <p class="text-muted mb-0">No categories yet.</p>
                        @else
                            @foreach ($productsByCategory as $category)
                                @php($maxCount = max(1, $productsByCategory->max('products_count')))
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium">{{ $category->name }}</span>
                                        <span class="text-muted">{{ $category->products_count }}</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: {{ round($category->products_count / $maxCount * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
