@foreach($landingContacts->where('type', '!=', 'social') as $contact)
    @if($contact->href())
        <a class="contact-row {{ $contact->type === 'address' ? 'address-row' : '' }}" href="{{ $contact->href() }}" @if(in_array($contact->type, ['whatsapp', 'address'])) target="_blank" rel="noopener noreferrer" @endif>
            <svg aria-hidden="true"><use href="#icon-{{ $contact->icon() }}"></use></svg><span>@foreach(explode("\n", $contact->value) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</span>
        </a>
    @else
        <p class="contact-row address-row"><svg aria-hidden="true"><use href="#icon-{{ $contact->icon() }}"></use></svg><span>@foreach(explode("\n", $contact->value) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</span></p>
    @endif
@endforeach
@php($socials = $landingContacts->where('type', 'social')->filter(fn ($contact) => $contact->href()))
@if($socials->isNotEmpty())
    <h3 class="social-title">Síguenos</h3>
    <div class="social-links">
        @foreach($socials as $contact)
            <a href="{{ $contact->href() }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $contact->label ?: $contact->value }} de Grupo Krea" title="{{ $contact->label ?: $contact->value }}">
                <svg aria-hidden="true">@if($contact->icon() === 'link')<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2" transform="scale(.7)"/>@else<use href="#icon-{{ $contact->icon() }}"></use>@endif</svg>
            </a>
        @endforeach
    </div>
@endif
