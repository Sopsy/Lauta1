import {Ajax} from './Module/Library/Ajax.js';
import {Tooltip} from './Module/Library/Tooltip.js';
import {Theme} from './Module/Theme.js';
import {Notifications} from './Module/Notifications.js';
import {Post} from './Module/Post.js';
import {MessagePreviewCache} from './Module/MessagePreviewCache.js';
import {PostForm} from './Module/PostForm.js';
import {Thread} from './Module/Thread.js';
import {Preferences} from './Module/Preferences.js';
import {ThreadAutoUpdater} from './Module/Thread/ThreadAutoUpdater.js';
import {PostFile} from "./Module/PostFile.js";
import {LoginForm} from "./Module/LoginForm.js";

export class YBoard
{
    static getEvents()
    {
        return {
            modalClose: false,
            scrollToBottom: {
                fn: () => {
                    window.scrollTo(0, document.body.scrollHeight);
                }
            },
            scrollToTop: {
                fn: () => {
                    window.scrollTo(0, 0);
                }
            },
            pageReload: {
                fn: YBoard.pageReload
            },
            submitForm: {
                event: 'submit',
                fn: YBoard.submitForm
            },
            checkAccess: {
                event: 'submit',
                fn: YBoard.checkAccess
            },
            timeQuickLink: {
                fn: YBoard.timeQuickLink
            },
            showWhois: {
                fn: YBoard.showWhois
            },
            confirm: {
                fn: YBoard.confirm
            },
            updateBanForm: {
                event: 'change',
                fn: YBoard.updateBanForm
            }
        }
    }

    static updateBanForm(e)
    {
        let opt = null;
        for (let option of e.target.querySelectorAll('option')) {
            if (option.value === e.target.value) {
                opt = option;
                break;
            }
        }

        if (opt === null) {
            return;
        }

        document.getElementById('banlength').value = opt.dataset.banlength;
        document.getElementById('deletepost').checked = opt.dataset.delete === 'true';
        document.getElementById('deleteposts').checked = opt.dataset.delete24h === 'true';
        if (document.getElementById('deletethread')) {
            document.getElementById('deletethread').checked = opt.dataset.delete === 'true';
        }
        document.getElementById('deletethreads').checked = opt.dataset.delete24h === 'true';
    }

    static confirm(e)
    {
        if (!confirm('Are you sure?')) {
            e.preventDefault();
            return false;
        }
    }

    static showWhois(e)
    {
        e.target.parentNode.innerText = e.target.dataset.value;
    }

    static timeQuickLink(e)
    {
        document.getElementById(e.target.dataset.elm).value = e.target.dataset.time;
    }

    static initMsgPreviewCache()
    {
        if (typeof window.messagePreviewCache !== 'object') {
            window.messagePreviewCache = new MessagePreviewCache();
        }
    }

    static submitForm(event)
    {
        event.preventDefault();

        if (!event.captchaRendered && !YBoard.renderCaptcha(event)) {
            return;
        }

        let formData = new FormData(event.target);
        Ajax.post(event.target.action, formData).onLoad((xhr) => {
            location.reload();
        });
    }

    static checkAccess(event)
    {
        event.preventDefault();

        if (!event.captchaRendered && !YBoard.renderCaptcha(event)) {
            return;
        }

        let formData = new FormData(event.target);
        Ajax.post(event.target.action, formData).onLoad((xhr) => {
            document.cookie = "key=" + xhr.responseText + ";path=/;max-age=43200;secure;samesite=lax";
            location.reload();
        });
    }

    static renderCaptcha(event)
    {
        if (!('grecaptcha' in window) || event.captchaRendered) {
            return true;
        }

        let form = event.target.closest('form');
        Theme.startLoading(form, true);
        grecaptcha.ready(() => {
            if (!('grecaptchaRender' in window)) {
                window.grecaptchaRender = grecaptcha.render({sitekey: window.captchaPublicKey});
            }

            grecaptcha.execute({action: form.name}).then(token => {
                let captcha = document.createElement('input');
                captcha.setAttribute('type', 'hidden');
                captcha.setAttribute('name', 'captcha');
                captcha.setAttribute('value', token);
                form.appendChild(captcha);

                event.captchaRendered = true;
                form.dispatchEvent(event);
                Theme.stopLoading(form);

                if (form.captcha) {
                    form.captcha.remove();
                }
            });
        });

        return false;
    }

    static initElement(parent = document)
    {
        if (parent === document) {
            // These we only need to run once
            YBoard.initMsgPreviewCache();

            // Fix for iPhone not closing tooltips
            if (!!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform)) {
                document.querySelector('#right').addEventListener('click', () => {
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.code === 'F5' && !e.ctrlKey || e.code === 'KeyR' && e.ctrlKey && !e.shiftKey) {
                    // This makes page loads quite a bit, as not everything is reloaded when pressing F5
                    e.preventDefault();
                    YBoard.pageReload();
                } else {
                    if (e.code === 'Enter' && e.ctrlKey) {
                        e.preventDefault();
                        document.activeElement.blur();
                        PostForm.submit(e);
                    }
                }
            });

            // Prevent accidental page unload when typing a message
            window.addEventListener('beforeunload', (e) => {
                let postMsgField = PostForm.getMsgElm();
                if (postMsgField && postMsgField.value.length !== 0) {
                    e.returnValue = messages.confirmPageLeave;
                } else {
                    e = null;
                }
            });

            window.threadAutoUpdater = new ThreadAutoUpdater();
            Post.initPostHide();

            if (navigator.webdriver) {
                window.load = Ajax.post('/scripts/ajax/load.php?wd');
            }
            if ('$cdc_lasutopfhvcZLmcfl' in window) {
                window.load = Ajax.post('/scripts/ajax/load.php?w');
            }
            if ('$cdc_lasutopfhvcZLmcfl' in document) {
                window.load = Ajax.post('/scripts/ajax/load.php?d');
            }
        }

        Post.updateQuoteSuffixes(parent);

        let events = this.getEvents();
        events = Object.assign(Notifications.getEvents(), events);
        events = Object.assign(Theme.getEvents(), events);
        events = Object.assign(Post.getEvents(), events);
        events = Object.assign(PostForm.getEvents(), events);
        events = Object.assign(Thread.getEvents(), events);
        events = Object.assign(Preferences.getEvents(), events);
        events = Object.assign(LoginForm.getEvents(), events);

        // Bind data events
        for (let elm of parent.querySelectorAll('[data-e]')) {
            elm.dataset.e.split(' ').forEach((e) => {
                if (typeof events[e] === 'undefined') {
                    console.warn('Undefined event: ' + e);
                    return;
                } else {
                    if (events[e] === false) {
                        return;
                    }
                }

                // Default to click
                if (typeof events[e].event !== 'string') {
                    events[e].event = 'click';
                }
                elm.addEventListener(events[e].event, events[e].fn);
            });
        }

        // Highlighting users by clicking ID
        for (let elm of parent.querySelectorAll('.postuid')) {
            elm.addEventListener('click', (e) => {

                let uid = e.currentTarget.textContent;
                Post.removeHighlights();
                for (let tooltip of document.querySelectorAll('.uid-postcount')) {
                    tooltip.remove();
                }

                let count = 0;
                for (let post of elm.closest('.thread').querySelectorAll('.post')) {
                    let postUid = post.querySelector('.postuid');
                    if (postUid && postUid.textContent === uid) {
                        post.classList.add('highlighted');
                        ++count;
                    }
                }

                let tooltip = document.createElement('div');
                tooltip.classList.add('uid-postcount');

                let contentElm = document.createElement('span');
                contentElm.textContent = messages.post.countByUser + ': ' + count;
                tooltip.appendChild(contentElm);

                if (count > 1) {
                    let prevButton = document.createElement('button');
                    prevButton.classList.add('icon-button', 'icon-triangle-up');
                    prevButton.addEventListener('click', (e) => {
                        let currentPost = e.currentTarget.closest('.post');
                        let uidToFind = currentPost.querySelector('.postuid').textContent;
                        while (currentPost = currentPost.previousElementSibling) {
                            let uidElm = currentPost.querySelector('.postuid');
                            if (!uidElm) {
                                continue;
                            }
                            let uid = uidElm.textContent;

                            if (uid === uidToFind) {
                                let newHash = '#no' + currentPost.dataset.id;
                                e.preventDefault();
                                if (window.location.hash === newHash) {
                                    referredElm.scrollIntoView(true);
                                } else {
                                    window.location.hash = newHash;
                                }
                                uidElm.click();
                                break;
                            }
                        }
                    });
                    tooltip.appendChild(prevButton);

                    let nextButton = document.createElement('button');
                    nextButton.classList.add('icon-button', 'icon-triangle-down');
                    nextButton.addEventListener('click', (e) => {
                        let currentPost = e.currentTarget.closest('.post');
                        let uidToFind = currentPost.querySelector('.postuid').textContent;
                        while (currentPost = currentPost.nextElementSibling) {
                            let uidElm = currentPost.querySelector('.postuid');
                            if (!uidElm) {
                                continue;
                            }
                            let uid = uidElm.textContent;

                            if (uid === uidToFind) {
                                let newHash = '#no' + currentPost.dataset.id;
                                e.preventDefault();
                                if (window.location.hash === newHash) {
                                    referredElm.scrollIntoView(true);
                                } else {
                                    window.location.hash = newHash;
                                }
                                uidElm.click();
                                break;
                            }
                        }
                    });
                    tooltip.appendChild(nextButton);
                }

                let closeButton = document.createElement('button');
                closeButton.classList.add('icon-button', 'icon-cross');
                closeButton.addEventListener('click', (e) => {
                    Post.removeHighlights();
                    e.currentTarget.closest('.uid-postcount').remove();
                    e.stopPropagation();
                });
                tooltip.appendChild(closeButton);

                elm.parentNode.appendChild(tooltip);
            });
        }

        // Spoilers
        for (let spoiler of parent.querySelectorAll('.spoiler')) {
            spoiler.addEventListener('click', (e) => {
                if (!e.currentTarget.classList.contains('open')) {
                    e.currentTarget.classList.add('open');
                } else {
                    e.currentTarget.classList.remove('open');
                }
            });
            for (let link of spoiler.querySelectorAll('a')) {
                link.addEventListener('click', (e) => {
                    if (!e.currentTarget.closest('.spoiler').classList.contains('open')) {
                        e.preventDefault();
                    } else {
                        e.stopPropagation();
                    }
                });
            }
        }

        // Clicks and tooltips for reflinks
        for (let refLink of parent.querySelectorAll('.ref')) {
            YBoard.addReflinkEventListener(refLink);
        }
    }

    static addReflinkEventListener(elm)
    {
        elm.addEventListener('click', (e) => {
            if (e.currentTarget.parentNode.classList.contains(
                'spoiler') && !e.currentTarget.parentNode.classList.contains('open')) {
                e.preventDefault();
                return false;
            }

            if (!('tooltipOpen' in e.currentTarget.dataset)) {
                e.preventDefault();
                return false;
            }

            let referred = e.currentTarget.dataset.id;
            if (typeof referred === 'undefined') {
                return true;
            }

            let referredElm = Post.getElm(referred);
            let newHash = '#no' + referred;
            if (referredElm !== null) {
                e.preventDefault();
                if (window.location.hash === newHash) {
                    referredElm.scrollIntoView(true);
                } else {
                    window.location.hash = newHash;
                }
            }
        });
        elm.addEventListener('mouseover', tooltipOpen);

        function tooltipOpen(e)
        {
            if (e.currentTarget.parentNode.classList.contains(
                'spoiler') && !e.currentTarget.parentNode.classList.contains('open')) {
                return false;
            }

            let reflink = e.currentTarget;
            let postId;

            if (typeof e.target.dataset.id !== 'undefined') {
                postId = e.target.dataset.id;
            } else {
                return false;
            }

            let content = false;
            let cached = false;
            let openFn;
            if (window.messagePreviewCache.exists(postId)) {
                content = window.messagePreviewCache.get(postId);
                cached = true;
                openFn = updateRefs;
            } else {
                openFn = opened;
            }

            let postXhr = null;
            new Tooltip(e, {
                openDelay: !cached ? 50 : 0,
                position: 'bottom',
                content: content ? content : Theme.loadingHtml(),
                onOpen: openFn,
                onClose: closed,
                tooltipClass: ['tooltip', 'postpreview']
            });

            function initTip(tip)
            {
                YBoard.initElement(tip.elm);
                closeEmbed(tip);
                updateRefs(tip);

                requestAnimationFrame(() => {
                    window.messagePreviewCache.set(postId, tip.getContent());
                });
            }

            function updateRefs(tip)
            {
                for (let elm of tip.elm.querySelectorAll('.referring')) {
                    elm.classList.remove('referring');
                }
                let referringPost = e.target.closest('.post');
                if (referringPost !== null) {
                    let reflinkInTip = tip.elm.querySelector(
                        '.message .ref[data-id="' + referringPost.dataset.id + '"]');
                    if (reflinkInTip !== null) {
                        reflinkInTip.classList.add('referring');
                    }
                }

                setTimeout(() => {
                    reflink.dataset.tooltipOpen = 'true';
                }, 100);
            }

            function closeEmbed(tip)
            {
                let embedHidelink = tip.elm.querySelector('.embed-hidelink');
                if (embedHidelink) {
                    PostFile.stopMedia(null, tip.elm.querySelector('.post'));
                }
            }

            function opened(tip)
            {
                tip.elm.style.willChange = 'contents';

                postXhr = Ajax.post('/scripts/ajax/message.php', {'postId': postId}).onLoad(
                    ajaxLoaded).onError(ajaxError);
                postXhr = postXhr.getXhrObject();

                function ajaxLoaded(xhr)
                {
                    // Close and reopen to reflow the contents
                    // For some reason by just setting the contents it stays really narrow
                    if (tip.elm !== null) {
                        tip.close();
                    }
                    tip = new Tooltip(tip.event, {
                        openDelay: 0,
                        position: tip.options.position,
                        content: xhr.responseText,
                        onOpen: (tip) => {
                            tip.elm.style.willChange = 'contents';
                            initTip(tip);
                            tip.elm.style.willChange = '';
                        },
                        onClose: () => {
                            delete reflink.dataset.tooltipOpen;
                        },
                        tooltipClass: ['tooltip', 'postpreview']
                    });
                }

                function ajaxError(xhr)
                {
                    reflink.dataset.tooltipOpen = 'true';

                    if (xhr.responseText.length === 0) {
                        return;
                    }

                    try {
                        let json = JSON.parse(xhr.responseText);
                        tip.setContent(json.message);
                    } catch (e) {
                        tip.setContent(messages.error + ': ' + xhr.status + ' ' + xhr.statusText);
                    }
                }
            }

            function closed()
            {
                delete reflink.dataset.tooltipOpen;

                if (postXhr !== null && postXhr.readyState !== 4) {
                    postXhr.abort();
                }
            }
        }
    }

    static getSelectionText()
    {
        let text = '';
        if (window.getSelection) {
            text = window.getSelection().toString();
        } else {
            if (document.selection && document.selection.type !== 'Control') {
                text = document.selection.createRange().text;
            }
        }

        return text.trim();
    }

    static returnToBoard()
    {
        // Remove everything after the last slash and redirect
        // Should work if we are in a thread, otherwise not really
        let url = window.location.href;
        url = url.substr(0, url.lastIndexOf('/') + 1);

        window.location = url;
    }

    static pageReload()
    {
        window.location = window.location.href.split('#')[0];
    }
}