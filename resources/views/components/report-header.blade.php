@props(['title' => null, 'subtitle' => null])
<div class="report-header" style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 12px;">
    <div style="margin-bottom: 6px;">
        <img src="{{ branding_header_base64() }}" alt="{{ branding_clinic_name() }}" style="max-width: 100%; height: auto; max-height: 95px; display: block; margin: 0 auto;">
    </div>
    @if($title)
        <h2 style="margin: 8px 0 2px 0; font-size: 16px; font-weight: bold; text-transform: uppercase; color: #111; letter-spacing: 1px;">{{ $title }}</h2>
    @endif
    @if($subtitle)
        <p style="margin: 0; font-size: 11px; color: #555;">{{ $subtitle }}</p>
    @endif
</div>
