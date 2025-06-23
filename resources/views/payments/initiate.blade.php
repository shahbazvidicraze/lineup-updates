<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Purchase Your Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://js.stripe.com/v3/"></script>
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
            padding: 2rem;
            background-size: cover;
            background-position: center;
        }
        .left-panel {
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            user-select: none;
        }
        .left-panel > h2 > span {
            font-size: 2.8em;
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
        <a class="navbar-brand" href="#">
            <img src="{{ asset('/web/assets/assets/images/line_up_hero_header.png') }}" alt="Lineup Hero Logo" class="header-logo">
        </a>

        <!-- Navbar Links (right side) - Always Visible -->
        <div class="navbar-collapse d-flex justify-content-end">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item user-profile">
                    <div class="d-flex gap-2 align-items-center">
                        <img src="{{ asset('/web/assets/assets/images/dummy_image.png') }}" alt="Avatar Logo" style="width:40px;" class="bg-blue rounded-pill ">
                        <div class="d-flex flex-column m-0 p-0 justify-content-center">
                            <h6 style="line-height: 0.6" class="mt-1 mb-0 p-0 text-maroon">Welcome <span style="font-weight: 200 !important;">&#128075;</span></h6>
                            <p class="m-0 p-0">{{ $team->user->first_name }}</p>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-container container">
    <!-- Left Panel -->
    <div class="left-panel text-center">
        <h2>PURCHASE YOUR <span class="fw-bold">TEAM</span></h2>
        <h4 class="mb-2 text-secondary">{{ $team->name }}</h4>
        <div class="price-text my-5">${{ number_format($amount / 100, 2) }} {{ strtoupper($currency) }}</div>
        <div class="border card rounded p-3 text-start">
            <div class="d-flex justify-content-between mb-2">
                <span>Platform basic</span>
                <strong>${{ number_format($amount / 100, 2) }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <strong>${{ number_format($amount / 100, 2) }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Tax</span>
                <strong>$0.00</strong>
            </div>
            <hr />
            <div class="d-flex justify-content-between">
                <strong>Total due today</strong>
                <strong>${{ number_format($amount / 100, 2) }}</strong>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="right-panel w-100" style="max-width: 500px;">
        <form id="payment-form">
{{--            <h5 class="text-danger fw-bold mb-3">CONTACT INFORMATION</h5>--}}
{{--            <div class="mb-3">--}}
{{--                <input type="email" class="form-control" placeholder="Email" required />--}}
{{--            </div>--}}

            <h5 class="text-maroon fw-bold mt-4 mb-3">PAYMENT METHOD</h5>
            <div class="mb-3">
                <!-- Stripe Payment Element mounts here -->
                <div id="payment-element" class="form-control p-3"></div>
            </div>

            <div id="error-message" class="text-danger mb-3"></div>

            <button id="submit" class="btn pay-btn w-100" type="submit">
                <span id="button-text">Pay Now</span>
                <span id="spinner" style="display: none;">Processing...</span>
            </button>
        </form>
    </div>
</div>

<script>
    const stripeKey = "{{ $stripeKey ?? '' }}";         // Replace with test/public key directly for testing if needed
    const clientSecret = "{{ $clientSecret ?? '' }}";   // Inject this from your server
    const returnUrl = "{{ $returnUrl ?? '' }}";         // e.g., route('checkout.success')

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit');
    const errorMessage = document.getElementById('error-message');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    if (!stripeKey || !clientSecret || !returnUrl) {
        errorMessage.textContent = 'Payment configuration missing. Cannot proceed.';
        if (submitButton) submitButton.disabled = true;
    } else {
        try {
            const stripe = Stripe(stripeKey);
            const elements = stripe.elements({ clientSecret });

            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                setLoading(true);
                errorMessage.textContent = '';

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: returnUrl,
                    },
                });

                if (error) {
                    errorMessage.textContent = error.message;
                    setLoading(false);
                } else {
                    buttonText.textContent = 'Redirecting...';
                }
            });
        } catch (e) {
            console.error('Stripe initialization failed:', e);
            errorMessage.textContent = 'Could not initialize payment form. ' + e.message;
            if (submitButton) submitButton.disabled = true;
        }
    }

    function setLoading(isLoading) {
        if (!submitButton) return;
        submitButton.disabled = isLoading;
        spinner.style.display = isLoading ? 'inline' : 'none';
        buttonText.style.display = isLoading ? 'none' : 'inline';
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
