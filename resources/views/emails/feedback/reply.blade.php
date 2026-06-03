<x-mail::message>
# Halo {{ $feedback->user->name }},

Terima kasih telah menghubungi kami. Berikut adalah balasan dari tim terkait masukan Anda:

<x-mail::panel>
**Pesan Anda:**
{{ $feedback->message }}
</x-mail::panel>

**Balasan Kami:**
{{ $feedback->admin_reply }}

Terima kasih,
<br>
Tim MOCO
</x-mail::message>
