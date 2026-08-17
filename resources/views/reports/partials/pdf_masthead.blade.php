<table class="report-masthead">
    <tr>
        <td class="moh-cell">
            @if(!empty($report['ministry_logo_image']))
                <img src="{{ $report['ministry_logo_image'] }}" alt="Ministry of Health" class="moh-logo">
            @endif
        </td>
        <td class="divider-cell"><div class="brand-divider"></div></td>
        <td class="vilocare-cell">
            @if(!empty($report['logo_image']))
                <img src="{{ $report['logo_image'] }}" alt="ViLoCare" class="vilocare-logo">
            @else
                {!! $report['logo_svg'] !!}
            @endif
            <p class="brand-tagline">Digital Health. Better Outcomes.</p>
        </td>
    </tr>
</table>
