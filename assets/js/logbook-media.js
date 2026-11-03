window.renderLogbookDetail = function (container, detail) {
    const lines = String(detail || '').split(/\r?\n/);
    const textLines = [];
    const mediaUrls = [];

    lines.forEach(function (line) {
        const match = line.match(/^Lampiran:\s*(https?:\/\/\S+)\s*$/);
        if (!match) {
            textLines.push(line);
            return;
        }

        try {
            const url = new URL(match[1], window.location.href);
            if (url.origin === window.location.origin &&
                /^\/(?:[^/]+\/)*uploads\/logbook\/[a-f0-9]{32}\.(?:jpg|png|gif|webp|mp4|webm|mov)$/i.test(url.pathname)) {
                mediaUrls.push(url.href);
                return;
            }
        } catch (error) {
            console.warn('Lampiran logbook tidak dapat ditampilkan.');
        }
        textLines.push(line);
    });

    container.replaceChildren();
    const text = textLines.join('\n').trim();
    if (text) {
        const description = document.createElement('div');
        description.style.whiteSpace = 'pre-wrap';
        description.textContent = text;
        container.append(description);
    }

    if (mediaUrls.length) {
        const gallery = document.createElement('div');
        gallery.className = 'logbook-media-gallery';
        mediaUrls.forEach(function (url) {
            const isVideo = /\.(?:mp4|webm|mov)$/i.test(new URL(url).pathname);
            const media = isVideo ? document.createElement('video') : document.createElement('img');
            media.src = url;
            if (isVideo) {
                media.controls = true;
                media.preload = 'metadata';
            } else {
                media.alt = 'Dokumentasi kegiatan siswa';
            }
            gallery.append(media);
        });
        container.append(gallery);
    }

    if (!text && !mediaUrls.length) {
        container.textContent = '-';
    } else if (!text) {
        const caption = document.createElement('p');
        caption.textContent = 'Dokumentasi terlampir.';
        container.prepend(caption);
    }
};
