document.addEventListener("DOMContentLoaded", () => {
    const receiverInput = document.getElementById("receiver_id");
    const senderInput = document.getElementById("sender_id");

    if (!receiverInput || !senderInput) {
        console.error("Sender or Receiver ID element not found.");
        return;
    }

    const receiverId = receiverInput.value;
    const senderId = senderInput.value;

    if (!senderId) {
        console.error("Current user ID not found.");
        return;
    }

    // Use ws (not wss) with local IP since likely no SSL on Ratchet server directly
const socket = new WebSocket(`wss://nixten.ddns.net:8090/?receiver_id=${receiverId}`);

    socket.onopen = () => {
        console.log("Connected to WebSocket server");
    };

    socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        const messageContainer = document.getElementById("messages");

        const div = document.createElement("div");
        div.textContent = `User ${data.sender_id}: ${data.message}`;
        messageContainer.appendChild(div);

        messageContainer.scrollTop = messageContainer.scrollHeight;
    };

    socket.onerror = (error) => {
        console.error("WebSocket error:", error);
    };

    socket.onclose = () => {
        console.log("WebSocket connection closed");
    };

    document.getElementById("chat-form").addEventListener("submit", (e) => {
        e.preventDefault();

        const messageInput = document.getElementById("message-input");
        const message = messageInput.value.trim();

        if (message === "") return;

        const payload = {
            sender_id: senderId,
            receiver_id: receiverId,
            message: message
        };

        socket.send(JSON.stringify(payload));
        messageInput.value = "";
    });
});
