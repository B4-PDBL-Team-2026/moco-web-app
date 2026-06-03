<x-mail::message>
# Halo {{ $feedback->user->name }},

Terima kasih atas masukan yang telah Anda berikan kepada MOCO.

Kami telah menerima masukan Anda dan tim kami akan segera meninjaunya. Feedback dari Anda sangat berarti bagi kami untuk terus meningkatkan kualitas layanan MOCO.

**Kategori:** {{ $feedback->category }}<br>
**Platform:** {{ $feedback->platform }}

<x-mail::panel>
**Pesan Anda:**
{{ $feedback->message }}
</x-mail::panel>

Salam hangat,
<br>
Tim MOCO
</x-mail::message>
