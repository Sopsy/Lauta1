import {Ajax} from './Library/Ajax.js';
import {Toast} from './Library/Toast.js';
import {YBoard} from '../YBoard.js';
import {PostFile} from './PostFile.js';
import {Theme} from './Theme.js';
import {Modal} from './Library/Modal.js';
import {PostForm} from './PostForm.js';

export class Post
{
    static getEvents()
    {
        return Object.assign({
            postReply: {
                fn: Post.reply,
            },
            postUpvote: {
                fn: Post.upvote,
            },
            postDelete: {
                fn: Post.delete,
            },
            postShare: {
                fn: Post.share,
            },
            postEdit: {
                fn: Post.edit,
            },
            postShowEdits: {
                fn: Post.showEdits,
            },
            postHide: {
                fn: Post.hide,
            },
            postUnhide: {
                fn: Post.unhide,
            },
            postToggleOptions: {
                fn: Post.toggleOptions,
            },
            postReportForm: {
                fn: Post.reportForm,
            },
            postDonateGold: {
                fn: Post.donateGold,
            },
            postToggleModBar: {
                fn: Post.toggleModBar,
            },
            postBanUser: {
                fn: Post.banUser,
            },
            postCheckReport: {
                fn: Post.checkReport,
            },
            playYoutube: {
                fn: Post.playYoutube,
            }
        }, PostFile.getEvents());
    }

    static playYoutube(e)
    {
        Post.closeYoutube();

        let id = e.currentTarget.dataset.id;

        let parent = e.currentTarget.closest('.inline-embed');

        let container = document.createElement('div');
        container.classList.add('youtube-embed');

        let iframe = document.createElement('iframe');
        let url = 'https://www.youtube-nocookie.com/embed/' + id + '?modestbranding=1&playsinline=1';
        let time = parseInt(e.target.dataset.time);

        if (time !== 0 && !isNaN(time)) {
            url += '&start=' + time;
        }

        iframe.src = url;
        iframe.sandbox = 'allow-scripts allow-same-origin';
        iframe.allowFullscreen = true;

        let closeButton = document.createElement('button');
        closeButton.classList.add('linkbutton');
        closeButton.textContent = messages.hideEmbed;
        closeButton.addEventListener('click', Post.closeYoutube);

        container.appendChild(iframe);
        parent.classList.add('playing');
        parent.appendChild(container);
        parent.appendChild(closeButton);
    }

    static closeYoutube()
    {
        for (let elm of document.querySelectorAll('.inline-embed.playing')) {
            for (let embed of elm.querySelectorAll('.youtube-embed')) {
                embed.remove();
            }
            for (let btn of elm.querySelectorAll('.linkbutton')) {
                btn.remove();
            }
            elm.classList.remove('playing');
        }
    }

    static reply(e)
    {
        e.preventDefault();
        let msgElm = PostForm.getMsgElm();
        let post = Post.getElmFromEvent(e);
        let thread = post.closest('.thread');

        PostForm.replyingTo(thread, post);

        let quoteId = e.currentTarget.dataset.id;
        let selectionText = YBoard.getSelectionText();
        let insertText = '';
        if (msgElm.value.length !== 0 && msgElm.value.slice(-1) !== "\n") {
            insertText = "\r\n";
        }
        insertText = insertText + '>>' + quoteId + "\r\n";
        if (selectionText) {
            if (!selectionText.match(/\n/)) {
                if (!selectionText.match(/^>/)) {
                    insertText = insertText + '>';
                }
                insertText = insertText + selectionText + "\r\n";
            } else {
                insertText = insertText + '[quote]' + selectionText + "[/quote]\r\n";
            }
        }
        msgElm.insertAtCaret(insertText, '', e);
        msgElm.focus();
    }

    static updateQuoteSuffixes(parent = document)
    {
        for (let elm of parent.querySelectorAll('.ref')) {
            if (elm.textContent.substr(-1) === ')') {
                continue;
            }

            let quotedId = elm.dataset.id;
            let quotedPost = document.querySelector('#no' + quotedId);
            if (!quotedPost) {
                continue;
            }

            if (quotedPost.classList.contains('own_post')) {
                elm.textContent = elm.textContent + ' (' + messages.post.you + ')';
            }
        }
    }

    static getElm(id)
    {
        return document.getElementById('no' + id);
    }

    static removeHighlights()
    {
        for (let elm of document.querySelectorAll('.post.highlighted')) {
            elm.classList.remove('highlighted');
        }
    }

    static highlight(id)
    {
        Post.getElm(id).classList.add('highlighted');
    }

    static edit(e)
    {
        let elm = Post.getElmFromEvent(e);
        let id = elm.dataset.id;

        let subjectInput = '';
        if (elm.classList.contains('op_post')) {
            let subject = elm.closest('.thread').querySelector('.subject').textContent;
            subjectInput = '<input type="text" name="subject" maxlength="60" placeholder="' + messages.thread.subject + '" value="' + subject + '" />';
        }

        new Modal({
            title: messages.post.edit,
            content: '<form action="/scripts/ajax/savemessage.php" class="wide" method="post">' +
                '<input type="hidden" name="postId" value="' + id + '">' +
                subjectInput + '<textarea placeholder="' + messages.post.message + '" disabled name="msg">' + messages.loading + '</textarea>' +
                '<div class="buttons"><button class="linkbutton" type="button" data-e="modalClose">' + messages.cancel + '</button>' +
                '<button class="right linkbutton" type="submit">' + messages.save + '</button></div></form>',
            onOpen: modalOpen,
            onClose: (modal) => {
                if (!confirm(messages.post.editCancel)) {
                    modal.preventClose();
                }
            }
        });

        function modalOpen(modal)
        {
            Ajax.post('/scripts/ajax/message.php', {postId: id, msgonly: true, nohtml: true}).onLoad((data) => {
                let textarea = modal.content.querySelector('textarea');
                textarea.textContent = data.responseText;
                textarea.removeAttribute('disabled');
                textarea.focus();
            });

            modal.content.querySelector('form').addEventListener('submit', (e) => {
                e.preventDefault();
                let form = e.currentTarget;
                let formData = new FormData(form);

                Ajax.post(e.currentTarget.getAttribute('action'), formData).onLoad((data) => {
                    Ajax.post('/scripts/ajax/message.php', {msgonly: true, postId: id}).onLoad((data) => {
                        Post.getElm(id).querySelector('.postcontent').innerHTML = data.responseText;
                        YBoard.initElement(Post.getElm(id));

                        if (elm.classList.contains('op_post')) {
                            Post.getElm(id).closest('.thread').querySelector('.subject').textContent = form.elements.subject.value;
                        }
                        modal.close(true);
                    });

                    if (data.responseText !== '') {
                        Toast.error(data);
                    }
                });
            });
        }
    }

    static toggleOptions(e)
    {
        let post = Post.getElmFromEvent(e);
        let options = post.querySelector('.messageoptions');

        if (!options.classList.contains('visible')) {
            Post.hideAllOptions();
            document.querySelector('.wrapper').addEventListener('click', Post.hideOptionsEvent);
            options.classList.add('visible');
        } else {
            options.classList.remove('visible');
        }
    }

    static hideAllOptions()
    {
        for (let menu of document.querySelectorAll('.messageoptions.visible')) {
            menu.classList.remove('visible');
        }

        document.querySelector('.wrapper').removeEventListener('click', Post.hideOptionsEvent);
    }

    static hideOptionsEvent(e = null)
    {
        if (e && (e.target.closest('.messageoptions') && e.target.tagName !== 'BUTTON'
            || 'e' in e.target.dataset && e.target.dataset.e === 'postToggleOptions')) {
            // Don't close if clicking the menu background
            return false;
        }

        Post.hideAllOptions();
    }

    static upvote(e)
    {
        let post = Post.getElmFromEvent(e);
        let countElm = post.querySelector('.upvote_count');
        if ('upvoted' in countElm.dataset) {
            return true;
        }

        countElm.dataset.upvoted = 'true';
        incrementCounter();

        Ajax.post('/scripts/ajax/addthis.php', {
            'post_id': post.dataset.id
        }).onLoad((data) => {
            if (data.responseText.length === 0) {
                return true;
            } else {
                Toast.error(data.responseText);
                delete countElm.dataset.upvoted;
                decrementCounter();
            }
        }).onError(() => {
            delete countElm.dataset.upvoted;
            decrementCounter();
        });

        function incrementCounter()
        {
            countElm.dataset.count = (parseInt(countElm.dataset.count) + 1).toString();
            countElm.textContent = '+' + countElm.dataset.count;
        }

        function decrementCounter()
        {
            countElm.dataset.count = (parseInt(countElm.dataset.count) - 1).toString();
            countElm.textContent = '+' + countElm.dataset.count;
        }
    }

    static delete(e)
    {
        let elm = Post.getElmFromEvent(e);
        let id = elm.dataset.id;

        let fileSelector = '<p>' + messages.post.deleteConfirm + '</p>' +
            '<input type="hidden" name="onlyfile" value="false">';
        if (Post.getElm(id) && Post.getElm(id).querySelector('.post-file')) {
            fileSelector = '<label><input type="radio" value="false" name="onlyfile" checked> ' + messages.post.deletePost + '</label>' +
                '<label><input type="radio" value="true" name="onlyfile"> ' + messages.post.deleteFile + '</label>';
        }

        new Modal({
            title: messages.post.delete,
            content: '<form action="/scripts/ajax/delete.php" method="post">' +
                '<input type="hidden" name="id" value="' + id + '">' + fileSelector +
                '<div class="buttons">' +
                '<button class="linkbutton" type="button" data-e="modalClose">' + messages.cancel + '</button>' +
                '<button class="linkbutton" type="submit">' + messages.post.delete + '</button>' +
                '</div></form>',
            onOpen: (modal) => {
                modal.content.querySelector('form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    modal.close();

                    let onlyFile = e.currentTarget.elements.onlyfile.value === 'true';
                    let formData = new FormData(e.currentTarget);

                    Ajax.post(e.currentTarget.getAttribute('action'), formData).onLoad(() => {
                        if (!onlyFile) {
                            for (let postElm of document.querySelectorAll('.post[data-id="' + id + '"]')) {
                                postElm.remove();
                            }
                            Toast.success(messages.post.deleted);
                        } else {
                            elm.querySelector('.post-file').remove();
                            Toast.success(messages.post.fileDeleted);
                        }
                    });
                });
            }
        });
    }

    static showEdits(e)
    {
        let postXhr = null;
        let id = Post.getElmFromEvent(e).dataset.id;

        new Modal({
            title: messages.post.edits,
            content: Theme.loadingHtml(),
            onOpen: function (modal) {
                modal.elm.style.willChange = 'contents';
                postXhr = Ajax.post('/scripts/modals/getedits.php', {msgid: id}, {
                    'errorFunction': null,
                }).onLoad(function (xhr) {
                    if (modal.elm === null) {
                        return;
                    }
                    modal.setContent(xhr.responseText);
                    modal.elm.style.willChange = '';
                    YBoard.initElement(modal.elm);
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

    static share(e)
    {
        function decodeEntities(encodedString)
        {
            let textArea = document.createElement('textarea');
            textArea.textContent = encodedString;
            return textArea.value;
        }

        let id = Post.getElmFromEvent(e).dataset.id;
        let subjectElm = e.target.closest(".thread").querySelector(".postsubject");
        let url = 'https://yli.sh/' + id;
        let subject = decodeEntities(subjectElm.querySelector(".subject").textContent);

        if (navigator.share) {
            navigator.share({
                title: subject,
                text: subject,
                url: url
            });
        } else {
            new Modal({
                title: messages.post.share,
                content: '<p>' + messages.post.linkHere + '</p><input type="url" value="' + url + '">' +
                    '<div class="buttons"><button class="linkbutton">' + messages.copy + '</button></div>',
                onOpen: (modal) => {
                    let input = modal.content.querySelector('input');
                    input.focus();
                    input.select();

                    modal.content.querySelector('button').addEventListener('click', (e) => {
                        input.focus();
                        input.select();
                        let copied = document.execCommand('copy');
                        if (copied) {
                            modal.close();
                            Toast.success(messages.copySuccess);
                        } else {
                            Toast.error(messages.copyFailed);
                        }
                    });
                }
            });
        }
    }

    static loadHiddenPosts()
    {
        let hiddenPosts = [];
        if (localStorage.getItem('hiddenPosts')) {
            try {
                hiddenPosts = JSON.parse(localStorage.getItem('hiddenPosts'));
            } catch (e) {
                console.warn('Corrupted hiddenPosts object in localStorage, removing');
                localStorage.removeItem('hiddenPosts');
            }
        }

        return hiddenPosts;
    }

    static initPostHide()
    {
        Post.loadHiddenPosts().forEach((id) => {
            if (!Post.getElm(id)) {
                return true;
            }
            Post.getElm(id).classList.add('hidden');
        });
    }

    static hide(e, store = true)
    {
        let elm = Post.getElmFromEvent(e);
        let id = parseInt(elm.dataset.id);

        PostFile.stopMedia(null, elm);
        elm.classList.add('hidden');

        let hiddenPosts = Post.loadHiddenPosts();

        if (store && hiddenPosts.indexOf(id) === -1) {
            hiddenPosts.push(id);
            localStorage.setItem('hiddenPosts', JSON.stringify(hiddenPosts));
        }
    }

    static unhide(e)
    {
        let elm = Post.getElmFromEvent(e);
        let id = parseInt(elm.dataset.id);
        elm.classList.remove('hidden');

        let hiddenPosts = Post.loadHiddenPosts();

        let index = hiddenPosts.indexOf(id);
        if (index >= 0) {
            hiddenPosts.splice(index, 1);
            localStorage.setItem('hiddenPosts', JSON.stringify(hiddenPosts));
        }
    }

    static reportForm(e)
    {
        let postXhr = null;
        let id = Post.getElmFromEvent(e).dataset.id;

        new Modal({
            title: messages.post.report.title,
            content: Theme.loadingHtml(),
            onOpen: function (modal) {
                modal.elm.style.willChange = 'contents';
                postXhr = Ajax.post('/scripts/modals/getreportform.php', {id: id}).onLoad(function (xhr) {
                    if (modal.elm === null) {
                        return;
                    }
                    modal.setContent(xhr.responseText);
                    modal.elm.style.willChange = '';
                    let form = modal.elm.querySelector('form');

                    form.addEventListener('submit', (e) => {
                        e.preventDefault();

                        if (!YBoard.renderCaptcha(e)) {
                            return;
                        }

                        let form = e.currentTarget;
                        let fd = new FormData(form);
                        Theme.startLoading(form);
                        Ajax.post(form.getAttribute('action'), fd).onLoad((data) => {
                            if (data.responseText.length !== 0) {
                                Toast.error(data.responseText, messages.error);
                                return;
                            }
                            Toast.success(messages.post.report.success);
                        }).onLoad(() => {
                            modal.close();
                        }).onEnd(() => {
                            Theme.stopLoading(form);
                        });
                    });
                }).onError(() => {
                    modal.close();
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


    static donateGold(e)
    {
        let postXhr = null;
        let id = Post.getElmFromEvent(e).dataset.id;

        new Modal({
            title: messages.post.donateGold.title,
            content: Theme.loadingHtml(),
            onOpen: function (modal) {
                modal.elm.style.willChange = 'contents';
                postXhr = Ajax.post('/scripts/modals/getgolddonateform.php', {id: id}).onLoad(function (xhr) {
                    if (modal.elm === null) {
                        return;
                    }
                    modal.setContent(xhr.responseText);
                    YBoard.initElement(modal.content);
                    modal.elm.style.willChange = '';

                    for (let button of modal.elm.querySelectorAll('.gold-key button')) {
                        button.addEventListener('click', (e) => {
                            let postId = e.currentTarget.dataset.id;
                            let key = e.currentTarget.dataset.key;
                            Ajax.post('/scripts/ajax/donate_gold_key.php', {
                                key: key,
                                post_id: postId
                            }).onLoad((data) => {
                                if (data.responseText.length !== 0) {
                                    Toast.error(data.responseText);
                                    return;
                                }

                                Toast.success(messages.post.donateGold.success);
                                modal.close();
                            });
                        });
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

    static toggleModBar(e)
    {
        let post = Post.getElmFromEvent(e);
        let modBar = post.querySelector('.modbar');
        if (!modBar) {
            return;
        }

        modBar.classList.toggle('visible');
    }

    static banUser(e)
    {
        let post = Post.getElmFromEvent(e);
        window.location = '/mod/index.php?action=addban&msgid=' + post.dataset.id;
    }

    static checkReport(e)
    {
        let post = Post.getElmFromEvent(e);
        let id = post.dataset.id;

        Ajax.post('/scripts/ajax/checkreport.php', {
            id: id
        });

        for (let postElm of document.querySelectorAll('.post[data-id="' + id + '"]')) {
            postElm.classList.add('checked');
        }
    }

    static getElmFromEvent(e)
    {
        return e.currentTarget.closest('.post');
    }
}
