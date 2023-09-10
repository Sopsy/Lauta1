import {Ajax} from './Library/Ajax.js';
import {Toast} from './Library/Toast.js';

export class Theme
{
    static getEvents()
    {
        return {
            changeDisplayStyle: {
                fn: Theme.changeDisplayStyle,
            },
            sidebarToggle: {
                fn: () => {
                    document.body.classList.toggle('sidebar-visible')
                    document.body.classList.remove('boardnav-open');
                },
            },
            sidebarHide: {
                fn: Theme.toggleSidebarHide,
            },
            shadowHide: {
                fn: (e) => {
                    if (e.offsetX > e.currentTarget.clientWidth) {
                        document.body.classList.toggle('sidebar-visible');
                    }
                },
            },
            toggleBoardSelector: {
                fn: Theme.toggleBoardSelector,
            },
        };
    }

    static changeDisplayStyle(e)
    {
        let id = e.currentTarget.dataset.style;
        Ajax.post('/scripts/ajax/savedisplaystyle.php', {style_id: id}).onLoad((data) => {
            if (data.responseText.length !== 0) {
                Toast.error(data.responseText);
            } else {
                window.location = window.location.href.replace(/\-[0-9]+\//g, '/');
            }
        });
    }

    static toggleSidebarHide()
    {
        document.body.classList.toggle('no-sidebar');
        let hide = document.body.classList.contains('no-sidebar');
        if (hide) {
            document.body.classList.remove('sidebar-visible');
        }

        Ajax.post('/scripts/ajax/toggle_sidebar.php', {
            'hideSidebar': hide,
        });
    }

    static toggleBoardSelector(e)
    {
        if (e.target !== e.currentTarget && e.target !== e.currentTarget.firstChild && e.currentTarget.firstChild.tagName === 'SPAN') {
            // Clicks on childs does not close the menu
            return;
        }
        document.body.classList.toggle('boardnav-open');
        document.body.classList.remove('sidebar-visible');
    }

    static loadingHtml(asFragment = false, classes = '')
    {
        if (classes !== '') {
            classes += ' ';
        }

        let html = '<span class="' + classes + 'loading"><img src="https://static.ylilauta.org/img/loading.gif" alt="' + messages.loading + '"></span>';

        if (asFragment) {
            let template = document.createElement('template');
            template.innerHTML = html;

            return template.content;
        }

        return html;
    }

    static startLoading(element, captcha = false)
    {
        let template = document.createElement('template');

        if (!captcha) {
            template.innerHTML = '<span class="formloading"><img src="https://static.ylilauta.org/img/loading.gif" alt="' + messages.loading + '"></span>';
        } else {
            template.innerHTML = '<span class="formloading"><p>' + window.messages.captchaRunning + '</p><img src="https://static.ylilauta.org/img/loading.gif" alt="' + messages.loading + '"></span>';
        }
        element.appendChild(template.content);
    }

    static stopLoading(element)
    {
        element.querySelector('.formloading').remove()
    }
}
