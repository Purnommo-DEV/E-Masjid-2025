@extends('masjid.master-guest')

@section('title', 'Laporan Qurban ' . $heroData['subtitle'] . ' | ' . $heroData['masjid'])

@push('head')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(
                135deg,
                #ffffff 0%,
                #f8fafc 25%,
                #ecfdf5 65%,
                #d1fae5 100%
            );
            background-size: 400% 400%;
            animation: softMorphGradient 24s ease infinite;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cpath fill='%23064e3b' fill-opacity='0.03' d='M50,10 L65,30 L35,30 Z M20,40 L80,40 L80,45 L20,45 Z M30,45 L70,45 L70,50 L30,50 Z M40,50 L60,50 L60,55 L40,55 Z M45,55 L55,55 L55,60 L45,60 Z M48,60 L52,60 L52,65 L48,65 Z'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 60px;
            pointer-events: none;
            z-index: 0;
            opacity: 0.4;
        }
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='100' cy='30' r='20' fill='none' stroke='%230f766e' stroke-opacity='0.04' stroke-width='2'/%3E%3Cpath d='M80,50 L120,50 L120,55 L80,55 Z' fill='%230f766e' fill-opacity='0.04'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 120px;
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
        }
        @keyframes softMorphGradient {
            0% { background-position: 0% 50%; }
            25% { background-position: 40% 30%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 40% 70%; }
            100% { background-position: 0% 50%; }
        }
        .font-serif { font-family: 'Playfair Display', serif; }
        
        .dramatic-section {
            position: relative;
            background-attachment: fixed;
            background-position: center center;
            background-size: cover;
            border-radius: 2rem;
            overflow: hidden;
        }
        .dramatic-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.7) 100%);
            z-index: 1;
        }
        .dramatic-content { position: relative; z-index: 2; }
        
        .card-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-radius: 1.75rem;
            border: 1px solid rgba(6,78,59,0.2);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
        .card-modern:hover { transform: translateY(-5px); border-color: #f59e0b; background: white; }
        
        .badge-gold {
            background: linear-gradient(95deg, #064e3b, #0f766e);
            color: #fef3c7;
            padding: 0.35rem 1.4rem;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #f59e0b50;
        }
        .title-grad {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            background: linear-gradient(125deg, #064e3b, #0f766e, #f59e0b);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .gallery-item {
            position: relative;
            border-radius: 1.25rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 15px 25px -12px rgba(0,0,0,0.15);
            background: #d4c9a8;
            width: 100%;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
            display: block;
        }
        .gallery-item:hover img { transform: scale(1.05); }
        .gallery-item.square { aspect-ratio: 1 / 1; }
        .gallery-item.landscape {
            grid-column: span 2;
            aspect-ratio: 20.8 / 10;
        }
        .gallery-more-overlay {
            position: absolute;
            inset: 0;
            background: rgba(6,78,59,0.85);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            z-index: 2;
        }
        .gallery-more-overlay i { font-size: 2rem; margin-bottom: 0.5rem; }
        .gallery-more-overlay span { font-size: 1.2rem; }
        
        @media (max-width: 768px) {
            .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
            .gallery-item.landscape { grid-column: span 2; }
        }
        @media (max-width: 480px) {
            .gallery-grid { grid-template-columns: 1fr; gap: 0.75rem; }
            .gallery-item.landscape { grid-column: span 1; aspect-ratio: 16 / 9; }
        }
        
        /* Animasi untuk gallery item - petunjuk klik */
        .gallery-item {
            position: relative;
            border-radius: 1.25rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 15px 25px -12px rgba(0,0,0,0.15);
            background: #d4c9a8;
            width: 100%;
        }

        /* Efek pulse pada saat pertama kali load (petunjuk klik) */
        .gallery-item.pulse-animation {
            animation: softPulse 1.5s ease-in-out 2;
        }

        @keyframes softPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(245, 158, 11, 0.2);
                transform: scale(1.01);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
                transform: scale(1);
            }
        }

        /* Efek hover */
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.2);
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
            display: block;
        }

        /* Efek zoom icon saat hover */
        .gallery-item::after {
            content: '\f00e';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            z-index: 10;
            pointer-events: none;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .gallery-item:hover::after {
            transform: translate(-50%, -50%) scale(1);
        }

        /* Efek overlay gelap saat hover */
        .gallery-item::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.3);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 5;
            pointer-events: none;
        }

        .gallery-item:hover::before {
            opacity: 1;
        }

        /* Tooltip petunjuk klik */
        .gallery-tooltip {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.6rem;
            color: #fbbf24;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 15;
            opacity: 0;
            transform: translateY(5px);
            transition: all 0.3s ease;
            pointer-events: none;
            border: 0.5px solid rgba(255,255,255,0.2);
        }

        .gallery-item:hover .gallery-tooltip {
            opacity: 1;
            transform: translateY(0);
        }

        /* Untuk more overlay button */
        .gallery-more-overlay {
            position: absolute;
            inset: 0;
            background: rgba(6,78,59,0.85);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .gallery-more-overlay i { 
            font-size: 2rem; 
            margin-bottom: 0.5rem; 
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-more-overlay i {
            transform: scale(1.1);
        }

        .gallery-more-overlay span { 
            font-size: 1.2rem; 
        }

        /* Efek tap di mobile */
        @media (max-width: 768px) {
            .gallery-item {
                -webkit-tap-highlight-color: rgba(245, 158, 11, 0.3);
            }
            .gallery-item:active {
                transform: scale(0.97);
                transition: transform 0.1s ease;
            }
        }
        
        /* MODAL GALLERY - PREMIUM GLASS (Z-index DI ATAS NAVBAR) */
        .modal-gallery {
            display: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            z-index: 999999999 !important; /* Lebih tinggi dari navbar (z-index 40) */
            justify-content: center !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 20px !important;
        }

        .modal-gallery.active {
            display: flex !important;
        }

        .modal-container {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            max-width: 90vw !important;
            max-height: 90vh !important;
            background: transparent !important;
        }

        /* Tombol Close */
        .modal-close {
            position: absolute !important;
            top: -50px !important;
            right: -10px !important;
            color: white !important;
            font-size: 1.5rem !important;
            cursor: pointer !important;
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(8px) !important;
            width: 44px !important;
            height: 44px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s !important;
            z-index: 1000001 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }

        .modal-close:hover { 
            background: #f59e0b !important; 
            transform: scale(1.1) rotate(90deg) !important;
            border-color: white !important;
            color: white !important;
        }

        /* Tombol Download */
        .modal-download {
            position: absolute !important;
            top: -50px !important;
            right: 55px !important;
            color: white !important;
            font-size: 1.3rem !important;
            cursor: pointer !important;
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(8px) !important;
            width: 44px !important;
            height: 44px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s !important;
            z-index: 1000001 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }

        .modal-download:hover {
            background: #f59e0b !important;
            transform: scale(1.1) !important;
            border-color: white !important;
            color: white !important;
        }

        /* Area Gambar - BORDER/OUTLINE DIPERKECIL */
        .modal-image-area {
            overflow: hidden !important;
            border-radius: 1rem !important; /* dari 1.5rem jadi 1rem */
            width: 85vw !important;
            height: 70vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .modal-image-area img {
            max-width: 100% !important;
            max-height: 100% !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            border-radius: 1rem !important; /* dari 1.5rem jadi 1rem */
            box-shadow: 0 15px 25px -10px rgba(0, 0, 0, 0.3), 0 0 0 3px rgba(255, 255, 255, 0.15), 0 0 0 6px rgba(0, 0, 0, 0.2) !important; /* outline diperkecil: 6px→3px, 12px→6px */
            border: 1px solid rgba(255, 255, 255, 0.3) !important; /* border lebih tipis */
        }

        /* Footer - Tombol Navigasi + Counter */
        .modal-footer {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 20px !important;
            margin-top: 20px !important;
            position: relative !important;
            z-index: 100 !important;
            background: transparent !important;
        }

        /* Tombol Navigasi (Prev/Next) */
        .modal-nav-btn {
            background: rgba(255, 255, 255, 0.2) !important;
            backdrop-filter: blur(8px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: white !important;
            font-size: 1.2rem !important;
            width: 48px !important;
            height: 48px !important;
            border-radius: 50% !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .modal-nav-btn:hover {
            background: #f59e0b !important;
            transform: scale(1.1) !important;
            border-color: white !important;
            color: white !important;
        }

        /* Counter */
        .modal-counter {
            text-align: center !important;
            color: white !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(8px) !important;
            padding: 8px 20px !important;
            border-radius: 40px !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            letter-spacing: 0.5px !important;
        }

        /* Responsive Mobile */
        @media (max-width: 768px) {
            .modal-gallery {
                padding: 10px !important;
            }
            
            .modal-image-area {
                width: 95vw !important;
                height: 60vh !important;
                border-radius: 0.75rem !important;
            }
            
            .modal-image-area img {
                border-radius: 0.75rem !important;
                box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.3), 0 0 0 2px rgba(255, 255, 255, 0.15), 0 0 0 4px rgba(0, 0, 0, 0.2) !important;
            }
            
            .modal-footer {
                gap: 12px !important;
                margin-top: 15px !important;
            }
            
            .modal-nav-btn {
                width: 44px !important;
                height: 44px !important;
                font-size: 1rem !important;
            }
            
            .modal-counter {
                font-size: 0.7rem !important;
                padding: 6px 16px !important;
            }
            
            .modal-close {
                top: -45px !important;
                right: -5px !important;
                width: 36px !important;
                height: 36px !important;
                font-size: 1.2rem !important;
            }
            
            .modal-download {
                top: -45px !important;
                right: 40px !important;
                width: 36px !important;
                height: 36px !important;
                font-size: 1rem !important;
            }
        }

        /* Tablet */
        @media (min-width: 769px) and (max-width: 1024px) {
            .modal-image-area {
                width: 80vw !important;
                height: 65vh !important;
            }
        }
        /* CSS untuk sembunyikan navbar */
        body.modal-open #mainNav {
            display: none !important;
        }

        body.modal-open .fixed.bottom-6 {
            display: none !important;
        }

        .list-distribution-single li { 
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.75rem 0; border-bottom: 1px solid #fde68a; flex-wrap: wrap; gap: 0.5rem;
        }
        .list-distribution-single li span:first-child { font-weight: 600; color: #1e293b; font-size: 0.85rem; }
        .list-distribution-single li span:last-child { font-weight: 800; color: #0f766e; background: #e6f7f0; padding: 0.2rem 0.8rem; border-radius: 30px; font-size: 0.75rem; }
        @media (max-width: 640px) {
            .list-distribution-single li { flex-direction: column; align-items: flex-start; }
        }
        
        .ring-container { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .ring-container { grid-template-columns: 1fr; gap: 1.5rem; } }
        .ring-card { border: 1px solid #e2e8f0; border-radius: 1.25rem; overflow: hidden; background: white; }
        .ring-header { padding: 1rem 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem; }
        .ring-body { padding: 1rem 1.25rem; }
        .ring-footer { background: #f8fafc; padding: 0.75rem 1.25rem; text-align: right; border-top: 1px solid #f1f5f9; font-weight: 700; }
        
        .info-bar-modern {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.75rem 1.5rem;
            padding: 0.7rem 1.8rem;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .info-bar-modern span { font-size: 0.8rem; color: white; }
        .info-bar-modern i { margin-right: 0.4rem; color: #fbbf24; }
        @media (max-width: 640px) {
            .info-bar-modern { flex-direction: column; padding: 0.6rem 1.2rem; border-radius: 1.5rem; }
        }
        
        .btn-pdf { background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s; }
        .btn-pdf:hover { background: rgba(255,255,255,0.35); transform: translateY(-2px); }
        
        .hero-premium { background: linear-gradient(115deg, #064e3b 0%, #0f766e 45%, #14b8a6 100%); border-radius: 2rem; }
        .stat-card { background: rgba(255,255,255,0.96); border-radius: 1.25rem; border-bottom: 3px solid #f59e0b; }
        .stat-number { font-size: 2.6rem; font-weight: 900; }
        
        .finance-section {
            background: white;
            border-radius: 1.25rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.2s;
        }
        .finance-section-header {
            background: linear-gradient(95deg, #f8fafc, #f1f5f9);
            padding: 0.75rem 1.25rem;
            border-bottom: 2px solid #cbd5e1;
        }
        .finance-section-body { padding: 1rem 1.25rem; }
        
        .finance-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
        .finance-label { font-weight: 500; color: #334155; font-size: 0.875rem; }
        .finance-amount { font-weight: 700; color: #0f766e; font-size: 0.95rem; }
        @media (max-width: 640px) {
            .finance-row { flex-direction: column; align-items: flex-start; gap: 0.25rem; }
        }
        
        .total-box { background: #f0fdf4; padding: 1rem; border-radius: 0.75rem; }
        .total-box-inner { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        @media (max-width: 640px) { .total-box-inner { flex-direction: column; text-align: center; } }
        
        .cinematic-quote { font-size: 1.4rem; line-height: 1.5; font-style: italic; text-shadow: 0 2px 10px rgba(0,0,0,0.4); font-weight: 600; }
        @media (min-width:768px) { .cinematic-quote { font-size: 2rem; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #fef9e6; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(#064e3b, #f59e0b); border-radius: 10px; }
        
        .keterangan-list { list-style: none; padding-left: 0; }
        .keterangan-list li { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.8rem; color: #334155; }
        .keterangan-list li i { color: #0f766e; margin-top: 0.2rem; flex-shrink: 0; }
        
        .content-wrapper { position: relative; z-index: 2; }
        
        /* Dropdown tahun */
        .tahun-dropdown {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            color: white;
            font-size: 0.875rem;
            cursor: pointer;
        }
        .tahun-dropdown option {
            background: #064e3b;
            color: white;
        }
        
        /* GALERI PELAKSANAAN */
        .glass-gallery {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        
        .glass-row-top {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.25rem;
            align-items: stretch;
        }
        
        .glass-row-bottom {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.25rem;
            align-items: stretch;
        }
        
        .glass-card {
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            background: #1a1a2e;
            height: 100%;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.3);
        }
        
        .glass-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .glass-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }
        
        .glass-card:hover .glass-image img {
            transform: scale(1.08);
        }
        
        .glass-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);
            padding: 1rem 0.8rem 0.6rem;
            z-index: 5;
        }
        
        .glass-caption h4 {
            font-size: 0.85rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .glass-caption i {
            color: #d4af37;
            font-size: 0.7rem;
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                115deg,
                transparent 0%,
                transparent 40%,
                rgba(255,255,255,0.08) 45%,
                rgba(255,255,255,0.12) 50%,
                rgba(255,255,255,0.08) 55%,
                transparent 60%,
                transparent 100%
            );
            transform: translateX(-100%) rotate(25deg);
            transition: transform 0.6s ease;
            z-index: 6;
            pointer-events: none;
        }
        
        .glass-card:hover::before {
            transform: translateX(100%) rotate(25deg);
        }
        
        .glass-row-top, .glass-row-bottom {
            min-height: 260px;
        }
        
        /* RESPONSIVE MOBILE - TETAP SAMA KAYAK DESKTOP */
        @media (max-width: 768px) {
            .glass-gallery {
                gap: 0.8rem;
            }
            
            /* TETAP 2 KOLOM, TIDAK DIUBAH JADI 1FR */
            .glass-row-top {
                grid-template-columns: 2fr 1fr;
                gap: 0.8rem;
                min-height: auto;
            }
            
            .glass-row-bottom {
                grid-template-columns: 1fr 2fr;
                gap: 0.8rem;
                min-height: auto;
            }
            
            .glass-card {
                min-height: 180px;
            }
            
            .glass-caption {
                padding: 0.6rem 0.5rem 0.4rem;
            }
            
            .glass-caption h4 {
                font-size: 0.65rem;
                gap: 0.3rem;
            }
            
            .glass-caption i {
                font-size: 0.55rem;
            }
        }
        
        /* UNTUK HP SANGAT KECIL */
        @media (max-width: 480px) {
            .glass-card {
                min-height: 150px;
            }
            
            .glass-caption {
                padding: 0.4rem 0.4rem 0.3rem;
            }
            
            .glass-caption h4 {
                font-size: 0.55rem;
            }
            
            .glass-caption i {
                font-size: 0.45rem;
            }
        }

        /* SELECT TAHUN LAPORAN*/
        .dropdown-fun {
            transition: all 0.2s cubic-bezier(0.34, 1.2, 0.64, 1);
        }
        .dropdown-fun:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 8px 20px -8px rgba(245, 158, 11, 0.3);
        }
        .dropdown-fun:active {
            transform: scale(0.98);
        }
        
        @keyframes wiggle {
            0%, 100% { transform: translateY(-50%) rotate(0deg); }
            25% { transform: translateY(-50%) rotate(-5deg); }
            75% { transform: translateY(-50%) rotate(5deg); }
        }
        
        select:hover + .wiggle-icon {
            animation: wiggle 0.5s ease-in-out;
        }
    </style>
@endpush

@section('content')
    
    <div class="content-wrapper max-w-7xl mx-auto px-5 md:px-8 lg:px-10 py-8 md:py-12 space-y-16">

        <!-- SELECTOR TAHUN -->
        @if(count($availableYears) > 0)
            <div class="flex flex-col items-center justify-center my-6">
                <div class="relative group">
                    <!-- Label yang bergerak -->
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-500 text-white px-3 py-0.5 rounded-full text-[10px] font-bold shadow-md z-10 whitespace-nowrap">
                        <i class="fas fa-chart-line mr-1"></i> LIHAT TAHUN LAIN
                    </div>
                    
                    <select id="tahunSelect"
                            onchange="if(this.value) window.location.href = this.value;"
                            class="dropdown-fun appearance-none bg-gradient-to-r from-white to-amber-50
                                border-2 border-amber-300 hover:border-amber-500
                                text-gray-800 text-base font-semibold
                                rounded-2xl px-8 py-3.5 pr-12
                                focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-100
                                shadow-md
                                cursor-pointer
                                min-w-[220px] md:min-w-[260px]
                                transition-all duration-200">
                        @foreach($availableYears as $year)
                            <option value="{{ route('qurban.laporan', $year) }}" 
                                    {{ $report->tahun_hijriah == $year ? 'selected' : '' }}>
                                📅 Laporan {{ $year }}
                            </option>
                        @endforeach
                    </select>
                    
                    <!-- Icon dengan wiggle -->
                    <div class="wiggle-icon pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-amber-500">
                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Hint yang lucu -->
                <div class="mt-3 flex items-center gap-2 text-gray-400 text-xs">
                    <span class="inline-block w-5 h-5 rounded-full bg-amber-100 text-amber-500 text-center leading-5">
                        📋
                    </span>
                    <span>Pilih tahun untuk melihat laporan qurban</span>
                </div>
            </div>
        @endif

        <!-- HERO UTAMA -->
        <div class="hero-premium relative overflow-hidden" data-aos="fade-up">
            <div class="absolute top-0 right-0 w-64 h-64 bg-amber-400 rounded-full opacity-10 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-teal-300 rounded-full opacity-10 blur-2xl"></div>
            <div class="relative z-10 py-12 md:py-20 lg:py-24 px-6 text-center">
                <div class="inline-flex items-center gap-2 bg-black/30 backdrop-blur-md rounded-full px-3 py-1 md:px-5 md:py-1.5 mb-5 border border-white/20">
                    <span class="relative flex h-2 w-2 md:h-2.5 md:w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-300"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 md:h-2.5 md:w-2.5 bg-teal-400"></span>
                    </span>
                    <span class="text-white text-[10px] md:text-xs font-bold tracking-wider">{{ $heroData['badge'] }}</span>
                </div>
                <h1 class="font-serif font-black text-white drop-shadow-2xl tracking-tight leading-[1.1]">
                    <span class="block text-5xl sm:text-6xl md:text-6xl lg:text-7xl xl:text-9xl">{{ $heroData['title'] }}</span>
                    <span class="block text-amber-300 mt-3 text-5xl sm:text-6xl md:text-6xl lg:text-7xl xl:text-8xl">{{ $heroData['subtitle'] }}</span>
                </h1>
                <div class="flex justify-center gap-3 my-6">
                    <div class="w-20 h-1 bg-amber-400 rounded-full"></div>
                    <div class="w-8 h-1 bg-teal-300 rounded-full"></div>
                </div>
                <p class="max-w-2xl mx-auto text-white/95 text-sm md:text-lg font-semibold bg-black/20 backdrop-blur-md inline-block px-4 py-1.5 md:px-6 md:py-2 rounded-full">
                    <i class="fas fa-mosque mr-1 md:mr-2"></i> {{ $heroData['masjid'] }}
                </p>
                
                <div class="flex justify-center mt-6">
                    <div class="info-bar-modern">
                        <span><i class="fas fa-book-quran"></i> {{ $heroData['tagline'] }}</span>
                    </div>
                </div>

                <div class="mt-8">
                    <button onclick="window.print()" class="btn-pdf inline-flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-full transition-all">
                        <i class="fas fa-file-pdf"></i>
                        <span>Cetak/Simpan PDF</span>
                        <i class="fas fa-arrow-down text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- STATISTIK UTAMA -->
        <div class="grid grid-cols-2 gap-4 md:gap-6" data-aos="fade-up" data-aos-delay="100">
            <!-- Card 1 - Hewan Qurban -->
            <div class="stat-card p-3 md:p-5 text-center">
                <i class="fas fa-drumstick-bite text-2xl md:text-3xl text-emerald-700 mb-1 md:mb-2"></i>
                <div class="stat-number text-emerald-800" id="counterHewan">0</div>
                <p class="font-extrabold text-gray-700 uppercase text-[10px] md:text-xs tracking-wide mt-1">Hewan Qurban</p>
                <span class="text-[10px] md:text-xs font-semibold text-gray-600">{{ $stats['hewan']['sapi'] }} Sapi + {{ $stats['hewan']['kambing'] }} Kambing</span>
            </div>
            
            <!-- Card 2 - Paket Daging -->
            <div class="stat-card p-3 md:p-5 text-center">
                <i class="fas fa-box-open text-2xl md:text-3xl text-blue-700 mb-1 md:mb-2"></i>
                <div class="stat-number text-blue-800" id="counterPaket">0</div>
                <p class="font-extrabold text-gray-700 uppercase text-[10px] md:text-xs tracking-wide mt-1">Paket Daging</p>
                <span class="text-[10px] md:text-xs font-semibold text-gray-600">{{ number_format($stats['paket']) }} Paket</span>
            </div>
        </div>

        <!-- DRAMATIS 1: SHOLAT ID -->
        <div class="dramatic-section" style="background-image: url('{{ $dramatis[1]['image'] ? asset($dramatis[1]['image']) : asset('images/qurban/dramatic/sholat.jpg') }}'); background-position: center 30%;" data-aos="fade-up">
            <div class="dramatic-overlay"></div>
            <div class="dramatic-content py-20 md:py-28 lg:py-36 px-6 text-center text-white">
                <i class="fas fa-mosque text-5xl md:text-7xl mb-4 md:mb-6 drop-shadow-2xl"></i>
                <h2 class="font-serif text-3xl md:text-4xl lg:text-5xl font-black mb-4 md:mb-5">{{ $dramatis[1]['title'] }}</h2>
                <div class="w-20 md:w-28 h-1 bg-amber-400 mx-auto mb-5 md:mb-7"></div>
                <p class="cinematic-quote max-w-3xl mx-auto">{{ $dramatis[1]['quote'] }}</p>
                <div class="mt-8 md:mt-10 text-amber-300 font-bold text-sm md:text-base"><i class="fas fa-camera"></i> {{ $dramatis[1]['stat'] }}</div>
            </div>
        </div>

        <!-- PELAKSANAAN -->
        <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-stretch" data-aos="fade-right">
            
            <!-- Sisi Kiri - Deskripsi -->
            <div class="flex flex-col h-full">
                <div class="badge-gold mb-4 inline-flex w-fit">
                    <i class="fas fa-calendar-check mr-2"></i> LAPORAN RESMI
                </div>
                <h2 class="title-grad text-3xl md:text-4xl lg:text-5xl font-black">Pelaksanaan Idul Adha<br>& Qurban {{ $heroData['subtitle'] }}</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-amber-500 to-emerald-500 my-5 rounded-full"></div>
                
                @php
                    $deskripsi = $pelaksanaan['deskripsi'] ?: $defaultDeskripsi;
                    $deskripsi = '<span class="text-gray-700">' . $deskripsi . '</span>';
                    
                    $highlights = [
                        // Kata pembuka
                        'Alhamdulillah' => '<span class="bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-700 px-2 py-0.5 rounded-md font-bold">Alhamdulillah</span>',
                        'Allah Subhanahu wa Ta\'ala' => '<span class="text-emerald-600 font-bold">Allah Subhanahu wa Ta\'ala</span>',
                        
                        // Data statistik (angka)
                        '8 ekor sapi' => '<span class="bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-bold">8 ekor sapi</span>',
                        '44 ekor kambing' => '<span class="bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-bold">44 ekor kambing</span>',
                        '1.172 paket' => '<span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-bold">1.172 paket</span>',
                        
                        // Nilai-nilai positif
                        'dengan baik, lancar, dan penuh khidmat' => '<span class="text-emerald-600 font-semibold">dengan baik, lancar, dan penuh khidmat</span>',
                        'kebersamaan, gotong royong' => '<span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-semibold">kebersamaan, gotong royong</span>',
                        'partisipasi seluruh pihak' => '<span class="text-emerald-600 font-semibold">partisipasi seluruh pihak</span>',
                        
                        // Nama tempat
                        'Lapangan Tenis TCE' => '<span class="text-amber-600 font-semibold">Lapangan Tenis TCE</span>',
                    ];
                    
                    foreach ($highlights as $word => $replacement) {
                        $deskripsi = str_replace($word, $replacement, $deskripsi);
                    }
                    
                    $deskripsi = nl2br($deskripsi);
                @endphp
                
                <div class="text-gray-700 leading-relaxed font-medium mb-6">
                    {!! $deskripsi !!}
                </div>
                
                <div class="card-modern p-4 md:p-5 mt-auto flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-landmark text-xl md:text-2xl text-emerald-700"></i>
                        </div>
                        <div>
                            <p class="font-black text-gray-800 text-sm md:text-base">{{ $pelaksanaan['masjid_nama'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ $pelaksanaan['masjid_sub'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 md:justify-end">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-tie text-xl md:text-2xl text-amber-700"></i>
                        </div>
                        <div class="md:text-right">
                            <p class="font-serif text-base md:text-xl font-black bg-gradient-to-r from-amber-600 to-orange-500 bg-clip-text text-transparent">{{ $pelaksanaan['ketua_nama'] }}</p>
                            <p class="text-[10px] md:text-xs font-semibold text-gray-500">{{ $pelaksanaan['ketua_jabatan'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sisi Kanan - Galeri -->
            <div class="card-modern p-4 md:p-5 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-amber-100">
                    <span class="text-[10px] text-gray-400">
                        {{ count(array_filter([$pelaksanaan['gambar1'], $pelaksanaan['gambar2'], $pelaksanaan['gambar3'], $pelaksanaan['gambar4']])) }} dokumentasi
                    </span>
                </div>
                
                <div class="glass-gallery flex-1">
                    <!-- BARIS 1: Card 1 (Besar) + Card 2 (Sedang) -->
                    <div class="glass-row-top">
                        <!-- Card 1 - BESAR (Sholat Ied) -->
                        <div class="glass-card" 
                            data-preview-img="{{ $pelaksanaan['gambar1'] ? asset($pelaksanaan['gambar1']) : 'https://picsum.photos/id/20/800/500' }}" 
                            data-preview-caption="{{ $pelaksanaan['caption1'] }}" 
                            data-preview-icon="fas fa-mosque">
                            <div class="glass-image">
                                <img src="{{ $pelaksanaan['gambar1'] ? asset($pelaksanaan['gambar1']) : 'https://picsum.photos/id/20/800/500' }}" alt="Sholat Ied">
                            </div>
                            <div class="glass-caption">
                                <h4><i class="fas fa-mosque"></i> {{ $pelaksanaan['caption1'] }}</h4>
                            </div>
                        </div>
                        
                        <!-- Card 2 - SEDANG (Khotib) -->
                        <div class="glass-card" 
                            data-preview-img="{{ $pelaksanaan['gambar2'] ? asset($pelaksanaan['gambar2']) : 'https://picsum.photos/id/30/600/500' }}" 
                            data-preview-caption="{{ $pelaksanaan['caption2'] }}" 
                            data-preview-icon="fas fa-microphone-alt">
                            <div class="glass-image">
                                <img src="{{ $pelaksanaan['gambar2'] ? asset($pelaksanaan['gambar2']) : 'https://picsum.photos/id/30/600/500' }}" alt="Khatib">
                            </div>
                            <div class="glass-caption">
                                <h4><i class="fas fa-microphone-alt"></i> {{ $pelaksanaan['caption2'] }}</h4>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BARIS 2: Card 3 (Sedang) + Card 4 (Besar) -->
                    <div class="glass-row-bottom">
                        <!-- Card 3 - SEDANG (Penyembelihan) -->
                        <div class="glass-card" 
                            data-preview-img="{{ $pelaksanaan['gambar3'] ? asset($pelaksanaan['gambar3']) : 'https://picsum.photos/id/141/600/500' }}" 
                            data-preview-caption="{{ $pelaksanaan['caption3'] }}" 
                            data-preview-icon="fas fa-hand-holding-heart">
                            <div class="glass-image">
                                <img src="{{ $pelaksanaan['gambar3'] ? asset($pelaksanaan['gambar3']) : 'https://picsum.photos/id/141/600/500' }}" alt="Penyembelihan">
                            </div>
                            <div class="glass-caption">
                                <h4><i class="fas fa-hand-holding-heart"></i> {{ $pelaksanaan['caption3'] }}</h4>
                            </div>
                        </div>
                        
                        <!-- Card 4 - BESAR (Distribusi) -->
                        <div class="glass-card" 
                            data-preview-img="{{ $pelaksanaan['gambar4'] ? asset($pelaksanaan['gambar4']) : 'https://picsum.photos/id/26/800/500' }}" 
                            data-preview-caption="{{ $pelaksanaan['caption4'] }}" 
                            data-preview-icon="fas fa-box-open">
                            <div class="glass-image">
                                <img src="{{ $pelaksanaan['gambar4'] ? asset($pelaksanaan['gambar4']) : 'https://picsum.photos/id/26/800/500' }}" alt="Distribusi">
                            </div>
                            <div class="glass-caption">
                                <h4><i class="fas fa-box-open"></i> {{ $pelaksanaan['caption4'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tambahan caption footer galeri -->
                {{-- <div class="text-center text-[10px] text-gray-400 mt-3 pt-2 border-t border-amber-100">
                    <i class="fas fa-mouse-pointer mr-1"></i> Klik foto untuk memperbesar
                </div> --}}
            </div>

        </div>

        <!-- DRAMATIS 2: PENYEMBELIHAN -->
        <div class="dramatic-section" style="background-image: url('{{ $dramatis[2]['image'] ? asset($dramatis[2]['image']) : asset('images/qurban/dramatic/penyembelihan.jpg') }}');" data-aos="fade-up">
            <div class="dramatic-overlay"></div>
            <div class="dramatic-content py-20 md:py-24 px-6 text-center text-white">
                <i class="fas fa-hand-holding-heart text-5xl md:text-6xl mb-4 md:mb-5"></i>
                <h2 class="font-serif text-3xl md:text-4xl lg:text-5xl font-bold">{{ $dramatis[2]['title'] }}</h2>
                <p class="cinematic-quote max-w-3xl mx-auto mt-4">{{ $dramatis[2]['quote'] }}</p>
                <div class="mt-6 md:mt-8 text-amber-300 font-black text-sm md:text-base">{{ $dramatis[2]['stat'] }}</div>
            </div>
        </div>

        <!-- DATA PEMOTONGAN -->
        <div class="bg-white rounded-2xl shadow-xl border border-teal-100 p-5 md:p-8" data-aos="fade-up">
            <div class="flex flex-wrap justify-between items-center border-b border-teal-100 pb-4 mb-6">
                <div><h3 class="font-serif text-xl md:text-2xl font-black text-gray-800"><i class="fas fa-chart-pie text-teal-600 mr-2"></i> Rincian Pemotongan</h3><p class="text-xs md:text-sm font-medium text-gray-500">Rekap Pelaksanaan Qurban {{ $heroData['subtitle'] }}</p></div>
                <div class="flex gap-2"><span class="bg-gray-100 px-2 py-1 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-bold text-emerald-800"><i class="far fa-calendar-alt"></i> 10 Dzulhijjah {{ $heroData['subtitle'] }}</span></div>
            </div>
            <div class="grid md:grid-cols-2 gap-5 md:gap-6">
                
            {{-- SAPI --}}
            <div class="text-center p-3 md:p-4 bg-blue-50 rounded-xl">
                <div class="text-3xl md:text-4xl font-black text-blue-800">{{ $stats['hewan']['sapi'] }}</div>
                <p class="font-black text-sm md:text-base text-gray-700">Sapi Kolektif</p>
                @if($pemotongan['sapi_is_range'])
                    <span class="text-[10px] md:text-xs font-semibold block mt-1 text-gray-700">
                        📊 Bobot : {{ $pemotongan['sapi_berat_kg'] }} kg/ekor
                    </span>
                    <span class="text-[9px] text-gray-600 block">
                        (Rata-rata ± {{ number_format($pemotongan['sapi_avg']) }} kg)
                    </span>
                @else
                    <span class="text-[10px] md:text-xs font-semibold block mt-1 text-gray-700">
                        🥩 Rata-rata {{ number_format($pemotongan['sapi_berat_kg']) }} kg/ekor
                    </span>
                @endif
            </div>

            {{-- KAMBING --}}
            <div class="text-center p-3 md:p-4 bg-amber-50 rounded-xl">
                <div class="text-3xl md:text-4xl font-black text-amber-800">{{ $stats['hewan']['kambing'] }}</div>
                <p class="font-black text-sm md:text-base text-gray-700">Kambing</p>
                @if($pemotongan['kambing_is_range'])
                    <span class="text-[10px] md:text-xs font-semibold block mt-1 text-gray-700">
                        📊 Rentang berat: {{ $pemotongan['kambing_berat_kg'] }} kg/ekor
                    </span>
                    <span class="text-[9px] text-gray-600 block">
                        (Rata-rata ± {{ number_format($pemotongan['kambing_avg']) }} kg)
                    </span>
                @else
                    <span class="text-[10px] md:text-xs font-semibold block mt-1 text-gray-700">
                        🥩 Rata-rata {{ number_format($pemotongan['kambing_berat_kg']) }} kg/ekor
                    </span>
                @endif
            </div>
                
            </div>
            <div class="mt-5 md:mt-6 bg-gradient-to-r from-emerald-700 to-teal-600 rounded-xl p-4 md:p-5 text-white flex flex-wrap justify-between items-center gap-3">
                <div class="flex gap-3 md:gap-4 flex-wrap">
                    <div><i class="fas fa-cow text-xl md:text-2xl"></i> <span class="font-black ml-1 text-sm md:text-base">Total Hewan: {{ $stats['hewan']['total'] }} Ekor</span></div>
                </div>
                <div><i class="fas fa-check-circle"></i> <span class="text-sm md:text-base">Dilaksanakan Sesuai Syariat, InsyaAllah</span></div>
            </div>
        </div>

        <!-- PENERIMA MANFAAT -->
        <div class="bg-white rounded-2xl shadow-xl p-5 md:p-8 border-t-8 border-amber-500" data-aos="fade-up">
            <div class="text-center mb-6 md:mb-8">
                <div class="inline-flex bg-emerald-100 text-emerald-800 px-4 py-1 md:px-5 md:py-1.5 rounded-full font-black text-[10px] md:text-sm">
                    <i class="fas fa-chart-pie mr-1 md:mr-2"></i> DISTRIBUSI QURBAN {{ $heroData['subtitle'] }}
                </div>
                <h2 class="font-serif text-2xl md:text-3xl lg:text-4xl font-black mt-3 text-gray-800">Data Distribusi Qurban</h2>
                <p class="text-gray-600 font-medium text-xs md:text-sm">Transparansi penyaluran daging (tanpa duplikasi data)</p>
            </div>
            
            <!-- Distribusi 3 kolom - DATA DARI CONTROLLER -->
            {{-- @php
                $distribusiCount = count($distribusi);
                
                // Tentukan grid columns berdasarkan jumlah
                if ($distribusiCount == 1) {
                    $gridClass = 'grid grid-cols-1 max-w-md mx-auto gap-4 md:gap-6 mb-8 md:mb-10';
                } elseif ($distribusiCount == 2) {
                    $gridClass = 'grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-8 md:mb-10';
                } elseif ($distribusiCount == 3) {
                    $gridClass = 'grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-10';
                } else {
                    $gridClass = 'grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-10';
                }
            @endphp

            <div class="{{ $gridClass }}">
                @foreach($distribusi as $item)
                <div class="bg-{{ $item['color'] }}-50 rounded-xl p-3 md:p-5 text-center">
                    <i class="fas {{ $item['icon'] }} text-2xl md:text-3xl text-{{ $item['color'] }}-700 mb-1 md:mb-2"></i>
                    <p class="font-black text-sm md:text-lg">{{ $item['label'] }}</p>
                    <span class="text-2xl md:text-3xl font-black text-{{ $item['color'] }}-800">{{ number_format($item['value']) }}</span>
                    <p class="text-[10px] md:text-sm font-semibold">Paket ({{ $item['percentage'] }}%)</p>
                </div>
                @endforeach
            </div> --}}
            <!-- Rings - dinamis -->
            <div class="ring-container grid grid-cols-1 md:grid-cols-{{ count($penerima) }} gap-4 md:gap-6">
                @php $ringColors = ['emerald', 'teal', 'blue', 'amber', 'purple']; @endphp
                @foreach($penerima as $ring)
                    @php 
                        $colorIndex = $loop->index % count($ringColors);
                        $color = $ringColors[$colorIndex];
                    @endphp
                    <div class="ring-card flex flex-col h-full">
                        <div class="ring-header bg-gradient-to-r from-{{ $color }}-800 to-{{ $color }}-600 text-white p-3 md:p-4 rounded-t-xl">
                            <div class="flex items-center gap-2">
                                <i class="fas {{ $ring['icon'] ?? 'fa-building' }} text-sm md:text-base"></i>
                                <h4 class="font-bold text-sm md:text-base lg:text-lg break-words">{{ $ring['title'] }}</h4>
                            </div>
                        </div>
                        <div class="ring-body flex-1 p-3 md:p-4 bg-white border-x border-b rounded-b-xl">
                            <ul class="list-distribution-single space-y-2 md:space-y-3">
                                @foreach($ring['items'] as $ringItem)
                                    <li class="border-b border-gray-200 pb-2 last:border-b-0 last:pb-0" style="display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: start;">
                                        <span class="text-gray-600 text-xs md:text-sm break-words">{{ $ringItem['label'] }}</span>
                                        <span class="bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap justify-self-end">{{ $ringItem['value'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="ring-footer bg-{{ $color }}-50 text-{{ $color }}-700 p-3 md:p-4 rounded-b-xl mt-auto">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-users text-xs md:text-sm"></i>
                                    <span class="font-semibold text-xs md:text-sm">TOTAL:</span>
                                </div>
                                <span class="font-bold text-sm md:text-base">{{ $ring['total'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Setelah rings, tampilkan grand total -->
            <div class="mt-6 md:mt-8 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-4 md:p-5 border border-emerald-200">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-line text-xl md:text-2xl text-emerald-600"></i>
                        <span class="font-black text-gray-800 text-sm md:text-lg uppercase tracking-wide">TOTAL DISTRIBUSI</span>
                    </div>
                    <div class="text-center sm:text-right">
                        <span class="font-black text-emerald-700 text-xl md:text-3xl">{{ number_format($grandTotalPenerima) }} penerima</span>
                        <p class="text-xs text-gray-500 mt-1">Dari seluruh Ring I, II, dst</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DRAMATIS 3: SENYUMAN -->
        <div class="dramatic-section" style="background-image: url('{{ $dramatis[3]['image'] ? asset($dramatis[3]['image']) : asset('images/qurban/dramatic/senyum.jpg') }}');" data-aos="fade-up">
            <div class="dramatic-overlay"></div>
            <div class="dramatic-content py-20 md:py-28 px-6 text-center text-white">
                <i class="fas fa-smile-wink text-5xl md:text-7xl mb-4 md:mb-5"></i>
                <h2 class="font-serif text-3xl md:text-4xl lg:text-5xl font-bold">{{ $dramatis[3]['title'] }}</h2>
                <p class="cinematic-quote max-w-3xl mx-auto mt-4">{{ $dramatis[3]['quote'] }}</p>
                <div class="mt-6 md:mt-8 text-amber-300 font-black text-sm md:text-base">{{ $dramatis[3]['stat'] }}</div>
            </div>
        </div>

        <!-- GALERI FOTO -->
        <div class="card-modern p-4 md:p-6" data-aos="fade-up">
            <div class="flex justify-between items-center mb-4 md:mb-6">
                <div>
                    <h3 class="font-serif text-xl md:text-2xl font-black text-gray-800">📸 Galeri Momen Idul Adha {{ $heroData['subtitle'] }}</h3>
                    <p class="text-[10px] md:text-sm font-medium text-gray-500">Total {{ count($galleryImages) + count($additionalImages) }} foto dokumentasi</p>
                </div>
                <i class="fas fa-camera-retro text-2xl md:text-3xl text-amber-500"></i>
            </div>
            
            <!-- Petunjuk Khusus Mobile -->
            <div class="md:hidden mb-3 bg-amber-50 rounded-xl p-2 flex items-center justify-center gap-2 text-xs text-amber-700 border border-amber-200">
                <i class="fas fa-fingerprint text-amber-500"></i>
                <span class="font-medium">✦ Ketuk foto untuk memperbesar ✦</span>
                <i class="fas fa-expand-alt text-amber-500"></i>
            </div>
            
            <!-- Petunjuk Desktop (hover style) -->
            <div class="hidden md:flex justify-end mb-2">
                <div class="text-[10px] text-gray-400 flex items-center gap-1">
                    <i class="fas fa-mouse-pointer"></i>
                    <span>hover pada foto untuk memperbesar</span>
                </div>
            </div>
            
            <div class="gallery-grid" id="galleryContainer">
                @foreach($galleryImages as $index => $image)
                    @if($index < 10)
                        <div class="gallery-item {{ $image['type'] ?? 'square' }}" data-index="{{ $index }}">
                            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? 'Foto galeri' }}">
                            <div class="gallery-tooltip">
                                <i class="fas fa-expand-alt"></i>
                                <span>Klik perbesar</span>
                            </div>
                        </div>
                    @endif
                @endforeach
                
                <!-- More overlay button -->
                <div class="gallery-item square relative" id="morePhotosTrigger" data-index="{{ count($galleryImages) }}">
                    <img src="{{ count($galleryImages) > 0 ? $galleryImages[0]['url'] : asset('images/qurban/gallery/default.jpg') }}" alt="+{{ count($additionalImages) }} Foto Lainnya">
                    <div class="gallery-more-overlay">
                        <i class="fas fa-images"></i>
                        <span>+{{ count($additionalImages) }} Foto</span>
                    </div>
                    <div class="gallery-tooltip">
                        <i class="fas fa-expand-alt"></i>
                        <span>Lihat semua</span>
                    </div>
                </div>
                
                <!-- Placeholder untuk menjaga grid rapi -->
                @php
                    $displayedCount = min(10, count($galleryImages)) + 1;
                    $placeholders = 4 - ($displayedCount % 4);
                    if($placeholders == 4) $placeholders = 0;
                @endphp
                @for($i = 0; $i < $placeholders; $i++)
                    <div class="gallery-item square" style="opacity: 0; cursor: default; pointer-events: none;"></div>
                @endfor
            </div>
            
            <!-- Info total dengan icon tap -->
            <p class="text-center text-[10px] md:text-xs text-gray-500 mt-4 md:mt-5 font-medium flex items-center justify-center gap-1 flex-wrap">
                <i class="fas fa-images text-amber-400"></i>
                <span>{{ count($galleryImages) + count($additionalImages) }} foto dokumentasi Idul Adha {{ $heroData['subtitle'] }}</span>
                <span class="hidden md:inline">•</span>
                <span class="inline-flex items-center gap-1 text-amber-500 md:hidden">
                    <i class="fas fa-hand-peace-o text-[10px]"></i>
                    tap foto
                </span>
            </p>
        </div>

        <!-- Modal Gallery -->
        <div id="galleryModal" class="modal-gallery">
            <div class="modal-container">
                <!-- Tombol Close -->
                <div class="modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </div>
                
                <!-- Tombol Download -->
                <div class="modal-download" id="modalDownload">
                    <i class="fas fa-download"></i>
                </div>
                
                <!-- Area Gambar -->
                <div class="modal-image-area" id="modalImageArea">
                    <img id="modalImage" src="" alt="Gallery">
                </div>
                
                <!-- Footer: Tombol Navigasi + Counter -->
                <div class="modal-footer">
                    <button id="prevBtn" class="modal-nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="modal-counter" id="modalCounter">1 / 10</div>
                    <button id="nextBtn" class="modal-nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- RINCIAN PENGELUARAN & PENGELOLAAN DANA QURBAN -->
        <div class="bg-white rounded-2xl shadow-xl border border-teal-100 overflow-hidden" data-aos="fade-up">
            <div class="bg-gradient-to-r from-emerald-800 to-teal-700 px-5 md:px-8 py-4 md:py-5">
                <h2 class="font-serif text-xl md:text-2xl lg:text-3xl font-black text-white flex items-center gap-3">
                    <i class="fas fa-chart-line text-amber-300"></i>
                    Rincian Pengeluaran & Pengelolaan Dana Qurban
                </h2>
                <p class="text-emerald-100 text-xs md:text-sm mt-1">Transparansi penuh atas setiap rupiah yang diamanahkan</p>
            </div>
            
            <div class="p-5 md:p-8 space-y-6">
                
            <!-- PENERIMAAN DARI PESERTA QURBAN -->
            <div class="finance-section">
                <div class="finance-section-header">
                    <h3 class="font-black text-gray-800 text-base md:text-lg flex items-center gap-2">
                        <i class="fas fa-money-bill-wave text-emerald-600"></i>
                        1A. PENERIMAAN DARI PESERTA QURBAN
                    </h3>
                </div>
                <div class="finance-section-body">
                    @forelse($keuangan['penerimaan_peserta'] ?? [] as $item)
                    <div class="finance-row">
                        <span class="finance-label">{{ $item['label'] ?? '-' }}</span>
                        <span class="finance-amount">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Belum ada data penerimaan dari peserta</p>
                    @endforelse
                    @if(count($keuangan['penerimaan_peserta'] ?? []) > 0)
                    <div class="total-box mt-3">
                        <div class="total-box-inner">
                            <span class="font-black text-gray-800">Total Penerimaan Peserta</span>
                            <span class="font-black text-emerald-800 text-lg md:text-xl">Rp {{ number_format($keuangan['total_penerimaan_peserta'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- PENERIMAAN INFAQ -->
            <div class="finance-section">
                <div class="finance-section-header">
                    <h3 class="font-black text-gray-800 text-base md:text-lg flex items-center gap-2">
                        <i class="fas fa-hand-holding-heart text-emerald-600"></i>
                        1B. PENERIMAAN INFAQ
                    </h3>
                </div>
                <div class="finance-section-body">
                    @forelse($keuangan['penerimaan_infaq'] ?? [] as $item)
                    <div class="finance-row">
                        <span class="finance-label">{{ $item['label'] ?? '-' }}</span>
                        <span class="finance-amount">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Belum ada data penerimaan infaq</p>
                    @endforelse
                    @if(count($keuangan['penerimaan_infaq'] ?? []) > 0)
                    <div class="total-box mt-3">
                        <div class="total-box-inner">
                            <span class="font-black text-gray-800">Total Penerimaan Infaq</span>
                            <span class="font-black text-emerald-800 text-lg md:text-xl">Rp {{ number_format($keuangan['total_penerimaan_infaq'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- TOTAL SELURUH PENERIMAAN -->
            <div class="finance-section">
                <div class="finance-section-header" style="background: linear-gradient(95deg, #e6f7f0, #d1fae5);">
                    <h3 class="font-black text-emerald-800 text-base md:text-lg flex items-center gap-2">
                        <i class="fas fa-chart-line text-emerald-700"></i>
                        TOTAL SELURUH PENERIMAAN
                    </h3>
                </div>
                <div class="finance-section-body">
                    <div class="total-box" style="background: #e6f7f0;">
                        <div class="total-box-inner">
                            <span class="font-black text-gray-800">Grand Total Penerimaan</span>
                            <span class="font-black text-emerald-800 text-xl md:text-2xl">Rp {{ number_format($keuangan['total_penerimaan'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-2 text-center">
                            (Penerimaan Peserta + Penerimaan Infaq)
                        </div>
                    </div>
                </div>
            </div>

                <!-- PENGELUARAN -->
                <div class="finance-section">
                    <div class="finance-section-header">
                        <h3 class="font-black text-gray-800 text-base md:text-lg flex items-center gap-2">
                            <i class="fas fa-receipt text-amber-600"></i>
                            2. PENGELUARAN
                        </h3>
                    </div>
                    <div class="finance-section-body">
                        @forelse($keuangan['pengeluaran'] as $item)
                        <div class="finance-row">
                            <span class="finance-label">{{ $item['label'] }}</span>
                            <span class="finance-amount">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                        </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">Belum ada data pengeluaran</p>
                        @endforelse
                        @if(count($keuangan['pengeluaran']) > 0)
                        <div class="total-box mt-3">
                            <div class="total-box-inner">
                                <span class="font-black text-gray-800">Total Pengeluaran</span>
                                <span class="font-black text-amber-800 text-lg md:text-xl">Rp {{ number_format($keuangan['total_pengeluaran'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- SISA DANA -->
                <div class="bg-gradient-to-r from-amber-100 to-orange-50 rounded-xl p-4 md:p-6 border-l-8 border-amber-500">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                        <div>
                            <span class="font-black text-gray-800 text-base md:text-lg"><i class="fas fa-coins text-amber-600 mr-2"></i>Sisa Dana / Saldo Akhir</span>
                            <p class="text-gray-500 text-xs mt-1">(Total Penerimaan - Total Pengeluaran)</p>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-amber-700 text-xl md:text-2xl">Rp {{ number_format($keuangan['sisa_dana'], 0, ',', '.') }}</span>
                            {{-- <p class="text-emerald-600 text-xs font-semibold mt-1">✓ Akan dialokasikan untuk kegiatan sosial berikutnya</p> --}}
                        </div>
                    </div>
                </div>

                <!-- KETERANGAN -->
                {{-- <div class="bg-blue-50 rounded-xl p-4 md:p-5 border border-blue-200">
                    <div class="flex gap-3">
                        <i class="fas fa-info-circle text-blue-500 text-lg flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold text-gray-800 text-sm md:text-base mb-3">Keterangan:</p>
                            <ul class="keterangan-list">
                                <li><i class="fas fa-check-circle text-xs"></i> {{ $stats['hewan']['sapi'] }} ekor sapi qurban merupakan hewan kolektif dari peserta</li>
                                <li><i class="fas fa-check-circle text-xs"></i> {{ $stats['hewan']['kambing'] }} ekor kambing dari peserta + 10 ekor kambing hibah/sumbangan</li>
                                <li><i class="fas fa-check-circle text-xs"></i> Total hewan qurban: {{ $stats['hewan']['sapi'] }} sapi + {{ $stats['hewan']['kambing'] }} kambing = {{ $stats['hewan']['total'] }} ekor</li>
                                <li><i class="fas fa-check-circle text-xs"></i> Seluruh dana telah dikelola secara amanah dan transparan</li>
                            </ul>
                        </div>
                    </div>
                </div> --}}

            </div>
        </div>

        <!-- CARD TERIMA KASIH - BURGUNDY PEKAT -->
        <div class="rounded-2xl p-6 md:p-10 text-center text-white relative overflow-hidden backdrop-blur-md bg-white/10 border border-white/40 shadow-2xl" 
            data-aos="fade-up"
            style="background: linear-gradient(135deg, #991b1b, #7f1d1d, #b91c1c);">
            <div class="absolute top-0 right-0 w-48 h-48 bg-red-400 rounded-full opacity-30 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-orange-400 rounded-full opacity-20 blur-2xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white rounded-full opacity-10 blur-3xl"></div>
            
            <div class="relative z-10 max-w-3xl mx-auto">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-1.5 mb-6 border border-white/40">
                    <i class="fas fa-heart text-yellow-300 text-xs"></i>
                    <span class="text-xs font-semibold tracking-wide">ALHAMDULILLAH</span>
                </div>
                
                <i class="fas fa-hand-holding-heart text-6xl md:text-7xl mb-5 text-yellow-300 drop-shadow-lg"></i>
                
                <h3 class="font-serif text-3xl md:text-4xl lg:text-5xl font-black mb-4 drop-shadow-md">{{ $thankyou['title'] }}</h3>
                
                <div class="w-24 h-1 bg-yellow-400 mx-auto mb-6 rounded-full"></div>
                
                <!-- Pesan - RATA TENGAH -->
                <div class="text-base md:text-lg text-white/95 leading-relaxed mb-6">
                    {!! nl2br(e($thankyou['message'])) !!}
                </div>
                
                <!-- Ayat -->
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 md:p-5 max-w-2xl mx-auto border border-white/30">
                    <i class="fas fa-quote-right text-yellow-300 text-sm mr-2"></i>
                    <p class="text-sm md:text-base italic text-white/90">{!! nl2br(e($thankyou['hadits'])) !!}</p>
                </div>
                
                <!-- Bintang -->
                <div class="flex justify-center gap-2 mt-6">
                    <i class="fas fa-star text-yellow-300 text-sm drop-shadow"></i>
                    <i class="fas fa-star text-yellow-300 text-sm drop-shadow"></i>
                    <i class="fas fa-star text-yellow-300 text-sm drop-shadow"></i>
                    <i class="fas fa-star text-yellow-300 text-sm drop-shadow"></i>
                    <i class="fas fa-star text-yellow-300 text-sm drop-shadow"></i>
                </div>
                
                <!-- Footer card -->
                <div class="mt-6 pt-4 border-t border-white/30">
                    <p class="text-xs text-white/70">📅 Laporan Qurban {{ $heroData['subtitle'] }} | {{ $heroData['masjid'] }}</p>
                </div>
            </div>
        </div>

        <!-- FOOTER - NAVY BLUE PEKAT (SIMPLE: SOSMED + COPYRIGHT) -->
        {{-- <footer class="text-center py-6 md:py-8 rounded-2xl relative overflow-hidden shadow-lg" 
                data-aos="fade-up"
                style="background: linear-gradient(135deg, #1e3a8a, #1e40af, #2563eb); backdrop-filter: blur(8px); border: 1px solid rgba(96,165,250,0.5);">
            
            <div class="absolute top-0 right-0 w-40 h-40 bg-blue-300 rounded-full opacity-15 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-indigo-300 rounded-full opacity-10 blur-2xl"></div>
            
            <div class="relative z-10 px-4">
                <!-- Decorative line -->
                <div class="flex justify-center gap-2 mb-4">
                    <div class="w-8 h-0.5 bg-blue-300 rounded-full"></div>
                    <div class="w-12 h-0.5 bg-blue-200 rounded-full"></div>
                    <div class="w-8 h-0.5 bg-blue-300 rounded-full"></div>
                </div>
                
                <!-- Social Media -->
                <div class="flex justify-center gap-5 md:gap-6">
                    @if($footer['instagram'])
                    <a href="{{ $footer['instagram'] }}" target="_blank" class="w-9 h-9 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-yellow-400 hover:text-blue-900 transition-all duration-300 border border-white/40">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>
                    @endif
                    @if($footer['whatsapp'])
                    <a href="{{ $footer['whatsapp'] }}" target="_blank" class="w-9 h-9 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-yellow-400 hover:text-blue-900 transition-all duration-300 border border-white/40">
                        <i class="fab fa-whatsapp text-sm"></i>
                    </a>
                    @endif
                    @if($footer['email'])
                    <a href="mailto:{{ $footer['email'] }}" class="w-9 h-9 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-yellow-400 hover:text-blue-900 transition-all duration-300 border border-white/40">
                        <i class="fas fa-envelope text-sm"></i>
                    </a>
                    @endif
                </div>
                
                <!-- Copyright -->
                <div class="mt-5 pt-3 border-t border-white/20">
                    <p class="text-white/50 text-[10px]">© {{ date('Y') }} {{ $heroData['masjid'] }} — Taman Cipulir Estate</p>
                </div>
            </div>
        </footer> --}}
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });
        
        // Animasi counter
        const animateNum = (id, target) => { 
            let el = document.getElementById(id); 
            if(!el) return; 
            let curr = 0; 
            let step = Math.ceil(target / 60); 
            let intv = setInterval(() => { 
                curr += step; 
                if(curr >= target) { 
                    el.innerText = target.toLocaleString('id-ID'); 
                    clearInterval(intv); 
                } else { 
                    el.innerText = Math.floor(curr).toLocaleString('id-ID'); 
                } 
            }, 25); 
        };
        
        const observer = new IntersectionObserver((e) => { 
            if(e[0].isIntersecting) { 
                animateNum('counterHewan', {{ $stats['hewan']['total'] }}); 
                animateNum('counterPaket', {{ $stats['paket'] }}); 
                animateNum('counterMustahik', {{ $stats['mustahik'] }});
                observer.disconnect(); 
            } 
        }, { threshold: 0.3 });
        
        const statSec = document.querySelector('.grid-cols-2.md\\:gap-6');
        if(statSec) observer.observe(statSec);
        
        // Animasi pulse pada gallery item pertama kali load
        document.addEventListener('DOMContentLoaded', function() {
            // Tambah class pulse pada 3 gallery item pertama
            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach((item, index) => {
                if (index < 3) {
                    item.classList.add('pulse-animation');
                    // Hapus class setelah animasi selesai
                    setTimeout(() => {
                        item.classList.remove('pulse-animation');
                    }, 3000);
                }
            });
            
            // Tambah tooltip pada setiap gallery item
            galleryItems.forEach(item => {
                // Cek apakah sudah ada tooltip
                if (!item.querySelector('.gallery-tooltip')) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'gallery-tooltip';
                    tooltip.innerHTML = '<i class="fas fa-expand-alt"></i> <span>Klik perbesar</span>';
                    item.appendChild(tooltip);
                }
            });
        });

        // Gallery images untuk modal
        const allGalleryImages = [
            @foreach($galleryImages as $img)
                "{{ $img['url'] }}",
            @endforeach
            @foreach($additionalImages as $img)
                "{{ $img }}",
            @endforeach
        ];
        
        // ============ MODAL GALLERY ============
        let currentIndex = 0;

        // DOM Elements
        const modal = document.getElementById('galleryModal');
        const modalImage = document.getElementById('modalImage');
        const modalCounter = document.getElementById('modalCounter');
        const closeBtn = document.getElementById('modalClose');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const downloadBtn = document.getElementById('modalDownload');

        // ============ FUNGSI DOWNLOAD ============
        function downloadImage() {
            const imageUrl = modalImage.src;
            const fileName = `qurban_photo_${currentIndex + 1}.jpg`;
            
            fetch(imageUrl)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => {
                    console.error('Download gagal:', error);
                    window.open(imageUrl, '_blank');
                });
        }

        // ============ FUNGSI MODAL ============
        function openModal(index) {
            currentIndex = index;
            modalImage.src = allGalleryImages[currentIndex];
            modalCounter.innerText = `${currentIndex + 1} / ${allGalleryImages.length}`;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open'); // Tambah class ke body
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            document.body.classList.remove('modal-open'); // Hapus class dari body
        }

        function nextImage() { 
            currentIndex = (currentIndex + 1) % allGalleryImages.length; 
            modalImage.src = allGalleryImages[currentIndex]; 
            modalCounter.innerText = `${currentIndex + 1} / ${allGalleryImages.length}`;
        }

        function prevImage() { 
            currentIndex = (currentIndex - 1 + allGalleryImages.length) % allGalleryImages.length; 
            modalImage.src = allGalleryImages[currentIndex]; 
            modalCounter.innerText = `${currentIndex + 1} / ${allGalleryImages.length}`;
        }

        // ============ EVENT LISTENERS ============
        // Gallery Items
        const galleryItems = document.querySelectorAll('.gallery-item');
        galleryItems.forEach((item) => {
            const idx = item.getAttribute('data-index');
            if(idx && idx !== '' && !isNaN(parseInt(idx))) {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openModal(parseInt(idx));
                });
            }
        });

        // Tombol More
        const moreBtn = document.getElementById('morePhotosTrigger');
        if(moreBtn) {
            moreBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                openModal({{ count($galleryImages) }});
            });
        }

        // Navigation Buttons
        closeBtn.addEventListener('click', closeModal);
        prevBtn.addEventListener('click', prevImage);
        nextBtn.addEventListener('click', nextImage);

        // Download Button
        if (downloadBtn) {
            downloadBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                downloadImage();
            });
        }

        // Close on outside click
        modal.addEventListener('click', (e) => { 
            if(e.target === modal) closeModal(); 
        });

        // Keyboard Navigation
        document.addEventListener('keydown', (e) => {
            if(!modal.classList.contains('active')) return;
            if(e.key === 'Escape') closeModal();
            if(e.key === 'ArrowLeft') prevImage();
            if(e.key === 'ArrowRight') nextImage();
        });
        // Dropdown tahun
        const tahunSelect = document.getElementById('tahunSelect');
        if(tahunSelect) {
            tahunSelect.addEventListener('change', function() {
                window.location.href = this.value;
            });
        }
    </script>
@endpush