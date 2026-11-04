@extends('layouts.contentNavbarLayout')

@section('title', 'Bảng điều khiển Nhà tuyển dụng')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection


@section('content')
<div class="row">
    <!-- chào mừng -->
    <div class="col-xxl-8 mb-6 order-0">
        <div class="card">
            <div class="d-flex align-items-start row">
                <div class="col-sm-7">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-3">Xin chào Nhà tuyển dụng! 🎉</h5>
                        <p class="mb-3">Bạn đã có 30 tin tuyển dụng
                        </p>

                        <p class="mb-3">Hãy kiểm tra các tin tuyển dụng để
                            không bỏ lỡ bất kì ứng viên tiềm năng nào nhé!
                        </p>


                        <a href="{{ route('employer.jobs.create') }}" class="btn  btn-outline-success">Đăng tin tuyển
                            dụng</a>
                    </div>
                </div>
                <div class="col-sm-5 text-center text-sm-left">
                    <div class="card-body pb-0 px-0 px-md-6">
                        <img src="{{ asset('admin_assets/img/illustrations/man-with-laptop.png') }}" height="175"
                            alt="View Badge User" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- thống kê tổng số ứng viên và tin -->
    <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
        <div class="row">
            <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{ asset('admin_assets/img/icons/unicons/chart-success.png') }}"
                                    alt="chart success" class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">Tin tuyển dụng</p>
                        <h4 class="card-title mb-3">30</h4>
                        <small class=" text-warning fw-medium"><i class="icon-base bx bx-up-arrow-alt"></i>
                            +72.80%</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{ asset('admin_assets/img/icons/unicons/wallet-info.png') }}"
                                    alt="wallet info" class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">Ứng viên</p>
                        <h4 class="card-title mb-3">100</h4>
                        <small class="text-warning fw-medium"><i class="icon-base bx bx-up-arrow-alt"></i>
                            +28.42%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê -->
    <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
        <div class="card">
            <div class="row row-bordered g-0">
                <div class="col-lg-12">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="m-0 me-2">Thống kê</h5>
                        </div>

                    </div>
                    <div id="totalRevenueChart" class="px-3" data-ungtuyen="{{ json_encode($chartData) }}"
                        data-thang="{{ json_encode($chartCategories) }}"
                        data-tintuyen="{{ json_encode($chartDataTinTuyen) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--Thống kê circel -->
    <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
        <div class="card">
            <div class="row g-6">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title mb-0">
                                <h5 class="mb-1 me-2">Thống kê ứng viên</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-6">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <h3 class="mb-1">{{$charDataTotelUngVien}}</h3>
                                    <small>Ứng viên</small>
                                </div>
                                <div id="orderStatisticsChart" data-chuthich="{{ json_encode($chartDataCircleLabel) }}"
                                    data-ungvien="{{ json_encode($chartDataCircle) }}"></div>
                            </div>
                            <ul class="p-0 m-0">
                                <li class="d-flex align-items-center mb-5">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="icon-base bx bx-happy"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Ứng viên</h6>
                                            <small>Đã được chọn</small>
                                        </div>
                                        <div class="user-progress">
                                            <h6 class="mb-0">{{ $chartDataCircle[0] }} ứng viên</h6>
                                        </div>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center mb-5">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-success"><i
                                                class="icon-base bx bx-sad"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Ứng viên</h6>
                                            <small>Đã từ chối</small>
                                        </div>
                                        <div class="user-progress">
                                            <h6 class="mb-0">{{ $chartDataCircle[1] }} ứng viên</h6>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>



                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between">
                            <div class="card-title mb-0">
                                <h5 class="mb-1 me-2">Thống kê tuyển dụng</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-6">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <h3 class="mb-1">{{$charDataTotelTin}}</h3>
                                    <small>Bài đăng tin tuyển dụng</small>
                                </div>
                                <div id="orderStatisticsChartTin"
                                    data-chuthichtintuyendung="{{ json_encode($chartDataCircleLabelTin) }}"
                                    data-tintuyendung="{{ json_encode($chartDataCircleTin) }}"></div>
                            </div>
                            <ul class="p-0 m-0">

                                <li class="d-flex align-items-center mb-5">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-success"><i
                                                class="icon-base bx bx-clipboard"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Tuyển dụng</h6>
                                            <small>Đang hoạt động</small>
                                        </div>
                                        <div class="user-progress">
                                            <h6 class="mb-0">{{ $chartDataCircleTin[0]}} bài</h6>
                                        </div>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center mb-5">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-info"><i
                                                class="icon-base bx bx-calendar-exclamation"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Tuyển dụng</h6>
                                            <small>Bị tạm dừng</small>
                                        </div>
                                        <div class="user-progress">
                                            <h6 class="mb-0">{{ $chartDataCircleTin[1]}} bài</h6>
                                        </div>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center mb-5">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="icon-base bx bx-task-x"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Tuyển dụng</h6>
                                            <small>Hết hạn</small>
                                        </div>
                                        <div class="user-progress">
                                            <h6 class="mb-0">{{ $chartDataCircleTin[2]}} bài</h6>
                                        </div>
                                    </div>
                                </li>



                            </ul>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

</div>
@endsection



@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Định nghĩa các màu sắc chuẩn của template để không bị lỗi undefined
    let cardColor = '#ffffff';
    let borderColor = '#eceef1';
    let labelColor = '#a1acb8';
    let legendColor = '#566a7f';
    let fontFamily =
        '"Public Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif';
    let headingColor = '#566a7f';
    // 3. Cấu hình biểu đồ

    //BIỂU CỘT
    //=============================================================================================//
    const totalRevenueChartEl = document.querySelector('#totalRevenueChart');
    const dataUngTuyen = JSON.parse(totalRevenueChartEl.dataset.ungtuyen);
    const dataTinTuyen = JSON.parse(totalRevenueChartEl.dataset.tintuyen);
    const nhanThang = JSON.parse(totalRevenueChartEl.dataset.thang);
    const totalRevenueChartOptions = {
        series: [{
                name: 'Tổng lượt ứng tuyển',
                data: dataUngTuyen // <--- BƠM DỮ LIỆU CỘT Y VÀO ĐÂY
            },
            {
                name: 'Tổng tin tuyển dụng',
                data: dataTinTuyen
            }
            // Nếu sau này bạn có thêm dữ liệu (ví dụ: Việc làm đăng mới), 
            // chỉ cần phẩy 1 cái và copy thêm 1 block { name: '...', data: [...] } nữa vào đây.
        ],
        chart: {
            height: 300,
            stacked: true,
            type: 'bar',
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '30%',
                borderRadius: 8,
                startingShape: 'rounded',
                endingShape: 'rounded',
                borderRadiusApplication: 'around'
            }
        },
        colors: ['#5dbb3f', '#f5a000'], // Màu Primary và Info của Sneat
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 6,
            lineCap: 'round',
            colors: [cardColor]
        },
        legend: {
            show: true,
            horizontalAlign: 'left',
            position: 'top',
            markers: {
                size: 4,
                radius: 12,
                shape: 'circle',
                strokeWidth: 0
            },
            fontSize: '13px',
            fontFamily: fontFamily,
            fontWeight: 400,
            labels: {
                colors: legendColor,
                useSeriesColors: false
            },
            itemMargin: {
                horizontal: 10
            }
        },
        grid: {
            strokeDashArray: 7,
            borderColor: borderColor,
            padding: {
                top: 0,
                bottom: -8,
                left: 20,
                right: 20
            }
        },
        fill: {
            opacity: [1, 1]
        },
        xaxis: {
            categories: nhanThang, // <--- BƠM DỮ LIỆU TRỤC X VÀO ĐÂY
            labels: {
                style: {
                    fontSize: '13px',
                    fontFamily: fontFamily,
                    colors: labelColor
                }
            },
            axisTicks: {
                show: false
            },
            axisBorder: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    fontSize: '13px',
                    fontFamily: fontFamily,
                    colors: labelColor
                },
                formatter: function(val) {
                    return Math.floor(val); // Ép kiểu số nguyên cho cột Y (không hiển thị 1.5 người)
                }
            }
        },
        responsive: [{
                breakpoint: 1700,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 10,
                            columnWidth: '35%'
                        }
                    }
                }
            },
            {
                breakpoint: 1440,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 12,
                            columnWidth: '43%'
                        }
                    }
                }
            },
            {
                breakpoint: 1300,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 11,
                            columnWidth: '45%'
                        }
                    }
                }
            },
            {
                breakpoint: 1200,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 11,
                            columnWidth: '37%'
                        }
                    }
                }
            },
            {
                breakpoint: 1040,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 12,
                            columnWidth: '45%'
                        }
                    }
                }
            },
            {
                breakpoint: 991,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 12,
                            columnWidth: '33%'
                        }
                    }
                }
            },
            {
                breakpoint: 768,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 11,
                            columnWidth: '28%'
                        }
                    }
                }
            },
            {
                breakpoint: 640,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 11,
                            columnWidth: '30%'
                        }
                    }
                }
            },
            {
                breakpoint: 576,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 10,
                            columnWidth: '38%'
                        }
                    }
                }
            },
            {
                breakpoint: 440,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 10,
                            columnWidth: '50%'
                        }
                    }
                }
            },
            {
                breakpoint: 380,
                options: {
                    plotOptions: {
                        bar: {
                            borderRadius: 9,
                            columnWidth: '60%'
                        }
                    }
                }
            }
        ],
        states: {
            hover: {
                filter: {
                    type: 'none'
                }
            },
            active: {
                filter: {
                    type: 'none'
                }
            }
        }
    };

    if (typeof totalRevenueChartEl !== undefined && totalRevenueChartEl !== null) {
        const totalRevenueChart = new ApexCharts(totalRevenueChartEl, totalRevenueChartOptions);
        totalRevenueChart.render();
    }

    //BIỂU ĐỒ TRÒN 1
    //=============================================================================================//
    const chartOrderStatistics = document.querySelector('#orderStatisticsChart');
    const dataLabel = JSON.parse(chartOrderStatistics.dataset.chuthich);
    const dataUngVien = JSON.parse(chartOrderStatistics.dataset.ungvien);

    orderChartConfig = {
        chart: {
            height: 165,
            width: 136,
            type: 'donut',
            offsetX: 15
        },
        labels: dataLabel,
        series: dataUngVien,
        colors: [config.colors.success, config.colors.primary, config.colors.secondary, config.colors.info],
        stroke: {
            width: 5,
            colors: [cardColor]
        },
        dataLabels: {
            enabled: false,
            formatter: function(val, opt) {
                return parseInt(val) + '%';
            }
        },
        legend: {
            show: false
        },
        grid: {
            padding: {
                top: 0,
                bottom: 0,
                right: 15
            }
        },
        states: {
            hover: {
                filter: {
                    type: 'none'
                }
            },
            active: {
                filter: {
                    type: 'none'
                }
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        value: {
                            fontSize: '1.125rem',
                            fontFamily: fontFamily,
                            fontWeight: 500,
                            color: headingColor,
                            offsetY: -17,
                            formatter: function(val) {
                                return parseInt(val) + '%';
                            }
                        },
                        name: {
                            offsetY: 17,
                            fontFamily: fontFamily
                        },
                        total: {
                            show: true,
                            fontSize: '13px',
                            color: legendColor,
                            label: 'Tổng',
                            formatter: function(w) {
                                return '100%';
                            }
                        }
                    }
                }
            }
        }
    };
    if (typeof chartOrderStatistics !== undefined && chartOrderStatistics !== null) {
        const statisticsChart = new ApexCharts(chartOrderStatistics, orderChartConfig);
        statisticsChart.render();
    }


    //BIỂU ĐỒ TRÒN 2
    //=============================================================================================//

    const chartOrderStatisticsTin = document.querySelector('#orderStatisticsChartTin');
    const dataLabelTin = JSON.parse(chartOrderStatisticsTin.dataset.chuthichtintuyendung);
    const dataTinTuyenDung = JSON.parse(chartOrderStatisticsTin.dataset.tintuyendung);

    orderChartConfig = {
        chart: {
            height: 165,
            width: 136,
            type: 'donut',
            offsetX: 15
        },
        labels: dataLabelTin,
        series: dataTinTuyenDung,
        colors: [config.colors.success, config.colors.primary, config.colors.secondary, config.colors.info],
        stroke: {
            width: 5,
            colors: [cardColor]
        },
        dataLabels: {
            enabled: false,
            formatter: function(val, opt) {
                return parseInt(val) + '%';
            }
        },
        legend: {
            show: false
        },
        grid: {
            padding: {
                top: 0,
                bottom: 0,
                right: 15
            }
        },
        states: {
            hover: {
                filter: {
                    type: 'none'
                }
            },
            active: {
                filter: {
                    type: 'none'
                }
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        value: {
                            fontSize: '1.125rem',
                            fontFamily: fontFamily,
                            fontWeight: 500,
                            color: headingColor,
                            offsetY: -17,
                            formatter: function(val) {
                                return parseInt(val) + '%';
                            }
                        },
                        name: {
                            offsetY: 17,
                            fontFamily: fontFamily
                        },
                        total: {
                            show: true,
                            fontSize: '13px',
                            color: legendColor,
                            label: 'Tổng',
                            formatter: function(w) {
                                return '100%';
                            }
                        }
                    }
                }
            }
        }
    };

    if (typeof chartOrderStatisticsTin !== undefined && chartOrderStatisticsTin !== null) {
        const statisticsChart = new ApexCharts(chartOrderStatisticsTin, orderChartConfig);
        statisticsChart.render();
    }
});
</script>
@endsection