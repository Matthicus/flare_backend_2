@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Flares Management</h1>
                <p class="mt-2 text-gray-600">Manage all flares in your application</p>
            </div>
            <div class="text-sm text-gray-500">
                Total: {{ $flares->total() }} flares
            </div>
        </div>

        <!-- Flares Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($flares as $flare)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <!-- Flare Image -->
                    @if($flare->photo_url)
                        <div class="h-48 bg-gray-200">
                            <img src="{{ $flare->photo_url }}" alt="Flare photo" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="h-48 bg-gray-100 flex items-center justify-center">
                            <span class="text-4xl">🔥</span>
                        </div>
                    @endif

                    <!-- Flare Content -->
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    {{ $flare->note ?: 'No note provided' }}
                                </h3>

                                <div class="space-y-1 text-sm text-gray-600">
                                    <p><strong>Category:</strong> {{ $flare->category ?: 'None' }}</p>
                                    <p><strong>Location:</strong> {{ round($flare->latitude, 4) }},
                                        {{ round($flare->longitude, 4) }}</p>
                                    <p><strong>Created:</strong> {{ $flare->created_at->format('M j, Y g:i A') }}</p>
                                </div>

                                <!-- User Info -->
                                <div class="mt-3 flex items-center">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                        <span
                                            class="text-xs font-medium text-gray-700">{{ substr($flare->user->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $flare->user->name ?? 'Unknown User' }}</span>
                                    @if($flare->user && $flare->user->username)
                                        <span class="text-sm text-gray-500 ml-1">(@{{ $flare->user->username }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <form action="{{ route('admin.flares.delete', $flare) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this flare?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition-colors">
                                    Delete Flare
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <span class="text-6xl">🔥</span>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No flares</h3>
                        <p class="mt-1 text-sm text-gray-500">No flares have been created yet.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($flares->hasPages())
            <div class="bg-white px-4 py-3 border border-gray-200 rounded-lg">
                {{ $flares->links() }}
            </div>
        @endif
    </div>
@endsection