@push('styles')
    <style>
        .bi-input {
            width: 100%;
            min-height: 34px;
            padding: 6px 10px;
            font-size: var(--text-sm, 0.8125rem);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: var(--radius-md, 8px);
            background: var(--bg-hover, #f9fafb);
            color: var(--text-color, #111827);
            box-sizing: border-box;
        }
        .bi-mapel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
        .bi-mapel-col {
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: var(--radius-lg, 12px);
            padding: 12px;
            background: var(--bg-card, #fff);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .bi-col-head { display: flex; align-items: center; justify-content: space-between; font-weight: 800; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); }
        .bi-mapel-col-locked {
            border: 1px dashed rgba(13,148,136,0.5);
            border-radius: var(--radius-lg, 12px);
            padding: 12px;
            background: rgba(13,148,136,0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 360px;
        }
        .bi-mapel-col-locked .bi-sel-item { background: rgba(13,148,136,0.1); border-color: rgba(13,148,136,0.3); }
        .bi-count {
            font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted);
            background: rgba(0,0,0,0.05); border-radius: 999px; padding: 2px 8px;
        }
        .bi-ref-search-wrap { position: relative; }
        .bi-ref-search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: var(--text-xs, 0.75rem); }
        .bi-ref-search-wrap .bi-ref-search { padding-left: 28px; }
        .bi-ref-list { max-height: 150px; overflow-y: auto; border: 1px dashed var(--border-color, #e5e7eb); border-radius: var(--radius-md, 8px); margin-top: 6px; }
        .bi-ref-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 5px 8px; font-size: var(--text-xs, 0.75rem); border-bottom: 1px solid var(--border-color, #f1f2f4); }
        .bi-ref-item:last-child { border-bottom: none; }
        .bi-ref-item span { color: var(--text-color); }
        .bi-ref-item button { border: none; background: rgba(16,185,129,0.12); color: #10b981; width: 22px; height: 22px; border-radius: var(--radius-sm, 6px); cursor: pointer; flex-shrink: 0; }
        .bi-ref-empty { padding: 10px; text-align: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem); }
        .bi-selected { display: flex; flex-direction: column; gap: 6px; min-height: 40px; }
        .bi-sel-empty { text-align: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem); padding: 10px; border: 1px dashed var(--border-color, #e5e7eb); border-radius: var(--radius-md, 8px); }
        .bi-sel-item { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.18); }
        .bi-sel-idx { font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); min-width: 18px; }
        .bi-sel-name { flex: 1; font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-color); }
        .bi-sel-actions { display: flex; gap: 3px; }
        .bi-sel-actions button { border: 1px solid var(--border-color, #e5e7eb); background: var(--bg-card, #fff); color: var(--text-muted); width: 24px; height: 24px; border-radius: var(--radius-sm, 6px); cursor: pointer; font-size: var(--text-2xs, 0.6875rem); }
        .bi-sel-actions button:hover { color: var(--text-color); }
        .bi-sel-actions .bi-remove { color: #ef4444; border-color: rgba(239,68,68,0.3); }
        .bi-add-custom { display: flex; gap: 6px; }
        .bi-add-custom .bi-add-btn { padding: 0 12px; height: 34px; flex-shrink: 0; }
        .bi-import-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .bi-file-input {
            flex: 1;
            min-width: 240px;
            padding: 8px 12px;
            font-size: var(--text-sm, 0.8125rem);
            border-radius: var(--radius-md, 8px);
            border: 1px solid var(--border-color, #e5e7eb);
            background: var(--bg-hover, #f9fafb);
            color: var(--text-color, #111827);
            box-sizing: border-box;
        }
        .bi-file-input.bi-file-sm { flex: 0 0 auto; min-width: 0; width: 150px; }
        .bi-sem-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
        .bi-sem-tab {
            border: 1px solid var(--border-color, #e5e7eb);
            background: var(--bg-card, #fff);
            color: var(--text-muted);
            border-radius: var(--radius-md, 8px);
            padding: 7px 16px;
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 700;
            cursor: pointer;
        }
        .bi-sem-tab.active { background: var(--primary, #3b82f6); border-color: var(--primary, #3b82f6); color: #fff; }
        .bi-sem-tab .bi-sem-badge { font-size: var(--text-2xs, 0.6875rem); font-weight: 700; opacity: .8; margin-left: 6px; }
        .bi-sem-panel { display: none; }
        .bi-sem-panel.active { display: block; }
        .bi-sem-note {
            grid-column: 1 / -1;
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted);
            background: rgba(99,102,241,0.08);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: var(--radius-md, 8px);
            padding: 8px 10px;
        }
        .bi-pkl-toggle { display: flex; align-items: center; gap: 7px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); cursor: pointer; white-space: nowrap; }
        .bi-copy-row {
            display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
            margin-bottom: 12px; padding: 8px 12px; border-radius: var(--radius-md, 8px);
            background: rgba(67,56,202,0.05); border: 1px solid rgba(67,56,202,0.18);
            font-size: var(--text-xs, 0.75rem); color: var(--text-muted);
        }
        .bi-pkl-per-sem {
            display: flex; align-items: flex-start; gap: 8px; white-space: normal; font-weight: 600;
            margin-bottom: 14px; padding: 10px 12px; border-radius: var(--radius-md, 8px);
            background: rgba(13,148,136,0.06); border: 1px solid rgba(13,148,136,0.2); line-height: 1.5;
        }
        .bi-pkl-per-sem input { margin-top: 2px; }
        .bi-nilai-scroll { overflow-x: auto; border: 1px solid var(--border-color, #e5e7eb); border-radius: var(--radius-md, 8px); }
        .bi-nilai-table { border-collapse: collapse; width: 100%; }
        .bi-nilai-table th, .bi-nilai-table td { border-bottom: 1px solid var(--border-color, #f1f2f4); padding: 5px 6px; text-align: left; font-size: var(--text-xs, 0.75rem); white-space: nowrap; }
        .bi-nilai-table thead th {
            position: sticky; top: 0; z-index: 1; background: var(--bg-hover, #f9fafb);
            font-weight: 700; color: var(--text-color); vertical-align: bottom;
        }
        .bi-nilai-nama { font-weight: 600; color: var(--text-color); }
        .bi-nilai-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
        .bi-nilai-input {
            width: 72px; min-height: 30px; padding: 4px 6px; text-align: center;
            border: 1px solid var(--border-color, #e5e7eb); border-radius: var(--radius-sm, 6px);
            background: var(--bg-card, #fff); color: var(--text-color); font-size: var(--text-xs, 0.75rem);
        }
        .bi-nilai-input:focus { outline: none; border-color: var(--primary, #3b82f6); }
        .bi-singkatan-item {
            flex: 0 0 64px; width: 64px; min-height: 24px; padding: 2px 6px; text-align: center;
            font-size: var(--text-2xs, 0.6875rem); font-weight: 700; letter-spacing: .02em;
        }
    </style>
@endpush
