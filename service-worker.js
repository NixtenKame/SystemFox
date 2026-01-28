self.addEventListener("push", event => {
    const data = event.data ? event.data.json() : {};

    event.waitUntil(
        self.registration.showNotification(data.title || "New Message", {
            body: data.body || "",
            data: data.url || "/c/"
        })
    );
});

self.addEventListener("notificationclick", event => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data)
    );
});
