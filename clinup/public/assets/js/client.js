// public/client.js
document.addEventListener('DOMContentLoaded', function () {
    const sessionIdInput = document.getElementById('session-id');
    const sessionId = new URLSearchParams(window.location.search).get('session_id');
    if (sessionId) {
        sessionIdInput.value = sessionId;
    }
});
