<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$cart = $_SESSION['checkout_cart'] ?? [];
$total = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

// Fetch user
$conn = new mysqli('localhost', 'root', 'Sun123flower@', 'lakway_delivery');
$stmt = $conn->prepare("SELECT email, mobile FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get store location
$store_query = $conn->query("SELECT latitude, longitude FROM stores WHERE status='approved' LIMIT 1");
$store = $store_query->fetch_assoc();
$store_lat = $store['latitude'] ?? 6.6844; // Ratnapura coordinates
$store_lng = $store['longitude'] ?? 80.3992;

$google_maps_api_key = 'Your_Google_Api_Key';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout - ලක්way Delivery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body{font-family:'Poppins',sans-serif;background:#f8faf9;color:#1a202c;margin:0;padding:2rem;}
    .container{max-width:800px;margin:auto;background:#fff;padding:2rem;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.1);}
    h1{font-size:1.8rem;font-weight:700;margin-bottom:1rem;color:#2d7a4e;}
    .user-info{background:#f0fdf4;padding:1rem;border-radius:12px;margin-bottom:1.5rem;}
    .user-info p{font-size:1rem;color:#1e5438;}
    .cart-items{margin-bottom:1.5rem;}
    .cart-item{display:flex;gap:1rem;padding:1rem;background:#f8faf9;border-radius:12px;margin-bottom:1rem;}
    .cart-item img{width:60px;height:60px;border-radius:8px;object-fit:cover;}
    .cart-item-info{flex:1;}
    .cart-item-name{font-weight:600;}
    .cart-item-price{color:#2d7a4e;font-weight:700;}
    .total{font-size:1.5rem;font-weight:700;color:#2d7a4e;text-align:right;margin:1.5rem 0;}
    .btn{width:100%;background:linear-gradient(135deg,#2d7a4e,#3a9d5d);color:#fff;border:none;padding:1rem;
         border-radius:12px;font-weight:600;cursor:pointer;margin-top:1rem;}
    .btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.15);}
    .btn-secondary{background:#e5e7eb;color:#374151;}
    .btn:disabled{background:#ccc;cursor:not-allowed;transform:none;}
    
    /* Map Styles */
    .map-section{margin-bottom:1.5rem;}
    .address-input{width:100%;padding:0.75rem;border:1px solid #d1d5db;border-radius:8px;margin-bottom:1rem;}
    .map-container{height:400px;border-radius:12px;overflow:hidden;margin-bottom:1rem;border:2px solid #e5e7eb;}
    #map{height:100%;width:100%;}
    
    /* Delivery Info */
    .delivery-info{background:#f0f9ff;padding:1rem;border-radius:12px;margin-bottom:1.5rem;}
    .delivery-info p{margin:0.5rem 0;}
    
    /* Modal */
    .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;}
    .modal-content{background:#fff;padding:2rem;border-radius:12px;max-width:500px;width:90%;}
    .modal-buttons{display:flex;gap:1rem;margin-top:1.5rem;}
    .modal-buttons button{flex:1;}
    
    .error{color:#dc2626;background:#fef2f2;padding:0.75rem;border-radius:8px;margin-bottom:1rem;display:none;}
    .info{color:#1e40af;background:#eff6ff;padding:0.75rem;border-radius:8px;margin-bottom:1rem;}
</style>
</head>
<body>
<div class="container">
    <h1>Checkout</h1>

    <div class="user-info">
        <p><strong>Name:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Mobile:</strong> <?= htmlspecialchars($user['mobile']) ?></p>
    </div>

    <div class="cart-items">
        <?php foreach($cart as $item): ?>
            <div class="cart-item">
                <img src="uploads/items/<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                <div class="cart-item-info">
                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="cart-item-price">LKR <?= number_format($item['price'] * $item['quantity'], 2) ?> (<?= $item['quantity'] ?> × LKR <?= number_format($item['price'], 2) ?>)</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="total">Subtotal: LKR <?= number_format($total, 2) ?></div>

    <div class="map-section">
        <h2>Select Delivery Location</h2>
        <div class="info">
            <strong>Delivery Area:</strong> Ratnapura to Kuruwita only
        </div>
        
        <input type="text" id="address-input" class="address-input" placeholder="Search for your delivery address in Ratnapura or Kuruwita">
        
        <div class="map-container">
            <div id="map"></div>
        </div>
        
        <div id="map-error" class="error">
            Google Maps failed to load. Please refresh the page or check your internet connection.
        </div>
    </div>

    <div class="delivery-info" id="delivery-info" style="display:none;">
        <p><strong>Selected Address:</strong> <span id="address-text">-</span></p>
        <p><strong>Distance:</strong> <span id="distance-text">-</span> km</p>
        <p><strong>Delivery Charge:</strong> LKR <span id="delivery-charge">0.00</span></p>
    </div>

    <div class="total" id="total-with-delivery" style="display:none;">
        Total with Delivery: LKR <span id="final-total">0.00</span>
    </div>

    <button class="btn" id="place-order-btn" disabled onclick="showAgreement()">Place Order</button>
    <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">Back to Shopping</button>
</div>

<!-- Agreement Modal -->
<div class="modal" id="agreement-modal">
    <div class="modal-content">
        <h2>Cash on Delivery Agreement</h2>
        <p>By proceeding, you agree to:</p>
        <ul>
            <li>Pay the full amount in cash upon delivery</li>
            <li>Accept the delivery charges calculated based on distance</li>
            <li>Be available at the provided address during delivery</li>
            <li>Provide exact change if possible</li>
            <li>We only deliver to Ratnapura and Kuruwita areas</li>
        </ul>
        
        <div class="delivery-info">
            <p><strong>Order Total: LKR <span id="modal-total">0.00</span></strong></p>
            <p><strong>Delivery to: </strong><span id="modal-address">-</span></p>
            <p><strong>Distance: </strong><span id="modal-distance">-</span> km</p>
        </div>
        
        <div class="modal-buttons">
            <button class="btn btn-secondary" onclick="closeAgreement()">Cancel</button>
            <button class="btn" onclick="processOrder()">I Agree & Place Order</button>
        </div>
    </div>
</div>

<script>
// Global variables
let map, directionsService, directionsRenderer;
let userLocation = null;
let deliveryDistance = 0;
let deliveryCharge = 0;
const baseCharge = 80; // LKR for first km
const chargePerKm = 80; // LKR per additional km
let userMarker = null;
let storeMarker = null;

// Store location from PHP
const storeLocation = {
    lat: <?= $store_lat ?>,
    lng: <?= $store_lng ?>
};

// Initialize Google Maps
function initMap() {
    if (typeof google === 'undefined') {
        document.getElementById('map-error').style.display = 'block';
        return;
    }

    try {
        // Initialize map centered on Ratnapura
        map = new google.maps.Map(document.getElementById("map"), {
            center: { lat: 6.6844, lng: 80.3992 }, // Ratnapura center
            zoom: 12,
            restriction: {
                latLngBounds: {
                    north: 6.9,   // Ratnapura district bounds
                    south: 6.5,
                    east: 80.7,
                    west: 80.1
                },
                strictBounds: false
            }
        });

        // Initialize services
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(map);

        // Add store marker
        storeMarker = new google.maps.Marker({
            position: storeLocation,
            map: map,
            title: "Our Store",
            icon: {
                url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(`
                    <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="15" fill="#2d7a4e" stroke="#fff" stroke-width="2"/>
                        <text x="16" y="21" text-anchor="middle" fill="#fff" font-size="14" font-weight="bold">S</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 32)
            }
        });

        // Create autocomplete for address input
        const input = document.getElementById("address-input");
        const autocomplete = new google.maps.places.Autocomplete(input, {
            bounds: {
                north: 6.9,
                south: 6.5,
                east: 80.7,
                west: 80.1
            },
            componentRestrictions: { country: "lk" },
            fields: ["formatted_address", "geometry", "name"],
            types: ['establishment', 'geocode']
        });

        // When a place is selected
        autocomplete.addListener("place_changed", () => {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                alert("Please select a valid address from the suggestions");
                return;
            }

            userLocation = place.geometry.location;
            
            // Clear previous user marker
            if (userMarker) {
                userMarker.setMap(null);
            }

            // Add new user marker
            userMarker = new google.maps.Marker({
                position: userLocation,
                map: map,
                title: "Delivery Location",
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                }
            });

            // Center map on selected location
            map.setCenter(userLocation);
            map.setZoom(14);

            // Calculate distance and delivery charge
            calculateDistance();
        });

        // Also allow clicking on map to set location
        map.addListener("click", (mapsMouseEvent) => {
            userLocation = mapsMouseEvent.latLng;
            
            // Clear previous user marker
            if (userMarker) {
                userMarker.setMap(null);
            }

            // Add new user marker
            userMarker = new google.maps.Marker({
                position: userLocation,
                map: map,
                title: "Delivery Location",
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                }
            });

            // Reverse geocode to get address
            reverseGeocode(userLocation);
        });

        document.getElementById('map-error').style.display = 'none';

    } catch (error) {
        console.error('Google Maps error:', error);
        document.getElementById('map-error').style.display = 'block';
    }
}

// Reverse geocode to get address from coordinates
function reverseGeocode(latLng) {
    const geocoder = new google.maps.Geocoder();
    
    geocoder.geocode({ location: latLng }, (results, status) => {
        if (status === "OK" && results[0]) {
            document.getElementById("address-input").value = results[0].formatted_address;
            calculateDistance();
        } else {
            document.getElementById("address-input").value = "Location selected on map";
            calculateDistance();
        }
    });
}

// Calculate distance between store and user location
function calculateDistance() {
    if (!userLocation) return;

    const request = {
        origin: storeLocation,
        destination: userLocation,
        travelMode: google.maps.TravelMode.DRIVING,
    };

    directionsService.route(request, (result, status) => {
        if (status === "OK") {
            // Get distance in km
            const distanceInMeters = result.routes[0].legs[0].distance.value;
            deliveryDistance = distanceInMeters / 1000;
            
            // Calculate delivery charge (ALWAYS ROUND UP to nearest full km)
            const distanceKm = Math.ceil(deliveryDistance);
            const additionalKm = Math.max(0, distanceKm - 1);
            deliveryCharge = baseCharge + (additionalKm * chargePerKm);
            
            // Update UI
            document.getElementById("address-text").textContent = document.getElementById("address-input").value;
            document.getElementById("distance-text").textContent = deliveryDistance.toFixed(2);
            document.getElementById("delivery-charge").textContent = deliveryCharge.toFixed(2);
            
            // Show delivery info and update total
            document.getElementById("delivery-info").style.display = "block";
            
            const subtotal = <?= $total ?>;
            const finalTotal = subtotal + deliveryCharge;
            document.getElementById("final-total").textContent = finalTotal.toFixed(2);
            document.getElementById("total-with-delivery").style.display = "block";
            
            // Enable place order button
            document.getElementById("place-order-btn").disabled = false;
            
            // Draw route on map
            directionsRenderer.setDirections(result);
            
        } else {
            alert("Error calculating distance: " + status);
            // Fallback: use straight-line distance
            calculateStraightLineDistance();
        }
    });
}

// Fallback distance calculation using Haversine formula
function calculateStraightLineDistance() {
    if (!userLocation) return;
    
    const R = 6371; // Earth's radius in km
    const dLat = (userLocation.lat() - storeLocation.lat) * Math.PI / 180;
    const dLon = (userLocation.lng() - storeLocation.lng) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(storeLocation.lat * Math.PI / 180) * Math.cos(userLocation.lat() * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    deliveryDistance = R * c;
    
    // Calculate delivery charge (ALWAYS ROUND UP to nearest full km)
    const distanceKm = Math.ceil(deliveryDistance);
    const additionalKm = Math.max(0, distanceKm - 1);
    deliveryCharge = baseCharge + (additionalKm * chargePerKm);
    
    // Update UI
    document.getElementById("address-text").textContent = document.getElementById("address-input").value;
    document.getElementById("distance-text").textContent = deliveryDistance.toFixed(2);
    document.getElementById("delivery-charge").textContent = deliveryCharge.toFixed(2);
    
    document.getElementById("delivery-info").style.display = "block";
    
    const subtotal = <?= $total ?>;
    const finalTotal = subtotal + deliveryCharge;
    document.getElementById("final-total").textContent = finalTotal.toFixed(2);
    document.getElementById("total-with-delivery").style.display = "block";
    
    document.getElementById("place-order-btn").disabled = false;
}

// Show agreement modal
function showAgreement() {
    if (!userLocation) {
        alert("Please select a delivery location first");
        return;
    }
    
    // Update modal with current values
    const subtotal = <?= $total ?>;
    const finalTotal = subtotal + deliveryCharge;
    document.getElementById("modal-total").textContent = finalTotal.toFixed(2);
    document.getElementById("modal-address").textContent = document.getElementById("address-input").value;
    document.getElementById("modal-distance").textContent = deliveryDistance.toFixed(2);
    
    document.getElementById("agreement-modal").style.display = "flex";
}

// Close agreement modal
function closeAgreement() {
    document.getElementById("agreement-modal").style.display = "none";
}

// Process the order
function processOrder() {
    const address = document.getElementById("address-input").value;
    const subtotal = <?= $total ?>;
    const finalTotal = subtotal + deliveryCharge;
    
    // Create form data
    const formData = new FormData();
    formData.append('user_id', <?= $_SESSION['user_id'] ?>);
    formData.append('cart_items', JSON.stringify(<?= json_encode($cart) ?>));
    formData.append('subtotal', subtotal);
    formData.append('delivery_charge', deliveryCharge);
    formData.append('total_amount', finalTotal);
    formData.append('delivery_address', address);
    formData.append('delivery_distance', deliveryDistance);
    formData.append('user_lat', userLocation.lat());
    formData.append('user_lng', userLocation.lng());
    
    // Show loading state
    const btn = document.querySelector('#agreement-modal .btn');
    btn.innerHTML = 'Placing Order...';
    btn.disabled = true;
    
    // Send to server
    fetch('process_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order placed successfully!');
            window.location.href = 'order_confirmation.php?id=' + data.order_id;
        } else {
            alert('Error placing order: ' + data.message);
            btn.innerHTML = 'I Agree & Place Order';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error placing order. Please try again.');
        btn.innerHTML = 'I Agree & Place Order';
        btn.disabled = false;
    });
}
</script>

<!-- Load Google Maps -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= $google_maps_api_key ?>&libraries=places&callback=initMap" async defer></script>
</body>

</html>
