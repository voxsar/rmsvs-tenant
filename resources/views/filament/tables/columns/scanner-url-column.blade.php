<div class="flex items-center space-x-2">
    <span class="text-xs text-gray-500 truncate max-w-xs" id="scan-url-{{ $getRecord()->id }}">{{ route('scanner.scan', $getRecord()) }}</span>
    <button 
        onclick="copyToClipboard('scan-url-{{ $getRecord()->id }}')" 
        class="p-1 text-primary-500 hover:text-primary-600 transition rounded-full hover:bg-gray-100"
        title="Copy URL"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
    </button>
</div>

<script>
    function copyToClipboard(elementId) {
        const text = document.getElementById(elementId).textContent;
        navigator.clipboard.writeText(text).then(
            function() {
                // Create and show notification
                const notification = document.createElement('div');
                notification.className = 'fixed right-4 bottom-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg z-50';
                notification.textContent = 'URL copied to clipboard!';
                document.body.appendChild(notification);
                
                // Remove notification after 2 seconds
                setTimeout(() => {
                    notification.remove();
                }, 2000);
            },
            function() {
                console.error('Failed to copy URL');
            }
        );
    }
</script>