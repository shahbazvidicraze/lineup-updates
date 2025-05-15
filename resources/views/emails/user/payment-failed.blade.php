<x-mail::message>
    # Payment Issue for Team "{{ $teamName }}"

    Hi {{ $userName }},

    We encountered an issue processing your payment of {{ $currency }}{{ $amountFormatted }} for team "{{ $teamName }}" on {{ $appName }}.

    Reason: {{ $failureMessage }}

    Please update your payment information or try again to ensure continued access to all features for your team.
    You can attempt to make the payment again through the app or website.

    If you believe this is an error or have any questions, please contact our support team.

    Thanks,
    The {{ $appName }} Team
</x-mail::message>
