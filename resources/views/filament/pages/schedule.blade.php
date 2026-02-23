<x-filament-panels::page>
    <style>
        /* Enhanced Glassmorphism & Animations */
        .timeline-container {
            position: relative;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .slot-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            animation: slideUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(10px);
        }

        /* Staggered animation delays for the cards */
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.10s; }
        .stagger-3 { animation-delay: 0.15s; }
        .stagger-4 { animation-delay: 0.20s; }
        .stagger-5 { animation-delay: 0.25s; }
        .stagger-6 { animation-delay: 0.30s; }
        .stagger-7 { animation-delay: 0.35s; }
        .stagger-8 { animation-delay: 0.40s; }
        .stagger-9 { animation-delay: 0.45s; }
        .stagger-10 { animation-delay: 0.50s; }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slot-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .slot-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            transition: width 0.3s ease;
        }

        .slot-card:hover::before {
            width: 8px;
        }

        .slot-free {
            background: linear-gradient(to right, rgba(16, 185, 129, 0.03), transparent);
            border: 1px solid rgba(16, 185, 129, 0.08);
        }
        .slot-free::before {
            background: #10b981;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
        }

        .slot-booked {
            background: linear-gradient(to right, rgba(239, 68, 68, 0.03), transparent);
            border: 1px solid rgba(239, 68, 68, 0.08);
        }
        .slot-booked::before {
            background: #ef4444;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
        }

        .time-badge {
            font-family: 'Outfit', 'Inter', monospace; /* Updated to Match Premium Font */
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Loading Skeleton Pulse */
        .skeleton-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        
        .sticky-column {
            position: sticky;
            top: 6rem; /* Accounts for Filament header */
            height: max-content;
        }
    </style>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Right/Mobile Top: Date Selection (Now Sticky) --}}
        <div class="lg:col-span-1">
            <div class="space-y-6 sticky-column">
                <x-filament::section compact>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2 text-emerald-500">
                            <x-heroicon-o-calendar class="w-5 h-5" />
                            <span>Select Date</span>
                        </div>
                    </x-slot>
                    <form wire:submit.prevent>
                        {{ $this->form }}
                    </form>
                </x-filament::section>

                @php
                    $bookings = $this->getBookings();
                    $totalRevenue = $bookings->sum('total_price');
                @endphp

                <x-filament::section compact>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2 text-blue-500">
                            <x-heroicon-o-chart-bar class="w-5 h-5" />
                            <span>Day Summary</span>
                        </div>
                    </x-slot>
                    <div class="flex flex-row gap-4 w-full">
                        <div class="flex-1 flex flex-col items-center justify-center p-3 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 text-center">
                            <div class="p-2 mb-2 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                <x-heroicon-o-ticket class="w-4 h-4" />
                            </div>
                            <span class="text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wider">Bookings</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white leading-none">{{ $bookings->count() }}</span>
                        </div>
                        
                        <div class="flex-1 flex flex-col items-center justify-center p-3 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 text-center">
                            <div class="p-2 mb-2 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <x-heroicon-o-banknotes class="w-4 h-4" />
                            </div>
                            <span class="text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wider">Revenue</span>
                            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 leading-none">EGP {{ number_format($totalRevenue, 0) }}</span>
                        </div>
                    </div>


                </x-filament::section>
            </div>
        </div>

        {{-- Left/Main: Timeline --}}
        <div class="lg:col-span-3">
            <div class="timeline-container relative">
                
                {{-- Date Changer Loading Overlay --}}
                <div wire:loading.flex wire:target="data.date" class="absolute inset-0 z-30 flex-col justify-start items-center pt-40 bg-white/40 dark:bg-gray-900/60 backdrop-blur-sm rounded-[1.5rem]">
                    <x-filament::loading-indicator class="w-12 h-12 text-emerald-500" />
                    <span class="mt-4 text-sm font-bold text-gray-700 dark:text-gray-200">Refreshing schedule...</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 mb-8 border-b border-gray-200 dark:border-gray-700/50 pb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-lg shadow-emerald-500/30">
                            <x-heroicon-s-clock class="w-6 h-6" />
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Court Timeline</h2>
                    </div>
                    
                    <div class="flex items-center">
                        <span class="text-[10px] sm:text-sm font-bold px-3 py-1.5 sm:px-4 sm:py-2 bg-emerald-100/50 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 rounded-2xl border border-emerald-200 dark:border-emerald-800/50 flex items-center gap-2 whitespace-nowrap">
                            <x-heroicon-o-calendar-days class="w-4 h-4" />
                            {{ \Carbon\Carbon::parse($selectedDate)->format('D, M j, Y') }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 2xl:grid-cols-2 gap-4" wire:key="timeline-container-{{ md5($selectedDate) }}" wire:loading.class="opacity-30 blur-sm pointer-events-none transition-all duration-300" wire:target="data.date">
                    
                    @if (empty($this->getTimeSlots()))
                        <div class="col-span-1 2xl:col-span-2 py-12 flex flex-col items-center justify-center text-center">
                            <x-heroicon-o-inbox class="w-16 h-16 text-gray-400 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No time slots configured</h3>
                            <p class="text-sm text-gray-500 mt-1">Please check the schedule settings.</p>
                        </div>
                    @else
                        @foreach ($this->getTimeSlots() as $index => $slot)
                            @php
                                $booking = $slot['booking'];
                                $status = $slot['status'];
                                $staggerClass = 'stagger-' . min(($index % 10) + 1, 10);
                            @endphp

                            <div 
                                wire:key="{{ $slot['id'] }}"
                                class="slot-card p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-5 {{ $staggerClass }} {{ $status === 'booked' ? 'slot-booked' : 'slot-free' }}"
                            >
                                <div class="flex items-center gap-4 sm:gap-6">
                                    <div class="flex flex-col items-start sm:items-center justify-center gap-1 w-20 shrink-0">
                                        <div class="time-badge text-lg sm:text-xl {{ $status === 'booked' ? 'text-red-500' : 'text-emerald-500' }}">
                                            {{ $slot['hour'] }}
                                        </div>
                                        @if($selectedDate === \Carbon\Carbon::today()->toDateString() && \Carbon\Carbon::now()->between(\Carbon\Carbon::parse($slot['hour']), \Carbon\Carbon::parse($slot['hour'])->addMinutes(30)))
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-red-500/10 text-[9px] font-black uppercase tracking-widest text-red-500 animate-pulse border border-red-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span> Live
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex flex-col min-w-0">
                                        @if ($status === 'booked')
                                            <div class="flex items-center gap-1.5 sm:gap-2 mb-1 flex-wrap">
                                                <div class="flex items-center gap-1.5 sm:gap-2">
                                                    <x-heroicon-s-user class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400 shrink-0" />
                                                    <span class="font-bold text-gray-900 dark:text-white text-sm sm:text-base truncate">{{ $booking->customer_name }}</span>
                                                </div>
                                                @if($booking->is_recurring)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/10 text-[9px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        <x-heroicon-s-arrow-path class="w-3 h-3" /> Fixed
                                                    </span>
                                                @endif
                                                @if($slot['startedYesterday'])
                                                    <span class="inline-flex items-center gap-1.5 rounded bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-500 ring-1 ring-inset ring-amber-500/20 uppercase tracking-widest">
                                                        <x-heroicon-s-arrow-uturn-left class="w-3 h-3" />
                                                        Started Yesterday
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs text-gray-500 font-medium">
                                                <x-heroicon-m-clock class="w-3 h-3 sm:w-3.5 sm:h-3.5 shrink-0" />
                                                <span class="uppercase tracking-wide truncate">
                                                    {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} — {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }} 
                                                    <span class="text-gray-400 px-1 hidden sm:inline">•</span><br class="sm:hidden" />
                                                    <span class="sm:inline">{{ $booking->hours }} hrs</span>
                                                </span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></div>
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-base sm:text-lg">Available</span>
                                            </div>
                                            <span class="text-[10px] sm:text-xs text-gray-500 font-medium tracking-wide truncate">Ready for reservation</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-end sm:justify-center gap-3 shrink-0">
                                    @if ($status === 'booked')
                                        @if(auth()->user()?->hasRole('admin'))
                                            <div class="text-right flex flex-col items-end">
                                                <span class="text-[9px] uppercase font-bold text-gray-500 tracking-wider mb-0.5">Total</span>
                                                <div class="font-bold text-gray-900 dark:text-white text-base leading-none">EGP {{ number_format($booking->total_price, 0) }}</div>
                                            </div>
                                        @endif
                                        <x-filament::button
                                            size="sm"
                                            color="gray"
                                            wire:click="openEditModal({{ $booking->id }})"
                                            icon="heroicon-m-eye"
                                            class="px-3"
                                        >
                                            View
                                        </x-filament::button>
                                    @else
                                        @php
                                            $proposedStart = \Carbon\Carbon::parse($selectedDate . ' ' . $slot['hour']);
                                            $isPast = $proposedStart->isPast();
                                            $isAdmin = auth()->user()?->hasRole('admin');
                                        @endphp

                                        @if (!$isPast || $isAdmin)
                                            <x-filament::button
                                                size="sm"
                                                color="emerald"
                                                wire:click="openCreateModal('{{ $selectedDate }}', '{{ \Carbon\Carbon::parse($slot['hour'])->format('H:i:00') }}')"
                                                icon="heroicon-m-plus"
                                                class="shadow-lg shadow-emerald-500/20 px-4"
                                            >
                                                Book Now
                                            </x-filament::button>
                                        @else
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border border-gray-200 dark:border-gray-700 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-800/30">
                                                Expired
                                            </span>
                                        @endif
                                    @endif

                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
