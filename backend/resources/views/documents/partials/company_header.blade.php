@if(!empty($entreprise_logo_path) && file_exists($entreprise_logo_path))
    <img
        src="{{ $entreprise_logo_path }}"
        alt="Logo {{ $entreprise->nom ?? 'entreprise' }}"
        style="max-height: 62px; max-width: 180px; margin-bottom: 10px;"
    >
@endif
<div class="company-name">{{ $entreprise->nom ?? 'Module RH' }}</div>
