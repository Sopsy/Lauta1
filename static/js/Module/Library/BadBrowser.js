export class BadBrowser
{
    static isBadBrowser()
    {
        if (!'FormData' in window) {
            return true;
        }

        if (!'localStorage' in window) {
            return true;
        }

        if (!'sendBeacon' in navigator) {
            return true;
        }

        return false;
    }

    static browserWarning()
    {
        let browserWarning = document.createElement('div');
        browserWarning.classList.add('old-browser-warning');
        browserWarning.innerHTML = '<p>' + messages.oldBrowserWarning + '</p>';

        document.body.appendChild(browserWarning);
    }
}