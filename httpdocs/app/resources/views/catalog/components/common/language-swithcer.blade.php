@php
    $current_route = request()->route()->getName();
    $route_params = request()->route()->parameters();
@endphp

<div class="dropdown-lang-menu relative inline-flex">
    <button type="button" class="dropdown-toggle dropdown-lang-menu-btn btn btn-primary"
            id="dropdown-lang-menu-btn"
            aria-haspopup="menu"
            aria-expanded="false"
            aria-label="Dropdown">
        Dropdown

        <svg class="icon-size dropdown-open:rotate-180"
            width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16.3543 10.3533L12.3543 14.3533C12.2563 14.4513 12.1283 14.4993 12.0003 14.4993C11.8723 14.4993 11.7442 14.4503 11.6462 14.3533L7.64625 10.3533C7.45125 10.1583 7.45125 9.84125 7.64625 9.64625C7.84125 9.45125 8.15828 9.45125 8.35328 9.64625L11.9993 13.2922L15.6453 9.64625C15.8403 9.45125 16.1573 9.45125 16.3523 9.64625C16.5473 9.84125 16.5493 10.1573 16.3543 10.3533Z"
                  fill="currentColor"/>
        </svg>
    </button>
    <ul class="dropdown-menu dropdown-open:opacity-100 hidden min-w-60" role="menu" aria-orientation="vertical" aria-labelledby="dropdown-lang-menu-btn">
        <li><a class="dropdown-item" href="#">My Profile</a></li>
        <li><a class="dropdown-item" href="#">Settings</a></li>
        <li><a class="dropdown-item" href="#">Billing</a></li>
        <li><a class="dropdown-item" href="#">FAQs</a></li>
    </ul>
</div>
