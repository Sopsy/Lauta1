import {Ajax} from './Library/Ajax.js';
import {Toast} from './Library/Toast.js';
import {YBoard} from '../YBoard.js';
import {Theme} from './Theme.js';
import {PostForm} from './PostForm.js';
import {Post} from './Post.js';
import {PostFile} from "./PostFile.js";
import {Modal} from "./Library/Modal.js";

export class Thread
{
    static getEvents()
    {
        return {
            threadDelete: {
                fn: Thread.delete,
            },
            threadHide: {
                fn: Thread.hide,
            },
            threadUnhide: {
                fn: Thread.unhide,
            },
            threadFollow: {
                fn: Thread.follow,
            },
            threadUnfollow: {
                fn: Thread.unfollow,
            },
            threadUnfollowFromBox: {
                fn: Thread.unfollowFromBox,
            },
            threadClearFollowed: {
                fn: Thread.clearFollowed,
            },
            threadMoreReplies: {
                fn: Thread.moreReplies,
            },
            threadLessReplies: {
                fn: Thread.lessReplies,
            },
            threadReply: {
                fn: Thread.reply,
            },
            threadUpdate: {
                fn: Thread.update,
            },
            threadUpdateFollowBox: {
                fn: Thread.updateFollowBox,
            },
            threadHideFollowBox: {
                fn: Thread.hideFollowBox,
            },
            threadManage: {
                fn: Thread.manage,
            }
        }
    }

    static manage(e)
    {
        let id;
        if (e.target.dataset.id) {
            id = e.target.dataset.id;
        } else {
            id = Thread.getElmFromEvent(e).dataset.threadId;
        }
        window.location = '/mod/index.php?action=updatethread&id=' + id;
    }

    static hideFollowBox(e)
    {
        document.getElementById('followedthreads').remove();
    }

    static getElm(id)
    {
        return document.getElementById('t' + id);
    }

    static delete(e)
    {
        let id, elm;
        if (e.target.dataset.id) {
            elm = e.target.closest('.post');
            id = e.target.dataset.id;
        } else {
            elm = Thread.getElmFromEvent(e);
            id = elm.dataset.threadId;
        }

        new Modal({
            title: messages.thread.delete,
            content: '<form action="/scripts/ajax/deletethread.php" method="post">' +
                '<input type="hidden" name="id" value="' + id + '">' +
                '<p>' + messages.thread.deleteConfirm + '</p>' +
                '<div class="buttons">' +
                '<button class="linkbutton" type="button" data-e="modalClose">' + messages.cancel + '</button>' +
                '<button class="linkbutton" type="submit">' + messages.thread.delete + '</button>' +
                '</div></form>',
            onOpen: (modal) => {
                modal.content.querySelector('form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    modal.close();

                    let formData = new FormData(e.currentTarget);

                    Ajax.post(e.currentTarget.getAttribute('action'), formData).onLoad(() => {
                        if (document.getElementById('right') && document.getElementById(
                            'right').classList.contains('thread-page')) {
                            // We're in the thread we just deleted
                            YBoard.returnToBoard();
                        } else {
                            // The deleted post is a thread and not the opened thread
                            elm.remove();
                        }
                        Toast.success(messages.thread.deleted);
                    });
                });
            }
        });
    }

    static hide(e)
    {
        let thread = Thread.getElmFromEvent(e);
        let threadId = thread.dataset.threadId;

        let subject = document.createTextNode(thread.querySelector('.postsubject .subject').textContent);
        let template = document.createElement('template');
        template.innerHTML = '<p class="thread just-hidden" data-thread-id="' + threadId + '">\
            <span class="hiddensubject"></span>\
            <button data-e="threadUnhide" class="icon-button icon-plus" title="' + messages.thread.hide.remove + '"></button>\
            </p>';
        template.content.querySelector('.hiddensubject').appendChild(subject);

        let hiddenThread = document.importNode(template.content, true);
        YBoard.initElement(hiddenThread);

        thread.parentNode.insertBefore(hiddenThread, thread.nextSibling);
        thread.style.display = 'none';

        PostFile.stopAllMedia(thread);

        Ajax.post('/scripts/ajax/hide_ping.php', {
            add: 'true',
            id: threadId
        });
    }

    static unhide(e)
    {
        let thread = Thread.getElmFromEvent(e);
        let threadId = thread.dataset.threadId;

        Thread.getElm(threadId).style.display = '';
        thread.remove();

        Ajax.post('/scripts/ajax/hide_ping.php', {
            add: 'false',
            id: threadId
        });
    }

    static follow(e)
    {
        let thread = Thread.getElmFromEvent(e);
        let threadId = thread.dataset.threadId;
        thread.classList.add('followed');

        Ajax.post('/scripts/ajax/follow.php', {id: threadId}).onLoad((text) => {
            text = text.responseText;
            if (text.length === 0) {
                Thread.updateFollowBox(null, false);
            } else {
                thread.classList.remove('followed');
                Toast.error(text);
            }
        });
    }

    static unfollow(e)
    {
        let thread = Thread.getElmFromEvent(e);
        let threadId = thread.dataset.threadId;
        thread.classList.remove('followed');

        Ajax.post('/scripts/ajax/follow.php', {
            id: threadId,
            do: "remove"
        }).onLoad((text) => {
            text = text.responseText;
            if (text.length === 0) {
                Thread.updateFollowBox(null, false);
            } else {
                thread.classList.add('followed');
                Toast.error(text);
            }
        });
    }

    static unfollowFromBox(e)
    {
        Thread.unfollow(e);
        e.target.parentElement.remove();
    }

    static clearFollowed(e)
    {
        if(!confirm(messages.thread.subscribe.removeAllConfirm)) {
            return false;
        }

        Ajax.post('/scripts/ajax/follow.php', {
            id: 'all',
            do: "remove"
        }).onLoad((text) => {
            text = text.responseText;
            if (text.length === 0) {
                for (let thread of document.querySelectorAll('.thread.followed')) {
                    thread.classList.remove('followed');
                }
                Thread.updateFollowBox(null, false);
            } else {
                Toast.error(text);
            }
        });
    }

    static updateVisibleReplyCount(id)
    {
        Thread.getElm(id).querySelector('.reply-count-visible').textContent =
            Thread.getElm(id).querySelectorAll('.answer').length;
    }

    static lessReplies(e)
    {
        let thread = Thread.getElmFromEvent(e);
        let id = thread.dataset.threadId;

        let container = thread.querySelector('.expand-container');
        if (container) {
            thread.querySelector('.expand-container').remove();
        }
        thread.querySelector('.morereplies').classList.remove('hidden');
        thread.querySelector('.lessreplies').classList.remove('visible');
        Thread.updateVisibleReplyCount(id);
    }

    static moreReplies(e)
    {
        let thread = Thread.getElmFromEvent(e);
        let id = thread.dataset.threadId;
        if ('expanding' in thread.dataset) {
            return false;
        }
        thread.dataset.expanding = 'true';

        let container = thread.querySelector('.expand-container');
        if (!container) {
            container = document.createElement('div');
            container.classList.add('expand-container');
        }
        thread.querySelector('.answers').prepend(container);

        // Show the loading icon
        let template = document.createElement('template');
        template.innerHTML = Theme.loadingHtml();
        container.prepend(template.content);

        let fromId = 0;
        if (thread.querySelector('.answer')) {
            fromId = thread.querySelector('.answer').dataset.id;
        }
        let loadCount = 10;
        Ajax.post('/scripts/ajax/expand.php', {
            'id': id,
            'count': loadCount,
            'start': fromId
        }).onLoad((xhr) => {
            let text = xhr.responseText;
            container.querySelector('.loading').remove();
            if (text.length === 0) {
                thread.querySelector('.morereplies').classList.add('hidden');
            } else {
                let template = document.createElement('template');
                template.innerHTML = text;

                let loaded = template.content.querySelectorAll('.post').length;
                if (loaded < loadCount) {
                    thread.querySelector('.morereplies').classList.add('hidden');
                }

                YBoard.initElement(template.content);

                thread.querySelector('.expand-container').prepend(template.content);
                thread.querySelector('.lessreplies').classList.add('visible');

                Thread.updateVisibleReplyCount(id);
                Post.updateQuoteSuffixes();
            }
            delete thread.dataset.expanding;
        });
    }

    static reply(e)
    {
        let thread = Thread.getElmFromEvent(e);

        PostForm.replyingTo(thread);

        PostForm.getMsgElm().focus();
    }

    static update(e)
    {
        let thread = Thread.getElmFromEvent(e);

        if (!'threadAutoUpdater' in window) {
            Toast.error(messages.thread.autoUpdater.notInitialized);
            return false;
        }

        window.threadAutoUpdater.runOnce(thread.dataset.threadId, true);
    }

    static updateFollowBox(e = null, manual = true)
    {
        let followBox = document.getElementById('followedcontent');
        if (!followBox) {
            return;
        }

        let button = false;
        if (e) {
            button = e.currentTarget;
            button.setAttribute('disabled', true);
        }

        Ajax.get('/scripts/ajax/ftupdate.php').onLoad((xhr) => {
            followBox.innerHTML = xhr.responseText;
            YBoard.initElement(followBox);
            if (button) {
                button.removeAttribute('disabled');
            }
            if (manual) {
                Toast.success(messages.thread.subscribe.updated);
            }
        });
    }

    static getElmFromEvent(e)
    {
        return e.currentTarget.closest('.thread');
    }
}
