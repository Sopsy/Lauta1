// WTF Apple? Why would window width ever be 0?
if (window.innerWidth === 0) {
    let retries = 1;
    let interval = setInterval(() => {
        console.log('Window width was 0, retry ' + retries + ': ' + window.innerWidth);

        if (window.innerWidth === 0) {
            ++retries;
        } else {
            render();
            clearInterval(interval);
        }

        if (retries > 10) {
            console.info('Exceeded 10 retries for window width checks, it was always 0. Giving up.');
            clearInterval(interval)
        }
    }, 100);
} else {
    render();
}

function render() {
    for (let ad of document.querySelectorAll('[data-adf]')) {
        let placementId = 0;

        if (window.innerWidth < 980) {
            // Mobile ads
            if (!ad.dataset.placementIdM) {
                continue;
            }

            placementId = parseInt(ad.dataset.placementIdM);
        } else {
            // Desktop ads
            if (!ad.dataset.placementIdD) {
                continue;
            }

            placementId = parseInt(ad.dataset.placementIdD);
        }

        if (placementId === 0 || isNaN(placementId)) {
            continue;
        }

        // Render banner
        let script = document.createElement('script');
        let adfScript = 'adx.adform.net/adx/?mid=' + placementId;
        if (ad.dataset.areaId) {
            adfScript += '&mkv=area:' + ad.dataset.areaId;
        }
        script.dataset.adfscript = adfScript;

        ad.appendChild(script);
    }
}