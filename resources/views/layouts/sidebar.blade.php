<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a class="app-brand-link">

            <span class="app-brand-text demo menu-text fw-bold"><img src="/assets/img/present_logo.png" alt=""
                   ></span>
        </a>

        {{-- <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
            <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
        </a> --}}
    </div>
    <div class="brandborder">

    </div>

    {{-- <div class="menu-inner-shadow"></div> --}}


    

    <ul class="menu-inner py-1">
        <!-- Dashboards -->




        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Dashboard</span>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-') ? 'active' : '' }}">
            <a href="{{ route('dashboard-') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="Statistics">Statistics</div>
            </a>
        </li>


        <!-- Apps & Pages -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">User Managements</span>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-users') ? 'active' : '' }}">
            <a href="{{ route('dashboard-users') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="User">Users</div>
            </a>
        </li>

        <li class="menu-item {{ Request::url() == route('dashboard-verify-users') ? 'active' : '' }}">
            <a href="{{ route('dashboard-verify-users') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="User">Verifications Requests</div>
                @if ($verifyCount != 0)

                    <div class="badge bg-danger rounded-pill ms-auto">{{ $verifyCount }}</div>
                @endif
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text"> Reported Requests</span>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-report-' , 'user') ? 'active' : '' }}">
            <a href="{{ route('dashboard-report-' ,'user') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="User">Reported Users</div>
                @if ($reportedUser )
                    <div class="badge bg-danger rounded-pill ms-auto">{{ $reportedUser }}</div>

                @endif

            </a>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-report-' , 'reels') ? 'active' : '' }}">
            <a href="{{ route('dashboard-report-' , 'reels') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="User">Reported Reels</div>
                @if ($reportedReel != 0)
                    <div class="badge bg-danger rounded-pill ms-auto">{{ $reportedReel }}</div>
                @endif
            </a>
        </li>

        <li class="menu-item {{ Request::url() == route('dashboard-report-' , 'posts') ? 'active' : '' }}">
            <a href="{{ route('dashboard-report-' , 'posts') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="User">Reported Posts</div>
                @if ($reportedPost != 0)
                    <div class="badge bg-danger rounded-pill ms-auto">{{ $reportedPost }}</div>
                @endif
            </a>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-report-' , 'stories') ? 'active' : '' }}">
            <a href="{{ route('dashboard-report-' , 'stories') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="User">Reported Stories</div>
                @if ($reportedStory != 0)
                    <div class="badge bg-danger rounded-pill ms-auto">{{ $reportedStory }}</div>
                @endif
            </a>
        </li>



        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Help & Supports</span>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-ticket-ticket', 'active') ? 'active' : '' }}">
            <a href="{{ route('dashboard-ticket-ticket', 'active') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div> Active Tickets </div>
                @if ($activeTicket != 0)
                    <div class="badge bg-danger rounded-pill ms-auto">{{ $activeTicket }}</div>
                @endif
            </a>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-ticket-ticket', 'close') ? 'active' : '' }} ">
            <a href="{{ route('dashboard-ticket-ticket', 'close') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>

                <div>Closed Tickets</div>
            </a>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-ticket-category') ? 'active' : '' }}">
            <a href="{{ route('dashboard-ticket-category') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>

                <div>Ticket Categories</div>
            </a>
        </li>
        <li class="menu-item {{ Request::url() == route('dashboard-faqs') ? 'active' : '' }}">
            <a href="{{ route('dashboard-faqs') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-circle"></i>
                <div data-i18n="FAQ'S">Faq's</div>
            </a>
        </li>


    </ul>
</aside>
