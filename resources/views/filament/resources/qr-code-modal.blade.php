<div class="p-4 flex flex-col items-center justify-center space-y-4">
    @if($record->qr_code)
        <div class="text-center">
            <h3 class="text-lg font-medium">Room Access QR Code</h3>
            <p class="text-sm text-gray-500">For {{ $record->guest->first_name }} {{ $record->guest->last_name }}, Room {{ $record->room->room_no }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <img src="{{ Storage::url($record->qr_code) }}" alt="Room Access QR Code" class="w-64 h-64 mx-auto">
        </div>
        <div class="flex space-x-2">
            <a href="{{ Storage::url($record->qr_code) }}" 
               download="room_{{ $record->room->room_no }}_guest_{{ $record->guest->id }}_qr.svg"
               class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download QR Code
            </a>
            <button onclick="printQrCode()" 
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print QR Code
            </button>
        </div>
        <div class="text-center text-sm text-gray-500">
            <p>Scan this QR code for room access</p>
            <p>Generated on {{ $record->created_at->format('Y-m-d H:i') }}</p>
        </div>
    @else
        <div class="p-6 text-center">
            <p class="text-gray-500">No QR code has been generated for this check-in yet.</p>
            <button onclick="generateQrCode({{ $record->id }})" 
                    class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                Generate QR Code
            </button>
        </div>
    @endif
</div>

<script>
function printQrCode() {
    const printWindow = window.open('', '_blank');
    const qrCodeImg = document.querySelector('img[alt="Room Access QR Code"]');
    const guestName = '{{ $record->guest->first_name }} {{ $record->guest->last_name }}';
    const roomNo = '{{ $record->room->room_no }}';
    
    printWindow.document.write(`
        <html>
            <head>
                <title>QR Code - Room ${roomNo}</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; margin: 50px; }
                    .qr-container { border: 2px solid #000; padding: 20px; display: inline-block; }
                    img { width: 300px; height: 300px; }
                    h2 { margin-bottom: 10px; }
                    p { margin: 5px 0; }
                </style>
            </head>
            <body>
                <div class="qr-container">
                    <h2>Room Access QR Code</h2>
                    <p><strong>Guest:</strong> ${guestName}</p>
                    <p><strong>Room:</strong> ${roomNo}</p>
                    <img src="${qrCodeImg.src}" alt="QR Code">
                    <p><small>Scan for room access</small></p>
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function generateQrCode(checkInId) {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = 'Generating...';
    
    fetch(`/generate-qr-code/${checkInId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the generated QR code
            location.reload();
        } else {
            alert('Error: ' + data.message);
            button.disabled = false;
            button.innerHTML = 'Generate QR Code';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to generate QR code');
        button.disabled = false;
        button.innerHTML = 'Generate QR Code';
    });
}
</script>