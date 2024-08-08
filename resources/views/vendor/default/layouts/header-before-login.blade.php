<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro&display=swap" rel="stylesheet">
  <link rel="stylesheet" type="text/css"
    href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

  <link rel="shortcut icon" href="{{ asset('assets/default/images/favicon.ico') }}" type="image/x-icon">
  <link rel="stylesheet" href="{{ asset('assets/default/css/style.css') }}">
  <title>Home Page - RoyoConsultant</title>
</head>

<body>
  <!-- Header section -->
  <header class="fixed-top">
    <nav class="navbar navbar-expand-lg navbar-light container">
      <a class="navbar-brand" href="#">
          <img src="{{ asset('assets/default/images/logo_header.png') }}">     
        </a>
      @if(Auth::check())  
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="menu-bar"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
              id="hamburger">
              <path
                d="M22 17a1 1 0 1 1 0 2H2a1 1 0 1 1 0-2zm0-6a1 1 0 1 1 0 2H2a1 1 0 1 1 0-2zm0-6a1 1 0 1 1 0 2H2a1 1 0 0 1 0-2z"
                fill-rule="evenodd"></path>
            </svg></span>
      </button>


      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav top-menue1 ml-auto">
          <li class="nav-item dmenu">
            <a class="nav-link" href="#" id="navbardrop">
              Appointments
            </a>
          </li>
          <li class="nav-item dmenu">
            <a class="nav-link" href="#" id="navbardrop">
              Chats
            </a>
          </li>
          <li class="nav-item dmenu ">
            <a class="nav-link" href="#" id="navbardrop">
                <img src="{{ asset('assets/default/images/ic_wallet.png') }}"> <i class="fa fa-inr ml-1 mr-1" aria-hidden="true"></i> 2239.00   
            </a>
          </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <img src="{{ asset('assets/default/images/profile_photo.png') }}">
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <a class="dropdown-item" href="#"><img src="{{ asset('assets/default/images/ic_account.png') }}"> Account</a>
                  <a class="dropdown-item" href="#"><img src="{{ asset('assets/default/images/ic_notification.png') }}"> Notifications</a>
                  <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><img src="{{ asset('assets/default/images/ic_logout.png') }}"> Logout</a>
                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                </div>
            </li>
        </ul>
        
        @else
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link link-btn" href="#" data-toggle="modal" data-target="#signup">Sign up</a>
          </li>
          <li class="nav-item">
            <a class="nav-link link-btn" href="#" data-toggle="modal" data-target="#login">Log in</a>
          </li>
        </ul>
        @endif
      </div>
    </nav>
  </header>