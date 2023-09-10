import {Modal} from './Library/Modal.js';
import {Ajax} from './Library/Ajax.js';
import {Theme} from './Theme.js';
import {YBoard} from '../YBoard.js';

export class Notifications
{
    static getEvents()
    {
        return {
            notificationsOpen: {
                event: 'click',
                fn: Notifications.open,
            },
            notificationsMarkAllRead: {
                event: 'click',
                fn: Notifications.markAllRead,
            },
            notificationsMarkRead: {
                event: 'click',
                fn: Notifications.markRead,
            },
        };
    }

    static open(e)
    {
        let postXhr = null;

        new Modal({
            'title': messages.notifications,
            'content': Theme.loadingHtml(),
            'onOpen': function (modal) {
                modal.elm.style.willChange = 'contents';
                postXhr = Ajax.post('/scripts/notifications/get.php', {}, {
                    'errorFunction': null,
                }).onLoad(function (xhr) {
                    if (modal.elm === null) {
                        return;
                    }
                    modal.setContent(xhr.responseText);
                    modal.elm.style.willChange = '';
                    Notifications.initModal(modal);
                    Notifications.updateUnreadCount(modal.elm);
                }).onError(function (xhr) {
                    if (xhr.responseText.length !== 0) {
                        try {
                            let json = JSON.parse(xhr.responseText);
                            modal.setContent(json.message);
                        } catch (e) {
                            modal.setContent(messages.invalidResponse);
                        }
                    } else {
                        modal.setContent(messages.emptyResponse);
                    }
                });
                postXhr = postXhr.getXhrObject();
            },
            'onClose': function () {
                if (postXhr !== null && postXhr.readyState !== 4) {
                    postXhr.abort();
                }
            },
        });
    }

    static initModal(modal)
    {
        // Clicking a link to go to the post
        for (let link of modal.elm.querySelectorAll('a')) {
            link.addEventListener('mousedown', (e) => {
                // Mark as read
                let notification = e.target.closest('.notification');
                if (notification.classList.contains('not-read')) {
                    notification.classList.remove('not-read');
                    let beaconUrl = '/scripts/notifications/markread.php';
                    let data = new FormData();
                    data.append('id', notification.dataset.id);
                    data.append('csrfToken', user.csrfToken);
                    navigator.sendBeacon(beaconUrl, data);
                }
            });
        }

        YBoard.initElement(modal.elm);
    }

    static markRead(e)
    {
        let notification = e.target.closest('.notification');
        notification.classList.remove('not-read');
        notification.classList.add('is-read');

        Ajax.post('/scripts/notifications/markread.php', {id: notification.dataset.id}).onError(function () {
            notification.classList.add('not-read');
            notification.classList.remove('is-read');
        });

        Notifications.updateUnreadCount(e.target.closest('.notification-list'));
    }

    static markAllRead(e)
    {
        e.target.closest('.modal').querySelectorAll('.notification.not-read').forEach(function (elm) {
            elm.classList.remove('not-read');
            elm.classList.add('is-read');
        });

        Ajax.post('/scripts/notifications/markread.php', {id: 'all'});
        Notifications.setUnreadCount(0);
    }

    static updateUnreadCount(elm)
    {
        Notifications.setUnreadCount(Notifications.getUnreadCount(elm));
    }

    static getUnreadCount(elm)
    {
        return elm.querySelectorAll('.notification.not-read').length;
    }

    static setUnreadCount(count)
    {
        count = parseInt(count);
        if (count >= 100) {
            count = ':D';
        }

        for (let elm of document.querySelectorAll('.unread-notifications')) {
            elm.textContent = count;

            if (count === 0) {
                elm.classList.add('none');
            } else {
                elm.classList.remove('none');
            }
        }
    }
}
