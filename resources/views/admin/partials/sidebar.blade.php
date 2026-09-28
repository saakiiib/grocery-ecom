<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt="" height="40">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt="" height="40">
            </span>
        </a>
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt="" height="40">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt="" height="40">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                @php
                    use App\Http\Controllers\Admin\SidebarController;
                    $menu = SidebarController::getMenu();
                @endphp

                @foreach($menu as $item)
                    @if($item['type'] === 'item')
                        <li class="nav-item">
                            <a href="{{ $item['href'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                                <i class="{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @elseif($item['type'] === 'group')
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $item['active'] ? 'active' : '' }}" href="#{{ $item['id'] }}" data-bs-toggle="collapse" role="button"
                                aria-expanded="{{ $item['active'] ? 'true' : 'false' }}" aria-controls="{{ $item['id'] }}">
                                <i class="{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                            <div class="collapse menu-dropdown {{ $item['active'] ? 'show' : '' }}" id="{{ $item['id'] }}">
                                <ul class="nav nav-sm flex-column">
                                    @foreach($item['children'] as $child)
                                        <li class="nav-item {{ in_array($child['label'], ['Sliders', 'Testimonials']) ? 'd-none' : '' }}">
                                            <a href="{{ $child['href'] }}" class="nav-link {{ $child['active'] ? 'active' : '' }}">
                                                {{ $child['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                @endforeach

                <li class="nav-item" style="margin-top: 250px;"></li>

            </ul>
        </div>
    </div>
</div>