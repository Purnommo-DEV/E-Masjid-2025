<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - E-Masjid</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('vendor/material-ui/img/apple-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('vendor/material-ui/img/favicon.png') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>

    <!-- Core CSS -->
    <link href="{{ asset('vendor/material-ui/css/nucleo-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/material-ui/css/nucleo-svg.css') }}" rel="stylesheet">
    <link id="pagestyle" href="{{ asset('vendor/material-ui/css/material-dashboard.css') }}" rel="stylesheet">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap-editable/css/bootstrap-editable.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.0/min/dropzone.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css"
          integrity="sha512-O03ntXoVqaGUTAeAmvQ2YSzkCvclZEcPQu1eqloPaHfJ5RuNGiS4l+3duaidD801P50J28EHyonCV06CUlTSag=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css"
          integrity="sha512-mSYUmp1HYZDFaVKK//63EcZq4iFWFjxSL+Z3T/aCt4IO9Cejm03q3NKKYN6pFQzY0SBOr8h+eCIAZHPXcpZaNw=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('vendor/material-ui/js/plugins/datatables/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/material-ui/css/jquery.dataTables.min.css') }}">

    <!-- SweetAlert -->
    <link rel="stylesheet" href="{{ asset('Front/asset/css/sweetalert2.min.css') }}">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/solid.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Nepcha Analytics -->
    <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>

    <!-- Custom CSS -->
    @stack('style')

    <style>
        .btn-sm {
            font-size: 0.7rem !important;
            border-radius: 0.8rem !important;
        }

        .modal-content, .card {
            border-radius: 0.8rem !important;
        }

        .loading {
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            width: 100px;
            color: #FFF;
            margin: auto;
            transform: translateY(-50%);
        }

        .loading span {
            position: absolute;
            height: 10px;
            width: 84px;
            top: 50px;
            overflow: hidden;
        }

        .loading span > i {
            position: absolute;
            height: 4px;
            width: 4px;
            border-radius: 50%;
            animation: wait 4s infinite;
        }

        .loading span > i:nth-of-type(1) {
            left: -28px;
            background: yellow;
        }

        .loading span > i:nth-of-type(2) {
            left: -21px;
            animation-delay: 0.8s;
            background: lightgreen;
        }

        @keyframes wait {
            0% { left: -7px; }
            30% { left: 52px; }
            60% { left: 22px; }
            100% { left: 100px; }
        }
    </style>
</head>
