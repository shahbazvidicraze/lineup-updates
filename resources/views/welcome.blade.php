<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f9f9f9;
            font-family: 'Segoe UI', sans-serif;
            --bs-gutter-x: 0 !important;
        }

        .header-logo{
            height: 70px;
            user-select: none;
        }

        .user-profile{
            user-select: none;
        }
        .main-container {
            margin-top: 70px;
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            --bs-gutter-x: 0;
            width: 100%;
            background-size: cover;
            background-position: center;
        }
        .left-panel {
            padding: 2rem;
            border-radius: 12px;
            max-width: 480px;
            user-select: none;
        }
        .left-panel > h2 > span {
            font-size: 1.8em;
            line-height: 0.85 !important;
            color: #054b8e;
        }

        .bg-blue{
            background-color: #054b8e;
        }
        .right-panel {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .price-text {
            font-size: 2rem;
            color: #7e1a1a;
            font-weight: 700;
        }

        .text-maroon{
            color: #7e1a1a !important;
        }
        .pay-btn {
            background-color: #7e1a1a;
            color: #fff;
        }

        .hero-section {
            height: 500px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to right, #2c3e50, #2e6d89);
            color: white;
            text-align: center;
            flex-direction: column;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .hero-section .btn {
            font-weight: bold;
            padding: 10px 30px;
            margin: 0 2px;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                gap: 1.5rem;
            }

            .user-profile{
                scale: 0.88;
            }

            .header-logo{
                height: 58px;
            }
        }
    </style>
</head>
<body>
<!-- Absolute Navbar -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm position-absolute top-0 start-0 w-100 z-3" style="height: 70px !important;">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ config('app.url') }}">
            <img src="{{ asset('/web/assets/assets/images/line_up_hero_header.png') }}" alt="Lineup Hero Logo" class="header-logo">
        </a>

        <!-- Navbar Links (right side) - Always Visible -->
        <div class="navbar-collapse d-flex justify-content-end">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item user-profile">
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ config('app.url') }}web/#/sign-in-screen" class="btn btn-dark py-2 px-3"><i class="fa fa-circle-user pe-3"></i>Log In</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-container container-fluid">
    <div class="hero-section">
        <h1>Game-Ready Lineup</h1>
        <div>
            <a href="{{ config('app.url') }}web/#/minified:vD" class="btn btn-light">Sign Up</a>
            <a href="{{ config('app.url') }}web/#/sign-in-screen" class="btn btn-light">Log In</a>
        </div>
        <div class="mt-3">
            <a href="#" class="btn px-4 fw-light btn-outline-light fs-5">Login as Organization</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
