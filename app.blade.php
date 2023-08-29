<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('public/css/app.css?v=1.0.0') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="{{ asset('public/assets/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/css/jquery-ui.min.css') }}">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-transparent">
            <div class="container">
                <a class="navbar-brand mr-md-5" href="{{ url('/') }}"><img src="{{ asset('public/assets/img/logo.svg') }}" height="40"></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse ml-md-5" id="navbar">
                    <ul class="navbar-nav space mr-auto">
                      <li class="nav-item active">
                        <a class="nav-link" href="{{ url('/') }}">{{ __('Home') }}</a>
                      </li>
                      @guest
                      <li class="nav-item">
                        <a class="nav-link" href="{{ url('/login') }}">{{ __('Buy') }}</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" href="{{ url('/coming-soon') }}">{{ __('Sell') }}</a>
                      </li>
                      @else
                      <li class="nav-item">
                        <a class="nav-link" href="{{ url('/coming-soon') }}">{{ __('Feed') }}</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" href="{{ url('/coming-soon') }}">{{ __('Favourites') }}</a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" href="{{ url('/coming-soon') }}">{{ __('Membership') }}</a>
                      </li>
                      @endguest
                      <li class="nav-item">
                        <a class="nav-link" href="{{ url('/about-us') }}">{{ __('About Us') }}</a>
                      </li>
                    </ul>
                    <ul class="navbar-nav ml-auto">
                        @guest
                        <li class="nav-item dropdown">
                            <a class="nav-link user-dropdown" href="#"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="{{ asset('public/assets/img/icons/menu-icon.svg') }}" width="17" class="ml-2">
                                <div class="avatar-xs ml-2">
                                    <div class="image">
                                        <img src="{{ asset('public/assets/img/user/default_user.png') }}">
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right default-dropdown size-14">
                                <a class="dropdown-item px-2" href="{{ url('login') }}"><img src="{{ asset('public/assets/img/icons/log.svg') }}" height="10" class="mr-1"> {{ __('Login') }}</a>
                                <a class="dropdown-item px-2" href="{{ url('register') }}"><img src="{{ asset('public/assets/img/icons/log.svg') }}" height="10" class="mr-1"> {{ __('Signup') }}</a>
                            </div>
                        </li>
                      @else
                      <li class="nav-item dropdown">
                        <a class="nav-link user-dropdown" href="#"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="{{ asset('public/assets/img/icons/menu-icon.svg') }}" width="17" class="ml-2">
                            <div class="avatar-xs ml-2">
                                <div class="image">
                                    <img src="{{ asset('public/assets/img/user/1.jpg') }}">
                                </div>
                            </div>
                            <span class="count">1</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right default-dropdown size-14">
                            <a class="dropdown-item px-3" href="{{ url('/coming-soon') }}"><img src="{{ asset('public/assets/img/icons/user-1.svg') }}" height="12" class="mr-2"> {{ __('My Profile') }}</a>
                            <a class="dropdown-item px-3" href="{{ url('/coming-soon') }}"><img src="{{ asset('public/assets/img/icons/calendar.svg') }}" height="12" class="mr-2"> {{ __('Events') }}</a>
                            <a class="dropdown-item px-3" href="{{ url('/coming-soon') }}"><img src="{{ asset('public/assets/img/icons/user-2.svg') }}" height="12" class="mr-2"> {{ __('Connections') }}</a>
                            <a class="dropdown-item px-3" href="{{ url('/coming-soon') }}"><img src="{{ asset('public/assets/img/icons/notification.svg') }}" height="12" class="mr-2"> {{ __('Notification') }}</a>
                            <a class="dropdown-item px-3" href="{{ url('/coming-soon') }}"><img src="{{ asset('public/assets/img/icons/chat.svg') }}" height="12" class="mr-2"> {{ __('Chat') }}</a>
                            <a class="dropdown-item px-3" href="{{ url('/coming-soon') }}"><img src="{{ asset('public/assets/img/icons/payment.svg') }}" height="12" class="mr-2"> {{ __('Payment History') }}</a>
                            <a class="dropdown-item px-3 mt-3" href="{{ route('logout') }}" onclick="event.preventDefault();
                            document.getElementById('logout-form').submit();"><img src="{{ asset('public/assets/img/icons/logout.svg') }}" height="12" class="mr-2"> {{ __('Log out') }}
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form></a>
                        </div>
                      </li>
                      @endguest
                    </ul>			
                </div>
            </div>
        </nav>

        <main>
            @yield('content')
        </main>
        <footer class="bg-light">
            <div class="container size-14">
                <div class="row py-4">
                    <div class="col-sm-3 text-center">
                        <div class="logo-footer mt-5 pt-2">
                            <img src="{{ asset('public/assets/img/footer-logo.jpg') }}">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="widget">
                            <div class="title font-md text-uppercase pt-3">ABOUT</div>
                            <ul class="list">
                                <li><a href="">How Real Rolty Works</a></li>
                                <li><a href="">Newsroom</a></li>
                                <li><a href="">Real Rolty 2021</a></li>
                                <li><a href="">Investors</a></li>
                                <li><a href="">How Real Rolty Works</a></li>
                                <li><a href="">Newsroom</a></li>
                                <li><a href="">Real Rolty 2021</a></li>
                                <li><a href="">Investors</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="widget">
                            <div class="title font-md text-uppercase pt-3">Community</div>
                            <ul class="list">
                                <li><a href="">Diversity & Belonging</a></li>
                                <li><a href="">Accessbility</a></li>
                                <li><a href="">Real Rolty Associates</a></li>
                                <li><a href="">Investors</a></li>
                                <li><a href="">How Real Rolty Works</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="widget">
                            <div class="title font-md text-uppercase pt-3">Support</div>
                            <ul class="list">
                                <li><a href="">Our COVID-19 Response</a></li>
                                <li><a href="">Help Centre</a></li>
                                <li><a href="">Cancellation options</a></li>
                                <li><a href="">Neighbourhood Support</a></li>
                                <li><a href="">Trust & Safety</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between border-top border-gray mt-5 py-3">
                    <div class="text-dl size-12">
                        2021 Real Rolty, Inc. Privacy Terms Sitemap Company details
                    </div>
                    <div class="text-dl size-12">
                        <select class="border-0 bg-transparent no-arrow">
                            <option value="">English (USA)</option>
                            <option value="">English (USA)</option>
                            <option value="">English (USA)</option>
                        </select>
                        <select class="border-0 bg-transparent no-arrow">
                            <option value="">$ USD</option>
                            <option value="">$ USD</option>
                            <option value="">$ USD</option>
                        </select>
                    </div>
                    <div class="social-links">
                        <a href="" class="text-dl ml-2"><i class="fa fa-facebook" aria-hidden="true"></i></a>
                        <a href="" class="text-dl ml-2"><i class="fa fa-instagram" aria-hidden="true"></i></a>
                        <a href="" class="text-dl ml-2"><i class="fa fa-twitter" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </footer>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="{{ asset('public/assets/js/jquery-ui.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
        <script src="https://unpkg.com/swiper@7/swiper-bundle.min.js"></script>
        <script src="{{ asset('public/assets/js/custom.js') }}"></script>
    </div>
</body>
</html>
