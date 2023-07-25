// Function to get a cookie
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Function to set a cookie
function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

// Function to add event listeners to an audio element
function handleAudioElement(audioElement) {
    audioElement.addEventListener('ended', (event) => {
        let playedAudios = JSON.parse(getCookie('playedAudios') || '[]');
        playedAudios.push(audioElement.src);
        setCookie('playedAudios', JSON.stringify(playedAudios), 7);
    });

    audioElement.addEventListener('play', (event) => {
        if (document.hidden) {
            event.preventDefault();
        } else {
            let playedAudios = JSON.parse(getCookie('playedAudios') || '[]');
            if (playedAudios.includes(audioElement.src)) {
                event.preventDefault();
            }
        }
    });
}

// Add event listeners to all existing audio elements
document.querySelectorAll('audio').forEach(handleAudioElement);

// Observe the body for additions of new audio elements
let observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.addedNodes) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.tagName === 'AUDIO') { // If it's an element node and it's an audio
                    handleAudioElement(node);
                }
            });
        }
    });
});

observer.observe(document.body, {
    childList: true, // This is set to true to observe additions or removals of child nodes
    subtree: true // This is set to true to extend the observation to the whole subtree of nodes
});
