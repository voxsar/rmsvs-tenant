<div>
    <label class="text-sm font-medium text-gray-700">Scanner URL</label>
    <div class="mt-1 flex items-center space-x-2">
        <div class="flex-1 relative">
            <input 
                type="text" 
                id="scanner-url-input-{{ $getRecord()->id }}" 
                value="{{ route('scanner.scan', $getRecord()) }}" 
                class="block w-full border-gray-300 rounded-md shadow-sm text-sm disabled:opacity-70 disabled:cursor-not-allowed read-only:bg-gray-50 dark:border-gray-600 dark:disabled:bg-gray-800 dark:disabled:border-gray-700" 
                readonly
            >
        </div>
        <button 
            type="button"
            onclick="copyUrlFromInput('scanner-url-input-{{ $getRecord()->id }}')" 
            class="inline-flex items-center justify-center font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset filament-button h-9 px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Copy URL
        </button>
        <a 
            href="{{ route('scanner.scan', $getRecord()) }}" 
            target="_blank"
            class="inline-flex items-center justify-center font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset filament-button h-9 px-4 text-sm text-white shadow focus:ring-white border-transparent bg-success-600 hover:bg-success-500 focus:bg-success-700 focus:ring-offset-success-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            Open
        </a>
    </div>
    <p class="mt-2 text-sm text-gray-500">
        Share this URL to provide access to the QR scanner
    </p>
</div>

<script>
    function copyUrlFromInput(inputId) {
        const input = document.getElementById(inputId);
        input.select();
        document.execCommand('copy');
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'fixed right-4 bottom-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg z-50';
        notification.textContent = 'Scanner URL copied to clipboard!';
        document.body.appendChild(notification);
        
        // Remove notification after 2 seconds
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }
</script>