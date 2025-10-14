You're Invited to {{ $appName }}!

Hello there!

{{ $inviterName }} has invited you to join {{ $appName }} as a {{ $roleDisplay }}.

Your Email: {{ $email }}
Assigned Role: {{ $roleDisplay }}
Invited By: {{ $inviterName }}

To get started, please click the link below to complete your registration:
{{ $url }}

Important Information:
- This invitation expires {{ $expiresIn }} ({{ $expiresAt }})
- The invitation link can only be used once for security
- You'll have {{ $roleDisplay }} access privileges
- After registration, you'll be automatically logged in

Security Notice:
This invitation was sent to {{ $email }}. If you did not expect this invitation, please disregard this email.

---
{{ $appName }}
© {{ date('Y') }} All rights reserved.
