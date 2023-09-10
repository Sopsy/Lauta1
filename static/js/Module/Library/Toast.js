export class Toast
{
    static success(message, title = null, options = {})
    {
        this._show('success', message, title, options);
    }

    static info(message, title = null, options = {})
    {
        this._show('info', message, title, options);
    }

    static warning(message, title = null, options = {})
    {
        this._show('warning', message, title, options);
    }

    static error(message, title = null, options = {})
    {
        this._show('error', message, title, options);
    }

    static _show(type, message, title, options)
    {
        let defaultOptions = {};
        if (typeof window.config === 'object' && typeof window.config.toast === 'object') {
            defaultOptions = window.config.toast;
        }

        options = Object.assign({
            displayTime: 3000,
            fadeTime: 2000,
        }, defaultOptions, options);

        let toastRoot = document.getElementById('toast-root');
        if (toastRoot === null) {
            toastRoot = document.createElement('div');
            toastRoot.id = 'toast-root';
            document.body.appendChild(toastRoot);
        }

        let toast = document.createElement('div');
        toast.classList.add('toast', type);

        let toastContent = document.createElement('div');
        toastContent.classList.add('toast-content');
        toast.appendChild(toastContent);

        if (title !== null) {
            let toastTitle = document.createElement('h3');
            toastTitle.textContent = title;
            toastContent.appendChild(toastTitle);
        }

        let toastMessage = document.createElement('p');
        toastMessage.textContent = message;
        toastContent.appendChild(toastMessage);

        toastRoot.appendChild(toast);

        toast.addEventListener('click', function(e) {
            e.currentTarget.removeToast();
        });

        toast.removeToast = function () {
            toast.remove();

            if (toastRoot.querySelector('.toast') === null) {
                toastRoot.remove();
            }
        };

        toast.startFade = function () {
            toast.classList.add('fading');
            toast.style.transitionDuration = options.fadeTime / 1000 + 's';
        };

        if (options.displayTime !== -1) {
            let fading, removing;
            fading = setTimeout(toast.startFade, options.displayTime);
            removing = setTimeout(toast.removeToast, options.displayTime + options.fadeTime);

            toast.addEventListener('mouseover', function (e) {
                clearTimeout(fading);
                clearTimeout(removing);
                e.currentTarget.classList.remove('fading');
                e.currentTarget.style.transitionDuration = '';
            });

            toast.addEventListener('mouseout', function (e) {
                fading = setTimeout(toast.startFade, options.displayTime);
                removing = setTimeout(toast.removeToast, options.displayTime + options.fadeTime);
            });
        }
    }
}