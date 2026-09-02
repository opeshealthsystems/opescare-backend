<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpesCare — Document Template Gallery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0A1628;
            --navy2:   #0F1F3D;
            --navy3:   #162849;
            --accent:  #3B82F6;
            --accent2: #60A5FA;
            --gray-bg: #F1F5F9;
            --white:   #FFFFFF;
            --border:  rgba(255,255,255,0.08);
            --text-dim:#94A3B8;
            --sidebar-w: 280px;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--gray-bg);
            overflow: hidden;
        }

        /* ── LAYOUT ── */
        .app-shell {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            background: var(--navy);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-right: 1px solid var(--border);
        }

        .sidebar-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .logo-mark {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3B82F6, #1D4ED8);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: -0.5px;
            flex-shrink: 0;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-name {
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1.1;
        }

        .logo-tagline {
            font-size: 10px;
            color: var(--text-dim);
            font-weight: 400;
        }

        .sidebar-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        /* Search */
        .sidebar-search {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .search-wrap {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            font-size: 13px;
        }

        .search-input {
            width: 100%;
            background: var(--navy2);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px;
            padding: 8px 10px 8px 32px;
            color: white;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input::placeholder { color: #475569; }
        .search-input:focus { border-color: rgba(59,130,246,0.4); }

        /* Doc list */
        .doc-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }

        .doc-list::-webkit-scrollbar { width: 4px; }
        .doc-list::-webkit-scrollbar-track { background: transparent; }
        .doc-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

        .doc-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            cursor: pointer;
            transition: background 0.15s;
            position: relative;
            border-left: 3px solid transparent;
        }

        .doc-item:hover {
            background: rgba(255,255,255,0.04);
        }

        .doc-item.active {
            background: rgba(255,255,255,0.08);
            border-left-color: var(--accent);
        }

        .doc-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .doc-icon svg {
            width: 16px;
            height: 16px;
            fill: white;
        }

        .doc-meta {
            flex: 1;
            min-width: 0;
        }

        .doc-name {
            font-size: 12.5px;
            font-weight: 500;
            color: #CBD5E1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .doc-item.active .doc-name {
            color: white;
            font-weight: 600;
        }

        .doc-badges {
            display: flex;
            gap: 4px;
            margin-top: 3px;
        }

        .badge-code {
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            background: rgba(255,255,255,0.07);
            color: #94A3B8;
            letter-spacing: 0.5px;
        }

        .badge-status {
            font-size: 9px;
            font-weight: 600;
            padding: 1px 5px;
            border-radius: 3px;
            letter-spacing: 0.3px;
        }

        .badge-built  { background: rgba(5,150,105,0.2);  color: #34D399; }
        .badge-preview{ background: rgba(59,130,246,0.2); color: #93C5FD; }

        /* Sidebar footer stats */
        .sidebar-stats {
            padding: 14px 16px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        .stats-count {
            font-size: 13px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
        }

        .stats-pills {
            display: flex;
            gap: 6px;
        }

        .stats-pill {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
        }

        .pill-built   { background: rgba(5,150,105,0.2);  color: #34D399; }
        .pill-preview { background: rgba(59,130,246,0.2); color: #93C5FD; }

        /* ── RIGHT PANEL ── */
        .right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--gray-bg);
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: white;
            border-bottom: 1px solid #E2E8F0;
            flex-shrink: 0;
            min-height: 56px;
        }

        .toolbar-back {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            cursor: pointer;
            color: #64748B;
            background: white;
            transition: all 0.15s;
        }

        .toolbar-back:hover {
            background: #F8FAFC;
            color: #0F172A;
        }

        .toolbar-title {
            flex: 1;
            font-size: 13.5px;
            font-weight: 600;
            color: #0F172A;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toolbar-type-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 5px;
            letter-spacing: 0.5px;
        }

        .toolbar-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
        }

        .btn-outline {
            background: white;
            border: 1px solid #E2E8F0;
            color: #374151;
        }

        .btn-outline:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }

        .btn-primary {
            background: #1D4ED8;
            color: white;
        }

        .btn-primary:hover {
            background: #1E40AF;
        }

        .btn-icon {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Preview frame */
        .preview-wrap {
            flex: 1;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .preview-frame {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
        }

        /* Welcome / empty state */
        .welcome-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }

        .welcome-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .welcome-icon svg {
            width: 36px;
            height: 36px;
            color: #3B82F6;
        }

        .welcome-title {
            font-size: 22px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
        }

        .welcome-desc {
            font-size: 14px;
            color: #64748B;
            max-width: 380px;
            line-height: 1.6;
        }

        .welcome-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 32px;
            max-width: 560px;
        }

        .welcome-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.15s;
            text-align: left;
        }

        .welcome-card:hover {
            border-color: #93C5FD;
            box-shadow: 0 4px 12px rgba(59,130,246,0.1);
            transform: translateY(-1px);
        }

        .wc-dot {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .wc-dot svg { width: 14px; height: 14px; fill: white; }

        .wc-name {
            font-size: 11.5px;
            font-weight: 600;
            color: #0F172A;
            line-height: 1.3;
        }

        .wc-code {
            font-size: 9px;
            font-weight: 700;
            color: #94A3B8;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* status bar */
        .status-bar {
            padding: 6px 20px;
            background: white;
            border-top: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10.5px;
            color: #94A3B8;
            flex-shrink: 0;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10B981;
            display: inline-block;
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="app-shell">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-row">
                <div class="logo-mark">OC</div>
                <div class="logo-text">
                    <div class="logo-name">OpesCare</div>
                    <div class="logo-tagline">Health Platform</div>
                </div>
            </div>
            <div class="sidebar-subtitle">Document Templates</div>
        </div>

        <div class="sidebar-search">
            <div class="search-wrap">
                <span class="search-icon">
                    <svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="9" r="7"/><line x1="16" y1="16" x2="12.5" y2="12.5"/>
                    </svg>
                </span>
                <input type="text" class="search-input" placeholder="Search templates…" id="searchInput">
            </div>
        </div>

        <nav class="doc-list" id="docList">
            @foreach($types as $t)
            <div class="doc-item"
                 id="item-{{ $t['slug'] }}"
                 data-slug="{{ $t['slug'] }}"
                 data-name="{{ $t['name'] }}"
                 data-code="{{ $t['code'] }}"
                 data-color="{{ $t['color'] }}"
                 data-built="{{ $t['built'] ? 'true' : 'false' }}">
                <div class="doc-icon" style="background: {{ $t['color'] }}22; box-shadow: inset 0 0 0 1px {{ $t['color'] }}40;">
                    <svg viewBox="0 0 20 20" fill="{{ $t['color'] }}">
                        <path d="M4 2h8l4 4v12a1 1 0 01-1 1H5a1 1 0 01-1-1V3a1 1 0 011-1z"/>
                        <path d="M12 2v4h4" fill="none" stroke="{{ $t['color'] }}" stroke-width="1.5"/>
                        <rect x="6" y="9" width="8" height="1.5" rx=".75" fill="white" opacity=".9"/>
                        <rect x="6" y="12" width="5" height="1.5" rx=".75" fill="white" opacity=".6"/>
                    </svg>
                </div>
                <div class="doc-meta">
                    <div class="doc-name">{{ $t['name'] }}</div>
                    <div class="doc-badges">
                        <span class="badge-code">{{ $t['code'] }}</span>
                        @if($t['built'])
                            <span class="badge-status badge-built">BUILT</span>
                        @else
                            <span class="badge-status badge-preview">PREVIEW</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </nav>

        <div class="sidebar-stats">
            <div class="stats-count">{{ count($types) }} Document Templates</div>
            <div class="stats-pills">
                <span class="stats-pill pill-built">{{ collect($types)->where('built', true)->count() }} Built</span>
                <span class="stats-pill pill-preview">{{ collect($types)->where('built', false)->count() }} Preview</span>
            </div>
        </div>
    </aside>

    <!-- ── RIGHT PANEL ── -->
    <div class="right-panel">
        <!-- Toolbar -->
        <div class="toolbar" id="toolbar">
            <div class="toolbar-title" id="toolbarTitle">Select a template to preview</div>
            <span id="toolbarBadge" style="display:none;" class="toolbar-type-badge"></span>
            <button class="toolbar-btn btn-outline" id="btnOpenFull" style="display:none;">
                <span class="btn-icon">
                    <svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 3h4v4M17 3l-7 7M7 17H3v-4M3 17l7-7"/>
                    </svg>
                </span>
                Open Full Page
            </button>
            <button class="toolbar-btn btn-primary" id="btnPrint" style="display:none;">
                <span class="btn-icon">
                    <svg width="13" height="13" viewBox="0 0 20 20" fill="white">
                        <rect x="5" y="2" width="10" height="5" rx="1"/>
                        <rect x="3" y="9" width="14" height="8" rx="1"/>
                        <rect x="6" y="12" width="8" height="1.5" rx=".75"/>
                        <rect x="6" y="14.5" width="5" height="1.5" rx=".75"/>
                        <circle cx="15" cy="11" r="1"/>
                    </svg>
                </span>
                Print
            </button>
        </div>

        <!-- Preview frame / welcome state -->
        <div class="preview-wrap" id="previewWrap">
            <!-- Welcome state shown initially -->
            <div class="welcome-state" id="welcomeState">
                <div class="welcome-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h1 class="welcome-title">Document Template Gallery</h1>
                <p class="welcome-desc">Browse and preview all OpesCare clinical document templates. Select a template from the sidebar to see it rendered with realistic patient data.</p>
                <div class="welcome-grid" id="quickLaunch">
                    @foreach(array_slice($types, 0, 6) as $t)
                    <div class="welcome-card"
                         data-slug="{{ $t['slug'] }}"
                         data-name="{{ $t['name'] }}"
                         data-code="{{ $t['code'] }}"
                         data-color="{{ $t['color'] }}"
                         data-built="{{ $t['built'] ? 'true' : 'false' }}">
                        <div class="wc-dot" style="background: {{ $t['color'] }};">
                            <svg viewBox="0 0 20 20"><path d="M4 2h8l4 4v12a1 1 0 01-1 1H5a1 1 0 01-1-1V3a1 1 0 011-1z"/></svg>
                        </div>
                        <div class="wc-name">{{ $t['name'] }}</div>
                        <div class="wc-code">{{ $t['code'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- iframe uses srcdoc (not src) to bypass X-Frame-Options security header -->
            <iframe id="docFrame" class="preview-frame" style="display:none;" title="Document Preview" sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
        </div>

        <div class="status-bar">
            <div>
                <span class="status-dot"></span>
                <span id="statusText">OpesCare Document Gallery — Dev/Sales Preview Tool</span>
            </div>
            <div>OpesCare Platform · {{ date('Y') }}</div>
        </div>
    </div>

</div>

<script src="/js/document-preview.js"></script>

</body>
</html>
