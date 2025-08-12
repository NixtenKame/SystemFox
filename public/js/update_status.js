// Check that the script is loaded
console.log("update_status.js loaded successfully!");

// Function to update online status
function updateOnlineStatus() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "/scripts/update_status.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            console.log("Online status updated: " + xhr.responseText);
        }
    };
    xhr.send();
}

// Function to set the user status to offline
function setOfflineStatus() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "/scripts/set_offline.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            console.log("Offline status set: " + xhr.responseText);
        }
    };
    xhr.send();
}

// Update online status every 5 minutes (300000 milliseconds)
setInterval(updateOnlineStatus, 300000);

// Update online status when the page loads
window.onload = updateOnlineStatus;

// Set the user status to offline when the user closes the tab or leaves the website
window.addEventListener('beforeunload', function (event) {
    setOfflineStatus();
});