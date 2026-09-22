@php
    $contactLabels = [
        'phone' => 'Llámanos',
        'whatsapp' => 'WhatsApp',
        'email' => 'Correo',
        'address' => 'Visítanos',
    ];
@endphp
<div class="footer-contact-list">
    @foreach($landingContacts->where('type', '!=', 'social') as $contact)
        @php($contactLabel = $contact->label ?: ($contactLabels[$contact->type] ?? 'Contacto'))
        @if($contact->href())
            <a class="contact-row contact-row-{{ $contact->type }} {{ $contact->type === 'address' ? 'address-row' : '' }}" href="{{ $contact->href() }}" @if(in_array($contact->type, ['whatsapp', 'address'])) target="_blank" rel="noopener noreferrer" @endif>
                <span class="contact-icon"><svg aria-hidden="true"><use href="#icon-{{ $contact->icon() }}"></use></svg></span>
                <span class="contact-copy"><small>{{ $contactLabel }}</small><strong>@foreach(explode("\n", $contact->value) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</strong></span>
                <span class="contact-arrow" aria-hidden="true">↗</span>
            </a>
        @else
            <div class="contact-row contact-row-{{ $contact->type }} address-row">
                <span class="contact-icon"><svg aria-hidden="true"><use href="#icon-{{ $contact->icon() }}"></use></svg></span>
                <span class="contact-copy"><small>{{ $contactLabel }}</small><strong>@foreach(explode("\n", $contact->value) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</strong></span>
            </div>
        @endif
    @endforeach
</div>
@php($socials = $landingContacts->where('type', 'social')->filter(fn ($contact) => $contact->href()))
@if($socials->isNotEmpty())
    <div class="footer-social-block">
        <div class="social-heading"><span>SÍGUENOS</span><i></i></div>
        <div class="social-links">
            @foreach($socials as $contact)
                <a href="{{ $contact->href() }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $contact->label ?: $contact->value }} de Grupo Krea" title="{{ $contact->label ?: $contact->value }}">
                    <svg aria-hidden="true">@if($contact->icon() === 'link')<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2" transform="scale(.7)"/>@else<use href="#icon-{{ $contact->icon() }}"></use>@endif</svg>
                </a>
            @endforeach
        </div>
    </div>
@endif
