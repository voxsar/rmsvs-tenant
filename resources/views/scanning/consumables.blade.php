<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumables - Room {{ $room->room_no }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .consumable-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            height: 100%;
        }
        .consumable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .consumable-card.selected {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }
        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
        }
        .quantity-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-weight: bold;
            cursor: pointer;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #dee2e6;
            margin: 0 8px;
        }
        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
        }
        .success-content {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            color: #333;
        }
        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        #toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center mb-4">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Consumable Requests</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5>Guest: {{ $guest->first_name }} {{ $guest->last_name }}</h5>
                            <p class="text-muted mb-0">Room: {{ $room->room_no }}</p>
                        </div>
                        <div>
                            <a href="{{ route('scanner.scan', 1) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Scanner
                            </a>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-4">Select Items to Request</h5>
                    
                    <div class="row row-cols-1 row-cols-md-3 g-4" id="consumables-container">
                        @foreach($consumables as $consumable)
                        <div class="col">
                            <div class="card consumable-card h-100" data-id="{{ $consumable->id }}" data-name="{{ $consumable->name }}" data-price="{{ $consumable->price }}">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $consumable->name }}</h5>
                                    <p class="card-text">{{ $consumable->description }}</p>
                                    <p class="card-text"><strong>${{ number_format($consumable->price, 2) }}</strong></p>
                                    
                                    <div class="quantity-control">
                                        <button class="quantity-btn decrease-btn" disabled>-</button>
                                        <input type="number" class="quantity-input" value="0" min="0" max="10" readonly>
                                        <button class="quantity-btn increase-btn">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button id="submit-request" class="btn btn-primary btn-lg" disabled>Submit Request</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<div id="success-overlay" class="success-overlay" style="display: none;">
    <div class="success-content">
        <div class="success-icon">✓</div>
        <h3>Request Submitted</h3>
        <p>Your consumable request has been successfully submitted.</p>
        <button id="close-success" class="btn btn-primary mt-3">Close</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const consumableCards = document.querySelectorAll('.consumable-card');
        const submitButton = document.getElementById('submit-request');
        const successOverlay = document.getElementById('success-overlay');
        const closeSuccessBtn = document.getElementById('close-success');
        const toastContainer = document.getElementById('toast-container');
        
        // Handle quantity changes
        consumableCards.forEach(card => {
            const increaseBtn = card.querySelector('.increase-btn');
            const decreaseBtn = card.querySelector('.decrease-btn');
            const quantityInput = card.querySelector('.quantity-input');
            
            increaseBtn.addEventListener('click', () => {
                let currentValue = parseInt(quantityInput.value);
                if (currentValue < 10) {
                    quantityInput.value = currentValue + 1;
                    decreaseBtn.disabled = false;
                    
                    if (parseInt(quantityInput.value) === 10) {
                        increaseBtn.disabled = true;
                    }
                    
                    if (parseInt(quantityInput.value) > 0) {
                        card.classList.add('selected');
                    }
                    
                    updateSubmitButton();
                }
            });
            
            decreaseBtn.addEventListener('click', () => {
                let currentValue = parseInt(quantityInput.value);
                if (currentValue > 0) {
                    quantityInput.value = currentValue - 1;
                    increaseBtn.disabled = false;
                    
                    if (parseInt(quantityInput.value) === 0) {
                        decreaseBtn.disabled = true;
                        card.classList.remove('selected');
                    }
                    
                    updateSubmitButton();
                }
            });
        });
        
        function updateSubmitButton() {
            // Check if any consumable has quantity > 0
            let hasItems = false;
            consumableCards.forEach(card => {
                const quantityInput = card.querySelector('.quantity-input');
                if (parseInt(quantityInput.value) > 0) {
                    hasItems = true;
                }
            });
            
            submitButton.disabled = !hasItems;
        }
        
        // Handle form submission
        submitButton.addEventListener('click', async () => {
            const selectedItems = [];
            
            consumableCards.forEach(card => {
                const quantityInput = card.querySelector('.quantity-input');
                const quantity = parseInt(quantityInput.value);
                
                if (quantity > 0) {
                    selectedItems.push({
                        consumable_id: card.dataset.id,
                        quantity: quantity,
                        name: card.dataset.name,
                        price: card.dataset.price
                    });
                }
            });
            
            if (selectedItems.length === 0) {
                return;
            }
            
            // Submit each request individually
            for (const item of selectedItems) {
                try {
                    const response = await fetch('{{ route("consumables.request", $checkIn->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            consumable_id: item.consumable_id,
                            quantity: item.quantity
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showToast(`Added ${item.quantity}x ${item.name}`, 'success');
                    } else {
                        showToast(`Error: ${result.error}`, 'danger');
                    }
                } catch (error) {
                    showToast(`Error: ${error.message}`, 'danger');
                }
            }
            
            // Show success overlay
            successOverlay.style.display = 'flex';
            
            // Reset all quantities
            consumableCards.forEach(card => {
                const quantityInput = card.querySelector('.quantity-input');
                const decreaseBtn = card.querySelector('.decrease-btn');
                const increaseBtn = card.querySelector('.increase-btn');
                
                quantityInput.value = 0;
                decreaseBtn.disabled = true;
                increaseBtn.disabled = false;
                card.classList.remove('selected');
            });
            
            updateSubmitButton();
        });
        
        closeSuccessBtn.addEventListener('click', () => {
            successOverlay.style.display = 'none';
        });
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast show bg-${type} text-white`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    });
</script>

</body>
</html>