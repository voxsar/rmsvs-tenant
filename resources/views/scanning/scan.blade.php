<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - {{ $scanner->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #video-container {
            width: 100%;
            max-width: 500px;
            height: auto;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
        }
        #qr-video {
            width: 100%;
            height: auto;
            background-color: #000;
        }
        .scanner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            color: white;
        }
        .scanner-border {
            border: 2px solid #ffffff;
            border-radius: 10px;
            position: relative;
        }
        #result-container {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
        }
        .loading-spinner {
            margin: 20px auto;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ $scanner->name }} - {{ ucfirst($scanner->type) }} Scanner</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <p>Please scan your room QR code to proceed</p>
                        <p><small class="text-muted">Scanner Location: {{ $scanner->location }}</small></p>
                    </div>
                    
                    <div id="video-container" class="scanner-border mb-4">
                        <video id="qr-video" playsinline></video>
                    </div>
                    
                    <div class="text-center">
                        <button id="start-button" class="btn btn-primary">Start Scanner</button>
                        <button id="stop-button" class="btn btn-secondary" style="display: none;">Stop Scanner</button>
                    </div>

                    <div id="result-container" class="mt-4">
                        <div id="result-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="loading-overlay" class="scanner-overlay" style="display: none;">
    <div class="loading-spinner"></div>
    <p class="mt-3">Processing...</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner-worker.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoElem = document.getElementById('qr-video');
        const startButton = document.getElementById('start-button');
        const stopButton = document.getElementById('stop-button');
        const resultContainer = document.getElementById('result-container');
        const resultContent = document.getElementById('result-content');
        const loadingOverlay = document.getElementById('loading-overlay');
        
        let scanner = null;
        
        function initScanner() {
            // Create QR Scanner instance
            scanner = new QrScanner(
                videoElem,
                result => handleScan(result),
                {
                    highlightScanRegion: true,
                    highlightCodeOutline: true,
                }
            );
            
            // Handle camera errors
            scanner.setInversionMode('both');
        }
        
        function startScanner() {
            if (!scanner) {
                initScanner();
            }
            
            scanner.start()
                .then(() => {
                    startButton.style.display = 'none';
                    stopButton.style.display = 'inline-block';
                    resultContainer.style.display = 'none';
                })
                .catch(err => {
                    alert('Error starting scanner: ' + err);
                    console.error('QR Scanner error:', err);
                });
        }
        
        function stopScanner() {
            if (scanner) {
                scanner.stop();
                startButton.style.display = 'inline-block';
                stopButton.style.display = 'none';
            }
        }
        
        function handleScan(result) {
            // Stop the scanner after successful scan
            stopScanner();
            
            // Show loading overlay
            loadingOverlay.style.display = 'flex';
            
            // Process the QR code data
            fetch('{{ route("scanner.process", $scanner->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    scan_data: result.data
                })
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading overlay
                loadingOverlay.style.display = 'none';
                
                // Display result
                resultContainer.style.display = 'block';
                
                if (data.success) {
                    resultContainer.className = 'alert alert-success';
                    
                    // Handle specific action types
                    if (data.action === 'door_access_granted') {
                        resultContent.innerHTML = `
                            <h4>Access Granted</h4>
                            <p>${data.message}</p>
                            <p>Guest: ${data.guest}</p>
                        `;
                    } else if (data.action === 'checkout') {
                        resultContent.innerHTML = `
                            <h4>Check-out Completed</h4>
                            <p>${data.message}</p>
                            <p>Guest: ${data.guest}</p>
                        `;
                    } else if (data.action === 'checkin') {
                        resultContent.innerHTML = `
                            <h4>Check-in Completed</h4>
                            <p>${data.message}</p>
                            <p>Guest: ${data.guest}</p>
                        `;
                    } else if (data.action === 'mealed') {
                        resultContent.innerHTML = `
                            <h4>Meal Recorded</h4>
                            <p>${data.message}</p>
                            <p>Guest: ${data.guest}</p>
                        `;
                    } else if (data.action === 'redirect') {
                        resultContent.innerHTML = `
                            <h4>Redirecting...</h4>
                            <p>${data.message}</p>
                            <p>Guest: ${data.guest}</p>
                        `;
                        // Redirect to the specified URL after a short delay
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1500);
                    } else {
                        resultContent.innerHTML = `
                            <h4>Success</h4>
                            <p>${data.message}</p>
                        `;
                    }
                } else {
                    resultContainer.className = 'alert alert-danger';
                    resultContent.innerHTML = `
                        <h4>Error</h4>
                        <p>${data.message}</p>
                    `;
                }
            })
            .catch(error => {
                // Hide loading overlay
                loadingOverlay.style.display = 'none';
                
                // Display error
                resultContainer.style.display = 'block';
                resultContainer.className = 'alert alert-danger';
                resultContent.innerHTML = `
                    <h4>Error</h4>
                    <p>Failed to process QR code: ${error.message}</p>
                `;
                console.error('Error processing QR code:', error);
            });
        }
        
        // Button event listeners
        startButton.addEventListener('click', startScanner);
        stopButton.addEventListener('click', stopScanner);
        
        // Initialize scanner on page load
        initScanner();
    });
</script>

</body>
</html>