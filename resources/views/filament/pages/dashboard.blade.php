<x-filament-panels::page>
    @hasrole('admin')
    <div class="space-y-8">
        {{-- Section 1: Today's Summary --}}
        <x-filament::section
            icon="heroicon-o-calendar"
            collapsible
        >
            <x-slot name="heading">
                Today's Overview
            </x-slot>

            <x-slot name="description">
                Real-time snapshot of bookings and expenses for {{ now()->format('d M Y') }}
            </x-slot>

            @livewire(\App\Filament\Widgets\TodayStats::class)
        </x-filament::section>

        {{-- Section 2: Monthly Analytics --}}
        <x-filament::section
            icon="heroicon-o-presentation-chart-line"
            collapsible
        >
            <x-slot name="heading">
                Monthly Performance
            </x-slot>

            <x-slot name="description">
                Financial and booking trends for {{ now()->format('F Y') }}
            </x-slot>

            @livewire(\App\Filament\Widgets\MonthStats::class)
        </x-filament::section>

        {{-- Section 3: Customer Insights --}}
        <x-filament::section
            icon="heroicon-o-users"
            collapsible
        >
            <x-slot name="heading">
                Customer Insights
            </x-slot>

            <x-slot name="description">
                Database growth and top-performing players
            </x-slot>

            <div class="space-y-6">
                @livewire(\App\Filament\Widgets\CustomerStatsWidget::class)

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @livewire(\App\Filament\Widgets\TopCustomersWidget::class)
                    @livewire(\App\Filament\Widgets\NewestCustomersWidget::class)
                </div>
            </div>
        </x-filament::section>

        {{-- Section 4: Visual Trends & Charts --}}
        <x-filament::section
            icon="heroicon-o-chart-pie"
            collapsible
            collapsed
        >
            <x-slot name="heading">
                Visual Analytics
            </x-slot>

            <x-slot name="description">
                Long-term profitability and booking volume charts
            </x-slot>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @livewire(\App\Filament\Resources\BookingResource\Widgets\BookingsChart::class)
                @livewire(\App\Filament\Widgets\BookingsPerMonthChart::class)
                <div class="lg:col-span-2 flex justify-center">
                    <div class="w-full max-w-xl">
                        @livewire(\App\Filament\Widgets\ProfitabilityChart::class)
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
    @else
    <div class="space-y-6">
        <div class="relative overflow-hidden flex items-center justify-center min-h-[200px] bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-8">
            {{-- Decorative background element for dark mode --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 bg-primary-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 h-32 w-32 bg-primary-500/10 rounded-full blur-3xl"></div>

            <div class="relative text-center">
                <div class="flex justify-center mb-4">
                    <div class="p-3 bg-primary-50 dark:bg-primary-500/10 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-user-circle"
                            class="h-16 w-16 text-primary-600 dark:text-primary-400"
                        />
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome back, {{ auth()->user()->name }}!</h2>
                <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Your dashboard is ready. What would you like to do today?</p>
            </div>
        </div>

        <x-filament::section
            icon="heroicon-o-rocket-launch"
            collapsible
        >
            <x-slot name="heading">
                Quick Access
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 py-4">
                <a href="{{ route('filament.admin.pages.schedule') }}" 
                   class="group flex flex-col items-center p-6 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-white dark:hover:bg-white/10 hover:shadow-md transition-all duration-300">
                    <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <x-filament::icon icon="heroicon-o-clock" class="h-8 w-8 text-primary-500 group-hover:text-white transition-colors duration-300" />
                    </div>
                    <span class="mt-4 font-semibold text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-300">Daily Schedule</span>
                </a>

                <a href="{{ route('filament.admin.resources.bookings.create') }}" 
                   class="group flex flex-col items-center p-6 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-white dark:hover:bg-white/10 hover:shadow-md transition-all duration-300">
                    <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <x-filament::icon icon="heroicon-o-plus-circle" class="h-8 w-8 text-primary-500 group-hover:text-white transition-colors duration-300" />
                    </div>
                    <span class="mt-4 font-semibold text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-300">New Booking</span>
                </a>

                <a href="{{ route('filament.admin.resources.customers.index') }}" 
                   class="group flex flex-col items-center p-6 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-white dark:hover:bg-white/10 hover:shadow-md transition-all duration-300">
                    <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <x-filament::icon icon="heroicon-o-users" class="h-8 w-8 text-primary-500 group-hover:text-white transition-colors duration-300" />
                    </div>
                    <span class="mt-4 font-semibold text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-300">Customers</span>
                </a>

                <a href="{{ route('filament.admin.resources.expenses.index') }}" 
                   class="group flex flex-col items-center p-6 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-white dark:hover:bg-white/10 hover:shadow-md transition-all duration-300">
                    <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <x-filament::icon icon="heroicon-o-banknotes" class="h-8 w-8 text-primary-500 group-hover:text-white transition-colors duration-300" />
                    </div>
                    <span class="mt-4 font-semibold text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-300">Expenses</span>
                </a>
            </div>
        </x-filament::section>
    </div>
    @endhasrole
</x-filament-panels::page>
