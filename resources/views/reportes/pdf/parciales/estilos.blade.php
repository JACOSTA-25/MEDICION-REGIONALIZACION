<style>
    :root {
        --paper-width: 215.9mm;
        --paper-height: 279.4mm;
        --design-height: 297mm;
        --header-horizontal-shift: -6mm;
        --content-left: 64px;
        --content-right: 108px;
        --content-narrow-right: 108px;
    }

    @page {
        size: letter portrait;
        margin: 0;
    }

    body {
        margin: 0;
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
    }

    .page {
        position: relative;
        box-sizing: border-box;
        width: var(--paper-width);
        min-height: var(--paper-height);
        overflow: hidden;
    }

    .page + .page {
        page-break-before: always;
    }

    .cover-page {
        padding: 0;
        height: var(--paper-height);
    }

    .cover-image {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: fill;
        z-index: 0;
    }

    .cover-quarter-roman {
        position: absolute;
        left: 48%;
        top: 35.5%;
        width: 7.45%;
        height: 4.45%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #f3f4f6;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 35px;
        font-style: normal;
        font-weight: 700;
        letter-spacing: 0.5px;
        line-height: 1;
        z-index: 2;
    }

    .page-with-decor {
        padding: 128px 52px 60px 52px;
    }

    .decor-header {
        position: absolute;
        top: 5px;
        left: calc(50% + var(--header-horizontal-shift));
        transform: translateX(-50%);
        width: 160mm;
        height: auto;
        z-index: 1;
    }

    .decor-sidebar {
        position: absolute;
        top: 0;
        left: 0;
        width: 36px;
        height: var(--design-height);
    }

    .content-block,
    .content-block-wide,
    .content-block-full {
        position: relative;
        z-index: 2;
        margin-top: 4px;
    }

    .content-block {
        margin-left: var(--content-left);
        margin-right: var(--content-narrow-right);
    }

    .content-block-wide {
        margin-left: var(--content-left);
        margin-right: var(--content-right);
    }

    .content-block-full {
        margin-left: var(--content-left);
        margin-right: var(--content-right);
    }

    h1,
    h2,
    h3,
    p,
    ul {
        margin: 0;
    }

    .section-title {
        margin: 0 0 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .section-title + .section-title {
        margin-top: 12px;
    }

    .section-text {
        margin-bottom: 10px;
        font-size: 12px;
        line-height: 1.5;
        text-align: justify;
    }

    .section-list {
        margin: 6px 0 10px 18px;
        padding: 0;
        font-size: 12px;
        line-height: 1.5;
    }

    .table-title {
        margin: 8px 0 6px;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid #d1d5db;
        padding: 5px 6px;
        vertical-align: top;
        font-size: 11px;
    }

    th {
        background: #4583ff;
        color: #111827;
        font-weight: 700;
        text-align: left;
    }

    .compact-table {
        width: 70%;
        margin-left: auto;
        margin-right: auto;
    }

    .compact-table td:last-child,
    .compact-table th:last-child,
    .centered-table td:not(:first-child),
    .centered-table th:not(:first-child),
    .summary-table td:last-child,
    .summary-table th:last-child,
    .consolidated-table td,
    .consolidated-table th {
        text-align: center;
    }

    .centered-table {
        width: 74%;
        margin-left: auto;
        margin-right: auto;
    }

    .summary-table {
        width: 66%;
        margin: 24px auto 24px;
    }

    .summary-table td:first-child,
    .summary-table th:first-child {
        width: 42%;
        text-align: left;
    }

    .summary-highlight {
        font-weight: 700;
        color: #0f172a;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .chart-shell {
        padding: 0;
    }

    .chart-image {
        width: 100%;
        height: auto;
        display: block;
    }

    .chart-caption {
        margin-top: 4px;
        text-align: center;
        font-size: 10px;
        color: #374151;
    }

    .services-chart {
        width: 86%;
        margin: 18px auto 0;
    }

    .question-title {
        margin: 0 0 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .question-result-table {
        width: 92%;
        margin: 12px auto 0;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .question-result-table td {
        border: 1px solid #111827;
        padding: 8px 10px;
        font-size: 11.4px;
        line-height: 1.26;
        vertical-align: top;
    }

    .question-row-title {
        padding: 10px 14px;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.14;
        text-align: center;
        text-transform: uppercase;
    }

    .question-row-intro {
        font-size: 11.7px;
    }

    .question-row-summary {
        font-size: 11.7px;
        line-height: 1.18;
    }

    .question-row-indicator {
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        vertical-align: middle !important;
    }

    .question-row-analysis {
        padding: 10px 12px;
        text-align: justify;
    }

    .question-row-analysis p {
        margin: 0 0 12px;
    }

    .question-row-analysis p:last-child {
        margin-bottom: 0;
    }

    .question-row-chart {
        padding: 12px 10px 8px;
    }

    .question-row-caption {
        padding: 8px 10px;
        text-align: center;
        font-size: 10px;
    }

    .question-inline-number {
        color: #111827;
        font-weight: 700;
    }

    .question-chart-figure {
        width: 84%;
        margin: 0 auto;
    }

    .question-chart {
        width: 76%;
        margin: 12px auto 0;
        position: relative;
        left: 18px;
    }

    .question-chart .chart-image {
        width: 94%;
        margin-left: auto;
        margin-right: auto;
    }

    .consolidated-table {
        width: 74%;
        margin: 0 auto;
        table-layout: fixed;
    }

    .consolidated-table th,
    .consolidated-table td {
        font-size: 8.7px;
        padding: 3px;
        word-break: break-word;
    }

    .consolidated-table th:nth-child(1),
    .consolidated-table td:nth-child(1) {
        width: 8%;
    }

    .consolidated-table th:nth-child(2),
    .consolidated-table td:nth-child(2) {
        width: 22%;
    }

    .consolidated-table th:nth-child(3),
    .consolidated-table td:nth-child(3),
    .consolidated-table th:nth-child(4),
    .consolidated-table td:nth-child(4),
    .consolidated-table th:nth-child(5),
    .consolidated-table td:nth-child(5) {
        width: 13%;
    }

    .consolidated-table th:nth-child(6),
    .consolidated-table td:nth-child(6) {
        width: 12%;
    }

    .consolidated-table th:nth-child(7),
    .consolidated-table td:nth-child(7) {
        width: 12%;
    }

    .indicator-chart {
        width: 56%;
        margin: 16px auto 0;
    }

    .signature-block {
        margin-top: 20px;
    }

    .signature-name,
    .signature-title,
    .signature-scope {
        font-size: 12px;
        line-height: 1.5;
    }

    .services-page .content-block-wide {
        margin-left: var(--content-left);
        margin-right: var(--content-right);
    }

    .signature-name {
        font-weight: 700;
    }

    .toc-page .content-block {
        margin-right: var(--content-right);
    }

    .balanced-right-spacing .content-block,
    .balanced-right-spacing .content-block-full {
        margin-right: 116px !important;
    }

    .toc-list {
        margin-top: 12px;
    }

    .toc-entry {
        margin-bottom: 8px;
        font-size: 12px;
        line-height: 1.4;
        overflow: hidden;
    }

    .toc-entry-label {
        font-weight: normal;
        color: #1a1a1a;
    }

    .toc-entry-dots {
        letter-spacing: 2px;
        color: #6b7280;
        word-spacing: -2px;
    }

    .toc-entry-page {
        float: right;
        padding-left: 6px;
        font-weight: normal;
        color: #1a1a1a;
    }
</style>
