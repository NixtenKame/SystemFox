document.addEventListener('DOMContentLoaded', () => {
    // Check if the user has already clicked the button (from localStorage)
    if (localStorage.getItem("notificationsRequested") === "true") {
        const button = document.getElementById('requestNotifications');
        if (button) {
            button.style.display = 'none';  // Hide the button if user has already clicked it
        }
    } else {
        // Show the button if the user hasn't clicked it
        const button = document.getElementById('requestNotifications');
        if (button) {
            button.style.display = 'block';  // Only show button if not clicked before
        }
    }

    // Request notification permission after the user clicks the button
    document.getElementById('requestNotifications').addEventListener('click', async () => {
        // Request notification permission from the user
        const permission = await Notification.requestPermission();
        if (permission === "granted") {
            console.log("Notification permission granted");
            registerPush();  // Register push notifications
        } else {
            console.warn("Notification permission denied");
        }

        // Hide the button after the user clicks it
        const button = document.getElementById('requestNotifications');
        if (button) {
            button.style.display = 'none';  // Hide the button after it’s clicked
        }

        // Store the user’s action in localStorage to persist across refreshes
        localStorage.setItem("notificationsRequested", "true");
    });
});

// Your existing registerPush function (no changes needed)
async function registerPush() {
    // Check for service worker and PushManager support
    if (!("serviceWorker" in navigator)) {
        console.warn("Service workers are not supported in this browser.");
        return;
    }
    if (!("PushManager" in window)) {
        console.warn("Push notifications are not supported in this browser.");
        return;
    }

    // Check HTTPS (required for push notifications)
    if (location.protocol !== "https:" && location.hostname !== "localhost") {
        console.warn("Push notifications require HTTPS.");
        return;
    }

    try {
        // Register the service worker
        const reg = await navigator.serviceWorker.register("/service-worker.js");
        console.log("Service worker registered:", reg);

        // Get existing subscription or create a new one
        const sub = await reg.pushManager.getSubscription() || await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(
                "BMCC_mvEPfFMgBaHhUkysj5nUUIB-Bk7ri3RT0L-2yzniY9sFZHL4xsI9LBKhtdDzQ8tADM9D2kBzNqtcZzuZU8"
            )
        });

        console.log("Push subscription object:", sub);

        // Convert subscription to plain JS object
        const subObj = sub.toJSON();
        console.log("Subscription object to send:", subObj);

        // Send subscription to server
        const response = await fetch("/actions/save_push", {
            method: "POST",
            headers: { 
                "Content-Type": "application/json",
                "X-CSRF-Token": CSRF_TOKEN  // include if your site uses CSRF
            },
            body: JSON.stringify(subObj)
        });

        // Log response for debugging
        console.log("Server response status:", response.status);
        const text = await response.text();
        console.log("Server response text:", text);

        if (response.ok) {
            console.log("Subscription saved successfully.");
        } else {
            console.error("Failed to save subscription:", text);
        }

    } catch (err) {
        console.error("Push registration failed:", err);
    }
}

// Convert VAPID key
function urlBase64ToUint8Array(base64) {
    const padding = "=".repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, "+").replace(/_/g, "/"));
    return Uint8Array.from([...raw].map(ch => ch.charCodeAt(0)));
}
