@extends('layouts.app')

@section('title', 'Import Meta Ads - ' . $campaign->name)

@section('content')
    <div class="min-h-screen bg-gray-50">
        @if(Auth::user()->role === 'admin')
            @include('admin.partials.sidebar')
        @else
            @include('realtor.partials.sidebar')
        @endif

        <main class="lg:ml-64 min-h-screen">
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
                <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <button onclick="toggleMobileMenu()"
                                class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div>
                                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">Import Meta Ads</h1>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base">Campaign: {{ $campaign->name }}</p>
                            </div>
                        </div>
                        @include('realtor.partials.user-dropdown')
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                <div class="max-w-5xl mx-auto">

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                            {{ session('warning') }}
                        </div>
                    @endif

                    <div class="mb-6">
                        <a href="{{ route('campaigns.show', $campaign) }}"
                            class="text-primary hover:text-primary/80 text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back to campaign
                        </a>
                    </div>

                    <div class="mb-6 bg-white rounded-lg p-1 shadow-sm inline-flex border border-gray-200">
                        <button type="button" onclick="switchMetaTab('csv')" id="tab-meta-csv"
                            class="px-6 py-2 rounded-md text-sm font-medium transition bg-[#1877F2] text-white shadow-sm">
                            Upload Meta CSV
                        </button>
                        <button type="button" onclick="switchMetaTab('api')" id="tab-meta-api"
                            class="px-6 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">
                            Sync from Facebook
                        </button>
                    </div>

                    {{-- CSV upload --}}
                    <div id="content-meta-csv" class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        <form method="POST" action="{{ route('leads.import.meta', $campaign) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                                <svg class="mx-auto h-12 w-12 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.25c-1.23 0-1.62.77-1.62 1.55V12h2.76l-.44 2.89h-2.32v6.99A10 10 0 0 0 22 12z"/>
                                </svg>
                                <div class="mt-4">
                                    <label for="meta_csv_file" class="cursor-pointer">
                                        <span class="mt-2 block text-sm font-medium text-gray-900">
                                            Upload Meta Ads export (CSV)
                                        </span>
                                        <input type="file" name="csv_file" id="meta_csv_file" class="hidden" accept=".csv,.txt"
                                            onchange="updateMetaFileName(this)">
                                        <span class="mt-1 block text-xs text-gray-500">CSV up to 5MB</span>
                                        <div id="meta-file-name" class="mt-2 text-sm text-[#1877F2] font-medium"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h4 class="text-sm font-bold text-gray-700 mb-2">How to export from Meta</h4>
                                <p class="text-sm text-gray-600 mb-4">
                                    Export leads from Meta Lead Center or Ads Manager. Columns like
                                    <code class="text-xs bg-gray-100 px-1 rounded">full_name</code>,
                                    <code class="text-xs bg-gray-100 px-1 rounded">email</code>, and
                                    <code class="text-xs bg-gray-100 px-1 rounded">phone_number</code> are mapped automatically.
                                </p>
                                <a href="{{ route('leads.import.meta.sample', $campaign) }}"
                                    class="inline-flex items-center justify-center bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                    Download Sample Meta CSV
                                </a>
                            </div>

                            <div class="mt-8 flex gap-4">
                                <button type="submit"
                                    class="bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold py-3 px-8 rounded-lg transition">
                                    Import Meta Leads
                                </button>
                                <a href="{{ route('campaigns.show', $campaign) }}"
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-8 rounded-lg transition">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    {{-- API sync --}}
                    <div id="content-meta-api" class="hidden bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        @if(!$facebookLinked)
                            <div class="text-center py-10">
                                <p class="text-gray-600 mb-6">Link your Facebook account to sync leads from Lead Forms.</p>
                                <a href="{{ route('connect.facebook.redirect', ['redirect' => route('leads.import.meta.page', $campaign)]) }}"
                                    class="inline-flex items-center bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold py-3 px-8 rounded-lg transition">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.25c-1.23 0-1.62.77-1.62 1.55V12h2.76l-.44 2.89h-2.32v6.99A10 10 0 0 0 22 12z"/>
                                    </svg>
                                    Link Facebook
                                </a>
                            </div>
                        @else
                            @if($metaPages->isEmpty())
                                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6 text-sm">
                                    Facebook is linked but no connected Pages were found.
                                    <a href="{{ route('connect.facebook.redirect', ['redirect' => route('leads.import.meta.page', $campaign)]) }}"
                                        class="font-semibold underline">Re-link Facebook</a>
                                    to grant Page and Lead Ads permissions.
                                </div>
                            @endif

                            <form method="POST" action="{{ route('leads.meta.sync', $campaign) }}" id="meta-sync-form">
                                @csrf
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <label for="page_id" class="block text-sm font-semibold text-gray-700">Facebook Page</label>
                                        <a href="{{ route('connect.facebook.redirect', ['redirect' => route('leads.import.meta.page', $campaign) . '?tab=api']) }}" class="text-xs text-[#1877F2] hover:underline flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Sync Facebook Pages
                                        </a>
                                    </div>
                                    <select name="page_id" id="page_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1877F2] focus:border-transparent"
                                        @if($metaPages->isEmpty()) disabled @endif onchange="loadLeadForms()">
                                        <option value="">Select a Facebook Page</option>
                                        @foreach($metaPages as $page)
                                            <option value="{{ $page->page_id }}">{{ $page->page_name }} ({{ $page->page_id }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-6">
                                    <div class="flex justify-between items-center mb-2">
                                        <label for="form_id" class="block text-sm font-semibold text-gray-700">Lead Form</label>
                                        <button type="button" onclick="loadLeadForms(true)" id="refresh-forms-btn" class="text-xs text-[#1877F2] hover:underline flex items-center disabled:opacity-50" disabled>
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Refresh Forms
                                        </button>
                                    </div>
                                    <select name="form_id" id="form_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1877F2] focus:border-transparent"
                                        disabled>
                                        <option value="">Select a Page first…</option>
                                    </select>
                                    <p id="forms-error" class="text-red-600 text-sm mt-2 hidden"></p>
                                </div>

                                <p class="text-xs text-gray-500 mb-6">
                                    Your Facebook app must include the <strong>leads_retrieval</strong> permission.
                                    Production use may require Meta App Review.
                                </p>

                                <div class="flex gap-4">
                                    <button type="submit" id="sync-btn"
                                        class="bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold py-3 px-8 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        @if($metaPages->isEmpty()) disabled @endif>
                                        Sync Leads
                                    </button>
                                    <a href="{{ route('campaigns.show', $campaign) }}"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-8 rounded-lg transition">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        @endif
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        const savedPageId = @json($savedPageId);
        const savedFormId = @json($savedFormId);
        const formsUrl = @json(route('leads.meta.forms', $campaign));
        const hasPages = @json($metaPages->isNotEmpty());
        const facebookLinked = @json($facebookLinked);

        function switchMetaTab(tab) {
            document.getElementById('content-meta-csv').classList.toggle('hidden', tab !== 'csv');
            document.getElementById('content-meta-api').classList.toggle('hidden', tab !== 'api');

            const csvBtn = document.getElementById('tab-meta-csv');
            const apiBtn = document.getElementById('tab-meta-api');
            const active = 'px-6 py-2 rounded-md text-sm font-medium transition bg-[#1877F2] text-white shadow-sm';
            const inactive = 'px-6 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition';

            csvBtn.className = tab === 'csv' ? active : inactive;
            apiBtn.className = tab === 'api' ? active : inactive;

            if (tab === 'api') {
                const pageSelect = document.getElementById('page_id');
                if (pageSelect && pageSelect.value && document.getElementById('form_id').disabled) {
                    loadLeadForms();
                }
            }
        }

        function updateMetaFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : '';
            document.getElementById('meta-file-name').textContent = fileName;
        }

        async function loadLeadForms(forceRefresh = false) {
            const pageSelect = document.getElementById('page_id');
            const formSelect = document.getElementById('form_id');
            const refreshBtn = document.getElementById('refresh-forms-btn');
            const errorEl = document.getElementById('forms-error');
            
            if (!pageSelect || !formSelect) return;
            
            const pageId = pageSelect.value;
            if (!pageId) {
                formSelect.innerHTML = '<option value="">Select a Page first…</option>';
                formSelect.disabled = true;
                if (refreshBtn) refreshBtn.disabled = true;
                return;
            }

            formSelect.innerHTML = '<option value="">Loading forms…</option>';
            formSelect.disabled = true;
            if (refreshBtn) refreshBtn.disabled = true;
            errorEl.classList.add('hidden');

            try {
                const url = new URL(formsUrl);
                url.searchParams.append('page_id', pageId);
                if (forceRefresh) {
                    url.searchParams.append('refresh', '1');
                }

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to load lead forms.');
                }

                const forms = data.forms || [];
                if (forms.length === 0) {
                    formSelect.innerHTML = '<option value="">No lead forms found on your Page</option>';
                    if (refreshBtn) refreshBtn.disabled = false;
                    return;
                }

                formSelect.innerHTML = '<option value="">Select a lead form</option>';
                
                // Add the "All forms" option
                const allOpt = document.createElement('option');
                allOpt.value = 'all';
                allOpt.textContent = 'All forms on this Page';
                if (savedFormId === 'all' && savedPageId === pageId) {
                    allOpt.selected = true;
                }
                formSelect.appendChild(allOpt);

                forms.forEach(form => {
                    const opt = document.createElement('option');
                    opt.value = form.id;
                    opt.textContent = form.name + (form.status ? ' (' + form.status + ')' : '');
                    if (savedFormId && form.id === savedFormId && savedPageId === pageId) {
                        opt.selected = true;
                    }
                    formSelect.appendChild(opt);
                });
                formSelect.disabled = false;
                if (refreshBtn) refreshBtn.disabled = false;
            } catch (e) {
                formSelect.innerHTML = '<option value="">Unable to load forms</option>';
                errorEl.textContent = e.message;
                errorEl.classList.remove('hidden');
                if (refreshBtn) refreshBtn.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get('tab') === 'api') {
                switchMetaTab('api');
            }

            const pageSelect = document.getElementById('page_id');
            if (pageSelect) {
                if (savedPageId) {
                    pageSelect.value = savedPageId;
                }
                
                // If a page is selected (either by savedPageId or it's the only option), load forms automatically
                if (pageSelect.value && document.getElementById('content-meta-api').classList.contains('hidden') === false) {
                    loadLeadForms();
                } else if (pageSelect.value && params.get('tab') === 'api') {
                    loadLeadForms();
                }
            }
        });
    </script>
@endsection
