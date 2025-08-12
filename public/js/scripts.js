// Check that the script is loaded
console.log("scripts.js loaded successfully!");

<script src="https://kit.fontawesome.com/8d091fb1f3.js" crossorigin="anonymous"></script>


// Toggle menu visibility
function toggleMenu() {
    const nav = document.querySelector("nav");
    nav.classList.toggle("active");
}

document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.querySelector("#menuToggle");
    if (menuToggle) {
        menuToggle.addEventListener("click", toggleMenu);
    }
});

// Check if cookie consent has been given
if (!localStorage.getItem('cookieConsent')) {
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('cookie-consent').style.display = 'flex';
    });
}

// Function to accept cookies
function acceptCookies() {
    const consentData = {
        essential: true, // Always accept essential cookies
        analytics: true, // Example of accepting analytics cookies
        marketing: false // Example of rejecting marketing cookies
    };

    // Store detailed consent information in localStorage
    localStorage.setItem('cookieConsent', JSON.stringify(consentData)); // Store consent data as JSON
    document.getElementById('cookie-consent').style.display = 'none'; // Hide the consent prompt

    // You can also send this data to your server if needed, for example:
    // fetch('/api/trackConsent', { method: 'POST', body: JSON.stringify(consentData), headers: { 'Content-Type': 'application/json' } });
}

function updateClock() {
    // Get the current time from the server's initial load
    let serverTime = new Date("<?php echo $currentTime; ?>");

    function tick() {
        // Increment time by 1 second
        serverTime.setSeconds(serverTime.getSeconds() + 1);

        // Format the time
        let options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true, 
            timeZone: 'America/Chicago' 
        };

        // Update the clock display
        document.getElementById("serverTime").innerHTML = serverTime.toLocaleString("en-US", options);
    }

    // Update every second
    setInterval(tick, 1000);
}

document.addEventListener("DOMContentLoaded", function() {
    if (window.matchMedia("(hover: none)").matches) {
        document.querySelectorAll(".dropdown").forEach(menu => {
            menu.addEventListener("click", function(event) {
                event.stopPropagation();
                this.classList.toggle("open");
            });
        });

        document.addEventListener("click", function() {
            document.querySelectorAll(".dropdown.open").forEach(menu => {
                menu.classList.remove("open");
            });
        });
    }
});

tinymce.init({
    selector: 'textarea[name="comment"]',
    menubar: false,  // Disable the menu bar
    plugins: 'lists link image charmap preview anchor',  // Plugins you want to use
    toolbar: 'undo redo | bold italic underline | fontselect fontsizeselect | alignleft aligncenter alignright | link image | bullist numlist',  // Toolbar buttons
    statusbar: false,  // Disable the status bar
    height: 200,  // Set the height of the editor
});

document.addEventListener("DOMContentLoaded", function () {
    const favoriteForm = document.querySelector("form[action='../actions/favorite.php']");
    if (favoriteForm) {
        favoriteForm.addEventListener("submit", function (event) {
            console.log("Favorite button clicked!");
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form[action='../actions/favorite.php']");

    if (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault(); // Stop normal form submission
            console.log("Submitting favorite form...");

            // Send manually using Fetch API
            fetch(form.action, {
                method: "POST",
                body: new FormData(form),
            })
            .then(response => response.text())
            .then(data => console.log("Server Response:", data))
            .catch(error => console.error("Error:", error));
        });
    }
});

function deletePost(imageId) {
    if (confirm("Are you sure you want to delete this post?")) {
        fetch('/posts/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `image_id=${imageId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Post deleted successfully.");
                window.location.href = "/"; // Redirect after deletion
            } else {
                alert("Error deleting post: " + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // Handle Like button
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function () {
            let commentId = this.getAttribute('data-comment-id');
            // Send AJAX request to update like count
            fetch(`/api/like_comment.php?comment_id=${commentId}&action=like`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the like count dynamically
                        let likesCount = this.innerText.match(/\d+/);
                        this.innerText = `Like (${parseInt(likesCount[0]) + 1})`;
                    } else {
                        alert("Failed to like the comment.");
                    }
                });
        });
    });

    // Handle Dislike button
    document.querySelectorAll('.dislike-btn').forEach(button => {
        button.addEventListener('click', function () {
            let commentId = this.getAttribute('data-comment-id');
            // Send AJAX request to update dislike count
            fetch(`/api/like_comment.php?comment_id=${commentId}&action=dislike`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the dislike count dynamically
                        let dislikesCount = this.innerText.match(/\d+/);
                        this.innerText = `Dislike (${parseInt(dislikesCount[0]) + 1})`;
                    } else {
                        alert("Failed to dislike the comment.");
                    }
                });
        });
    });

    // Handle Reply button (This part can be customized for reply functionality)
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function () {
            let commentId = this.getAttribute('data-comment-id');
            let replyForm = document.createElement('form');
            replyForm.innerHTML = `<textarea name="reply" rows="3" cols="60"></textarea><br><input type="submit" value="Post Reply">`;
            this.parentNode.appendChild(replyForm);

            replyForm.addEventListener('submit', function (e) {
                e.preventDefault();
                let replyText = this.querySelector('textarea').value;
                // Send the reply via AJAX
                fetch(`/api/reply_comment.php?comment_id=${commentId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reply=${encodeURIComponent(replyText)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Reply posted successfully!");
                    } else {
                        alert("Failed to post reply.");
                    }
                });
            });
        });
    });
});

fetch('favorite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ image_id: $imageId })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error(error));

document.getElementById("favorite-btn").addEventListener("click", function(event) {
    event.preventDefault();

    let imageId = this.getAttribute("data-image-id");
    let button = this;

    fetch("/actions/favorite", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ image_id: imageId })
    })
    .then(response => response.json())
    .then(data => {
        button.textContent = data.status === "added" ? "Unfavorite" : "Favorite";
    })
    .catch(error => console.error("Error:", error));
});

document.addEventListener("DOMContentLoaded", function () {
    let seenComments = new Set();
    
    document.querySelectorAll("ul li").forEach(comment => {
        let commentId = comment.querySelector(".like-btn")?.getAttribute("data-comment-id");
        if (seenComments.has(commentId)) {
            comment.remove(); // Remove duplicate comment
        } else {
            seenComments.add(commentId);
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    let seenComments = new Set();

    document.querySelectorAll("li.comment-item").forEach(comment => {
        let commentText = comment.querySelector(".comment-text").innerText.trim();
        let username = comment.querySelector(".comment-username").innerText.trim();
        
        let uniqueKey = username + "|" + commentText;

        if (seenComments.has(uniqueKey)) {
            comment.style.display = "none"; // Hide duplicate
        } else {
            seenComments.add(uniqueKey);
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const tagInput = document.getElementById("tagInput");
    const tagContainer = document.getElementById("tagContainer");
    const hiddenTagField = document.getElementById("tags");
    const fileInput = document.getElementById("file");
    const previewImage = document.getElementById("previewImage");

    let tags = [];

    tagInput.addEventListener("keydown", function(event) {
        if (event.key === "Enter" && tagInput.value.trim() !== "") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = " "; // Clear input field after adding tag
        }
    });

    fileInput.addEventListener("change", function() {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = "block";
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.style.display = "none";
        }
    });

    function addTag(tag) {
        if (!tags.includes(tag)) {
            tags.push(tag);
            updateTags();
        }
    }

    function removeTag(tag) {
        tags = tags.filter(t => t !== tag);
        updateTags();
    }

    function updateTags() {
        tagContainer.innerHTML = "";
        tags.forEach(tag => {
            let tagElement = document.createElement("span");
            tagElement.classList.add("tag");
            tagElement.textContent = tag;
            tagElement.onclick = () => removeTag(tag);
            tagContainer.appendChild(tagElement);
        });

        hiddenTagField.value = tags.join(", "); // Update the hidden input
    }
});

function uploadFile() {
    let formData = new FormData(document.getElementById("uploadForm"));
    let xhr = new XMLHttpRequest();

    xhr.upload.addEventListener("progress", function(event) {
        if (event.lengthComputable) {
            let percentComplete = (event.loaded / event.total) * 100;
            document.getElementById("progressBar").value = percentComplete;
            document.getElementById("progressText").innerText = Math.round(percentComplete) + "%";
        }
    });

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
            let response = JSON.parse(xhr.responseText);
            if (response.status === "success") {
                document.getElementById("uploadStatus").innerHTML = "<span style='color:green;'>Upload successful!</span>";
            } else {
                document.getElementById("uploadStatus").innerHTML = "<span style='color:red;'>Error: " + response.message + "</span>";
            }
        }
    };

    xhr.open("POST", "/upload/new", true);
    xhr.send(formData);
}

/*
COPYRIGHT 2025 FLUFFFOX THIS CSS FILE IS PART OF THE NIXTEN.DDDNS.NET SYSTEM DO NOT MODIFY OR REDISTRIBUTE WITHOUT PERMISSION
*/