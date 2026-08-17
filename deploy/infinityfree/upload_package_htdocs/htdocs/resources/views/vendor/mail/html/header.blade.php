<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $logoPath = public_path('images/vilocarelogo.png');
    $logoDataUri = null;

    if (is_file($logoPath) && is_readable($logoPath)) {
        $mimeType = mime_content_type($logoPath) ?: 'image/png';
        $logoDataUri = 'data:'.$mimeType.';base64,'.base64_encode(file_get_contents($logoPath));
    }
@endphp
@if ($logoDataUri)
<img src="{{ $logoDataUri }}" class="logo" alt="ViloCare" style="display: block; height: 72px; max-height: 72px; width: auto;">
@else
<span style="font-size: 28px; font-weight: 700; color: #1f2937;">ViloCare</span>
@endif
</a>
</td>
</tr>
