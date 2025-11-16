<!-- Nav items -->
<ul class="navbar-nav">
    <li class="nav-item">
        <a class="nav-link {{ Request::is('user/dashboard*') ? 'active' : '' }}"
            href="{{ route('user.dashboard.index') }}">
            <i class="fi fi-rs-dashboard"></i>
            <span class="nav-link-text">{{ __('Dashboard') }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Request::is('user/device*') ? 'active' : '' }}" href="{{ route('user.device.index') }}">
            <i class="fi-rs-sensor-on"></i>
            <span class="nav-link-text">{{ __('My Devices') }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Request::is('user/webhook*') ? 'active' : '' }}" href="{{ route('user.webhook.index') }}">
            <i class="fas fa-paper-plane"></i>
            <span class="nav-link-text">{{ __('My Webhook') }}</span>
        </a>
    </li>
    @if (in_array(Auth::user()->position, ['CEO', 'CSO']))
        <li class="nav-item">
            <a class="nav-link {{ Request::is('user/downline') || Request::is('user/downline/*') ? 'active' : '' }}"
                href="{{ route('user.downline.index') }}">
                <i class="fi-rs-users"></i>
                <span class="nav-link-text">{{ __('My Downlines') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('user/downline-device*') ? 'active' : '' }}"
                href="{{ route('user.downline-device.index') }}">
                <i class="fi-rs-devices"></i>
                <span class="nav-link-text">{{ __('Downline Devices') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('user/sent-text-message*') ? 'active' : '' }}"
                href="{{ url('user/sent-text-message') }}">
                <i class="fi fi-rs-paper-plane"></i>
                <span class="nav-link-text">{{ __('Single Send') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('user/chatbot*') ? 'active' : '' }}"
                href="{{ route('user.chatbot.index') }}">
                <i class="fas fa-robot"></i>
                <span class="nav-link-text">{{ __('Chatbot (Auto Reply)') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('user/apps*') ? 'active' : '' }}" href="{{ route('user.apps.index') }}">
                <i class="fi fi-rs-apps-add"></i>
                <span class="nav-link-text">{{ __('My Apps') }}</span>
            </a>
        </li>
        @if (Auth::user()->allow_broadcast == 1)
            <li class="nav-item">
                <a class="nav-link {{ Request::is('user/broadcast*') ? 'active' : '' }}"
                    href="{{ url('user/broadcast') }}">
                    <i class="fi fi-rs-megaphone"></i>
                    <span class="nav-link-text">{{ __('Broadcast') }}</span>
                </a>
            </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ Request::is('user/template*') ? 'active' : '' }}" href="{{ url('user/template') }}">
                <i class="fi  fi-rs-template-alt"></i>
                <span class="nav-link-text">{{ __('My Templates') }}</span>
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link {{ Request::is('user/contact*') ? 'active' : '' }}"
            href="{{ route('user.contact.index') }}">
            <i class="fi  fi-rs-address-book"></i>
            <span class="nav-link-text">{{ __('Contacts Book') }}</span>
        </a>
    </li>
    <li class="nav-item ">
        <a class="nav-link {{ Request::is('user/logs*') ? 'active' : '' }}" href="{{ url('user/logs') }}">
            <i class="ni ni-ui-04"></i>
            <span class="nav-link-text">{{ __('Message Log') }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Request::is('user/report*') ? 'active' : '' }}" href="{{ url('user/report') }}">
            <i class="fi fi-rs-file-spreadsheet"></i>
            <span class="nav-link-text">{{ __('Report') }}</span>
        </a>
    </li>
</ul>
