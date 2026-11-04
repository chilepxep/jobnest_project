@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@extends('layouts/commonMaster')

@php
/* Display elements */
$contentNavbar = $contentNavbar ?? true;
$containerNav = $containerNav ?? 'container-xxl';
$isNavbar = $isNavbar ?? true;
$isMenu = $isMenu ?? true;
$isFlex = $isFlex ?? false;
$isFooter = $isFooter ?? true;
$customizerHidden = $customizerHidden ?? '';

/* HTML Classes */
$navbarDetached = 'navbar-detached';
$menuFixed = isset($configData['menuFixed']) ? $configData['menuFixed'] : '';
if (isset($navbarType)) {
$configData['navbarType'] = $navbarType;
}
$navbarType = isset($configData['navbarType']) ? $configData['navbarType'] : '';
$footerFixed = isset($configData['footerFixed']) ? $configData['footerFixed'] : '';
$menuCollapsed = isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '';

/* Content classes */
$container = ($container ?? 'container-xxl');

@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }}">
    <div class="layout-container">

        @if ($isMenu)
        @include('layouts/sections/menu/verticalMenu')
        @endif


        <!-- Layout page -->
        <div class="layout-page">

            {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}
            {{-- <x-banner /> --}}

            <!-- BEGIN: Navbar-->
            @if ($isNavbar)
            @include('layouts/sections/navbar/navbar')
            @endif
            <!-- END: Navbar-->


            <!-- Content wrapper -->
            <div class="content-wrapper">

                <!-- Content -->
                @if ($isFlex)
                <div class="{{ $container }} d-flex align-items-stretch flex-grow-1 p-0">
                    @else
                    <div class="{{ $container }} flex-grow-1 container-p-y">
                        @endif

                        @yield('content')

                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    @if ($isFooter)
                    @include('layouts/sections/footer/footer')
                    @endif
                    <!-- / Footer -->
                    <div class="content-backdrop fade"></div>
                </div>
                <!--/ Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        @if ($isMenu)
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        @endif
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->
</div>
@endsection


<?php if (session('success') || session('error')): ?>
<style>
.jobnest-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    min-width: 320px;
    max-width: 420px;
    background: #dbdee0;
    padding: 16px 18px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(67, 89, 113, 0.15);
    border: 1px solid rgba(67, 89, 113, 0.08);
    border-left: 5px solid;
    overflow: hidden;
    transform: translateX(120%);
    transition: transform 0.35s cubic-bezier(0.21, 1.02, 0.73, 1), opacity 0.3s ease;
}

.jobnest-toast.show {
    transform: translateX(0);
}

.jobnest-toast.is-success {
    border-left-color: #28c76f;
}

.jobnest-toast.is-error {
    border-left-color: #ea5455;
}

.jobnest-toast-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.is-success .jobnest-toast-icon {
    background: #e8fadf;
    color: #28c76f;
}

.is-error .jobnest-toast-icon {
    background: #ffe0db;
    color: #ea5455;
}

.jobnest-toast-body {
    flex: 1;
    padding-right: 8px;
}

.jobnest-toast-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #32475c;
    margin-bottom: 3px;
    line-height: 1.3;
}

.jobnest-toast-msg {
    font-size: 0.85rem;
    color: #697a8d;
    margin: 0;
    line-height: 1.45;
}

.jobnest-toast-close {
    background: transparent;
    border: 0;
    color: #a1acb8;
    font-size: 18px;
    line-height: 1;
    padding: 2px;
    cursor: pointer;
    transition: color 0.15s;
}

.jobnest-toast-close:hover {
    color: #566a7f;
}

.jobnest-toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    transform-origin: left;
    animation: toastProgress 4s linear forwards;
}

.is-success .jobnest-toast-progress {
    background: #28c76f;
}

.is-error .jobnest-toast-progress {
    background: #ea5455;
}

.jobnest-toast:hover .jobnest-toast-progress {
    animation-play-state: paused;
}

@keyframes toastProgress {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}
</style>

<?php
    $isSuccess = session()->has('success');
    $toastType = $isSuccess ? 'is-success' : 'is-error';
    $toastTitle = $isSuccess ? 'Thành công!' : 'Đã xảy ra lỗi!';
    $toastIcon = $isSuccess ? 'bx-check-circle' : 'bx-error-circle';
    $toastMessage = session('success') ?? session('error');
?>

<div id="jobnestToast" class="jobnest-toast {{ $toastType }}">
    <div class="jobnest-toast-icon">
        <i class="bx {{ $toastIcon }}"></i>
    </div>
    <div class="jobnest-toast-body">
        <div class="jobnest-toast-title">{{ $toastTitle }}</div>
        <p class="jobnest-toast-msg">{{ $toastMessage }}</p>
    </div>
    <button type="button" class="jobnest-toast-close" id="jobnestToastClose" aria-label="Close">
        <i class="bx bx-x"></i>
    </button>
    <div class="jobnest-toast-progress" id="jobnestToastBar"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toast = document.getElementById('jobnestToast');
    const closeBtn = document.getElementById('jobnestToastClose');
    const bar = document.getElementById('jobnestToastBar');
    if (!toast) return;

    requestAnimationFrame(function() {
        toast.classList.add('show');
    });

    function hideToast() {
        toast.classList.remove('show');
        setTimeout(function() {
            toast.remove();
        }, 350);
    }

    bar.addEventListener('animationend', hideToast);
    closeBtn.addEventListener('click', hideToast);
});
</script>
<?php endif; ?>