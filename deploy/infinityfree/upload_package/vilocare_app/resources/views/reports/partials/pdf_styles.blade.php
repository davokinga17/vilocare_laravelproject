<style>
    @page { size: A4 {{ $reportOrientation ?? 'portrait' }}; margin: 22px 28px 58px; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #17313d; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
    .report-page { position: relative; min-height: 748pt; page-break-after: always; }
    .report-page:last-child { page-break-after: auto; }
    .report-masthead { width: 100%; margin: 0 0 18px; border-collapse: collapse; border-bottom: 2px solid #087f72; }
    .report-masthead td { height: 80px; padding: 0 0 10px; vertical-align: middle; }
    .moh-cell { width: 105px; text-align: center; }
    .moh-logo { width: 80px; height: 80px; object-fit: contain; }
    .brand-divider { width: 1px; height: 58px; margin: 0 auto; background: #aebcc3; }
    .divider-cell { width: 22px; }
    .vilocare-cell { text-align: right; }
    .vilocare-logo { width: 205px; max-height: 62px; object-fit: contain; }
    .brand-tagline { margin: -4px 10px 0 0; color: #087f72; font-size: 7px; font-weight: 700; text-align: right; }
    .report-title-block { margin: 0 0 16px; text-align: center; }
    .report-title-block h1 { margin: 0; color: #087f72; font-size: 21px; line-height: 1.15; }
    .report-title-block p { margin: 5px 0 0; color: #627582; font-size: 11px; }
    .info-panel { width: 100%; margin: 0 0 14px; border: 1px solid #bcded9; border-collapse: separate; border-radius: 7px; background: #fbfefe; }
    .info-panel td { width: 50%; padding: 8px 10px; vertical-align: top; }
    .info-row { width: 100%; border-collapse: collapse; }
    .info-row td { padding: 3px 0; vertical-align: top; }
    .info-icon, .section-icon, .kpi-icon { display: inline-block; color: #087f72; background: #e8f6f3; border-radius: 50%; font-weight: 700; text-align: center; }
    .info-icon { width: 25px; height: 25px; padding-top: 6px; font-size: 7px; }
    .info-copy { padding-left: 8px !important; }
    .info-copy span { display: block; color: #617482; font-size: 7px; font-weight: 700; }
    .info-copy strong { display: block; margin-top: 2px; color: #087f72; font-size: 8px; word-break: break-word; }
    .kpi-grid { width: 100%; margin: 0 0 14px; border-collapse: separate; border-spacing: 7px 0; }
    .kpi-grid td { width: 25%; padding: 11px 9px; border: 1px solid #d5e1e4; border-radius: 7px; vertical-align: middle; }
    .kpi-layout { width: 100%; border-collapse: collapse; }
    .kpi-layout td { padding: 0; border: 0; }
    .kpi-icon { width: 35px; height: 35px; padding-top: 11px; font-size: 8px; }
    .kpi-copy { padding-left: 7px !important; }
    .kpi-copy span { display: block; color: #087f72; font-size: 7px; font-weight: 700; }
    .kpi-copy strong { display: block; margin-top: 4px; color: #15313d; font-size: 17px; line-height: 1; }
    .section-heading { margin: 0 0 8px; color: #087f72; font-size: 12px; font-weight: 700; }
    .section-heading.with-icon { margin-top: 3px; }
    .section-icon { width: 29px; height: 29px; margin-right: 7px; padding-top: 8px; font-size: 8px; vertical-align: middle; }
    .panel { padding: 10px; border: 1px solid #d7e2e5; border-radius: 7px; }
    .two-column { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
    .two-column > tbody > tr > td { width: 50%; padding: 0; vertical-align: top; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 6px 8px; border: 1px solid #d5e0e3; text-align: left; vertical-align: middle; }
    .data-table th { color: #fff; background: #087f72; font-size: 8px; font-weight: 700; }
    .data-table tr:nth-child(even) td { background: #f7fbfb; }
    .data-table .metric-name { color: #21404c; font-weight: 700; }
    .status-good { color: #087f72; font-weight: 700; }
    .status-alert { color: #dc3d38; font-weight: 700; }
    .donut-table { width: 100%; border-collapse: collapse; }
    .donut-table td { padding: 3px; vertical-align: middle; }
    .donut-graphic { width: 115px; text-align: center; }
    .donut-graphic svg { width: 105px; height: 105px; }
    .donut-total { font-size: 14px; font-weight: 700; fill: #18323e; }
    .donut-label { font-size: 7px; fill: #667985; }
    .legend-row { margin: 0 0 7px; color: #425966; font-size: 8px; }
    .legend-swatch { display: inline-block; width: 8px; height: 8px; margin-right: 6px; }
    .breakdown-table td:first-child { width: 43%; color: #28434f; font-weight: 700; background: #f6fafb; }
    .system-note { margin-top: 14px; padding: 11px 13px; border: 1px solid #bcded9; border-radius: 7px; background: #fbfefe; }
    .system-note strong { display: block; margin-bottom: 4px; color: #087f72; }
    .system-note p { margin: 0; color: #536b78; }
    .report-footer { position: absolute; right: 0; bottom: 0; left: 0; width: 100%; padding-top: 9px; border-top: 2px solid #087f72; }
    .report-footer table { width: 100%; border-collapse: collapse; }
    .report-footer td { vertical-align: middle; }
    .confidential { width: 38%; color: #087f72; font-size: 7px; font-weight: 700; }
    .confidential small { display: block; margin-top: 3px; color: #687a85; font-size: 6px; font-weight: 400; }
    .signature { width: 38%; color: #526975; font-size: 7px; text-align: center; }
    .signature-line { display: block; width: 135px; margin: 0 auto 4px; border-top: 1px solid #86979f; }
    .page-label { color: #087f72; font-size: 8px; font-weight: 700; text-align: right; }
    .running-footer { position: fixed; right: 0; bottom: -40px; left: 0; padding-top: 7px; border-top: 2px solid #087f72; color: #647681; font-size: 7px; }
    .running-footer table { width: 100%; border-collapse: collapse; }
    .running-footer td:nth-child(2) { text-align: center; }
    .running-footer td:last-child { color: #087f72; text-align: right; }
    .page-number:after { content: counter(page); }
    .report-meta-band { width: 100%; margin: 0 0 13px; border: 1px solid #bcded9; border-collapse: collapse; background: #fbfefe; }
    .report-meta-band td { padding: 7px 9px; border-right: 1px solid #d5e3e3; }
    .report-meta-band td:last-child { border-right: 0; }
    .report-meta-band span { display: block; color: #667985; font-size: 7px; }
    .report-meta-band strong { display: block; margin-top: 2px; color: #087f72; font-size: 8px; }
    .record-table { table-layout: fixed; font-size: 7px; }
    .record-table th, .record-table td { padding: 5px 5px; word-wrap: break-word; }
    .record-table thead { display: table-header-group; }
    .record-table tr { page-break-inside: avoid; }
    .empty-row { padding: 18px !important; color: #71828c; text-align: center !important; }
</style>
