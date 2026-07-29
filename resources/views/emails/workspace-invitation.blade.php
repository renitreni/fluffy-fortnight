<x-mail::message>
# You have been invited to join a workspace

You have been invited to join the **{{ $invitation->workspace->name }}** workspace on {{ config('app.name') }}.

Please click the button below to accept the invitation. If you do not have an account, you will be prompted to create one first.

<x-mail::button :url="route('invitations.accept', $invitation->token)">
Accept Invitation
</x-mail::button>

If you did not expect this invitation, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
