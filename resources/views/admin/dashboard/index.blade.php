@extends('welcome')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="mt-1 text-gray-600">Overview of restaurant performance and analytics</p>
        </div>

        <!-- Date Range Selector -->
        <div class="mb-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-2">
                    <h2 class="text-lg font-semibold text-gray-700">Performance Overview</h2>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                        Till {{ now()->format('F Y') }}
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2">
            <!-- Total Sales Card -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div class="rounded-lg bg-green-100 p-3">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-800">Total Sale</h3>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($orders->total_sales, 2) }}</p>
            </div>

            <!-- Total Taxes Card -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div class="rounded-lg bg-blue-100 p-3">
                        <i class="fas fa-percentage text-blue-600"></i>
                    </div>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-800">Total Taxes</h3>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($orders->tax, 2) }}</p>
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Tax Rate</span>
                        <span class="font-medium">14%</span>
                    </div>
                </div>
            </div>

            <!-- Net Profit Card -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div class="rounded-lg bg-purple-100 p-3">
                        <i class="fas fa-chart-line text-purple-600"></i>
                    </div>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-800">Total Sales After Tax Deduct</h3>
                @php
                    $gross = $orders->total_sales - $orders->tax;
                @endphp
                <p class="text-3xl font-bold text-gray-900">${{ number_format($gross, 2) }}</p>
            </div>

            <!-- Total Orders Card -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div class="rounded-lg bg-orange-100 p-3">
                        <i class="fas fa-shopping-cart text-orange-600"></i>
                    </div>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-800">Total Orders</h3>
                <p class="text-3xl font-bold text-gray-900">{{ $orders->total_orders }}</p>
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <div class="flex justify-between text-sm text-gray-600">
                        @php
                            $avgSales = $orders->total_orders > 0 ? $orders->total_sales / $orders->total_orders : 0;
                        @endphp
                        <span>Average Order Value</span>
                        <span class="font-medium">${{ number_format($avgSales, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-1">
            <!-- Sales Trend Chart -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Sales Chart</h3>
                    <div class="flex space-x-2">
                        {{-- <button class="chart-period-btn rounded-lg bg-gray-100 px-3 py-1 text-xs text-gray-700"
                            data-period="week">Weekly</button>
                        <button class="chart-period-btn rounded-lg bg-gray-100 px-3 py-1 text-xs text-gray-700"
                            data-period="month">Monthly</button> --}}
                        <button class="chart-period-btn rounded-lg bg-blue-100 px-3 py-1 text-xs text-blue-700"
                            data-period="year">Yearly</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Tax Breakdown Chart -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Tax Breakdown</h3>
                    <button class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="taxChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales by Category -->
        <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-6 text-lg font-semibold text-gray-800">Top Sales Items</h3>
            <div class="space-y-4">
                @if (isset($topItems) && count($topItems) > 0)
                    @foreach ($topItems as $item)
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $item->name }}</span>
                                <span class="text-gray-600">${{ number_format($item->total_sales, 2) }}</span>
                            </div>
                            @php
                                $maxSales = $topItems->max('total_sales');
                                $percentage = $maxSales > 0 ? ($item->total_sales / $maxSales) * 100 : 0;
                            @endphp
                            <div class="h-2 w-full rounded-full bg-gray-200">
                                <div class="h-2 rounded-full bg-blue-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="py-4 text-center text-gray-500">No sales data available</p>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Charts
            initializeCharts();

            // Chart period switching
            const periodButtons = document.querySelectorAll('.chart-period-btn');
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const period = this.dataset.period;

                    // Update active button
                    periodButtons.forEach(btn => {
                        btn.classList.remove('bg-blue-100', 'text-blue-700');
                        btn.classList.add('bg-gray-100', 'text-gray-700');
                    });
                    this.classList.remove('bg-gray-100', 'text-gray-700');
                    this.classList.add('bg-blue-100', 'text-blue-700');

                    // Fetch new data for the selected period
                    fetchChartData(period);
                });
            });

            function initializeCharts() {
                // Sales Chart (Line Chart)
                const salesCtx = document.getElementById('salesChart');
                if (!salesCtx) return;

                // yearly data from backend
                const initialSalesData = {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov',
                        'Dec'],
                    datasets: [{
                        label: 'Sales ($)',
                        data: {!! json_encode($yearlyData) !!},
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                };

                window.salesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: initialSalesData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    color: '#4b5563',
                                    font: {
                                        size: 12,
                                        family: 'system-ui'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#3b82f6',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return `$${context.parsed.y.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    color: '#6b7280',
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    color: '#6b7280'
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });

                // Tax Chart (Doughnut Chart)
                const taxCtx = document.getElementById('taxChart');
                if (!taxCtx) return;

                window.taxChart = new Chart(taxCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['VAT (14%)'],
                        datasets: [{
                            data: {!! json_encode([$orders->tax]) !!},
                            backgroundColor: ['#3b82f6', '#10b981'],
                            borderColor: '#ffffff',
                            borderWidth: 2,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#4b5563',
                                    padding: 20,
                                    font: {
                                        size: 12,
                                        family: 'system-ui'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${context.label}: $${value.toLocaleString()} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // function fetchChartData(period) {
            //     // This function would fetch real data from your backend
            //     // For now, we'll use sample data
            //     const sampleData = {
            //         week: {
            //             labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            //             data: [1250, 1890, 2150, 2400, 2850, 3120, 2780]
            //         },
            //         month: {
            //             labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            //             data: [8520, 9240, 10150, 12450]
            //         },
            //         year: {
            //             labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov',
            //                 'Dec'
            //             ],
            //             data: [28500, 31200, 29800, 32400, 35600, 38500, 41200, 39800, 42500, 45600, 48500,
            //                 52400
            //             ]
            //         }
            //     };

            //     if (window.salesChart && sampleData[period]) {
            //         window.salesChart.data.labels = sampleData[period].labels;
            //         window.salesChart.data.datasets[0].data = sampleData[period].data;
            //         window.salesChart.update();
            //     }
            // }
        });
    </script>

    <style>
        /* Custom scrollbar for chart containers */
        .h-64::-webkit-scrollbar {
            width: 6px;
        }

        .h-64::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .h-64::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .h-64::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .grid.grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid.grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .p-6 {
                padding: 1rem;
            }
        }

        /* Card hover effects */
        .rounded-xl {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .rounded-xl:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Smooth animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bg-white.rounded-xl {
            animation: fadeInUp 0.5s ease-out;
        }

        .bg-white.rounded-xl:nth-child(2) {
            animation-delay: 0.1s;
        }

        .bg-white.rounded-xl:nth-child(3) {
            animation-delay: 0.2s;
        }

        .bg-white.rounded-xl:nth-child(4) {
            animation-delay: 0.3s;
        }
    </style>
@endsection
