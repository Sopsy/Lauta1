import {YBoard} from '../../YBoard.js';
import {Ajax} from '../Library/Ajax.js';
import {Toast} from '../Library/Toast.js';
import {Post} from '../Post.js';

export class ThreadAutoUpdater
{
    constructor()
    {
        this.threadId = false;
        this.newReplies = 0;
        this.lastUpdateNewReplies = 0;
        this.nowLoading = false;
        this.isActive = false;
        this.nextRunTimeout = 0;
        this.startDelayTimeout = 0;
        this.originalDocTitle = document.title;
        this.minLoadDelay = 1000;
        this.maxLoadDelay = 10000;
        this.lastReply = Date.now();
        this.nextLoadDelay = this.minLoadDelay;
        this.blurStopTimeout = null;

        this.start(1000);

        window.addEventListener('blur', () => {
            this.minLoadDelay = 10000;
            this.maxLoadDelay = 60000;
            this.nextLoadDelay = 10000;

            // Stop loading after not being in focus for a while
            this.blurStopTimeout = setTimeout(() => {
                this.stop();
            }, 1800000)
        });
        window.addEventListener('focus', () => {
            clearTimeout(this.blurStopTimeout);
            this.lastReply = Date.now();
            this.minLoadDelay = 1000;
            this.maxLoadDelay = 10000;
            this.nextLoadDelay = 1000;
            this.restart(1);
        });
    }

    run(runOnce = false)
    {
        if (this.nowLoading) {
            return false;
        }

        this.nextLoadDelay = Math.round(this.nextLoadDelay * 1.5);
        if (this.nextLoadDelay > this.maxLoadDelay) {
            this.nextLoadDelay = this.maxLoadDelay;
        }

        // Limit
        if (Date.now() - 300000 > this.lastReply) {
            // Stop autoupdate after approx half an hour of no new replies
            this.stop();

            return;
        }

        let thread = document.getElementById('t' + this.threadId);
        if (!thread) {
            Toast.warning(messages.thread.autoUpdater.unknownError);

            return;
        }

        let fromId = thread.dataset.newestReply;

        let visibleReplies = [];
        for (let answer of thread.querySelectorAll('.answer')) {
            visibleReplies.push(answer.dataset.id);
        }

        this.nowLoading = true;
        let that = this;
        Ajax.post('/scripts/ajax/get_new_replies.php', {
            threadId: this.threadId,
            fromId: fromId,
            newest: true,
            visibleReplies: visibleReplies,
        }, {
            errorFunction: null,
        }).onLoad(function(xhr)
        {
            // Remove deleted replies
            let deletedReplies = xhr.getResponseHeader('X-Deleted-Replies');
            if (deletedReplies !== null) {
                deletedReplies.split(',').forEach(function(id) {
                    let post = document.getElementById('no' + id);
                    if (post !== null) {
                        post.remove();
                    }
                });
            }

            let response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch(e) {
                Toast.error(messages.invalidResponse);
                return;
            }

            if (response.error) {
                Toast.error(response.error);
                return;
            }

            let template = document.createElement('template');
            template.innerHTML = response.html;

            that.lastUpdateNewReplies = template.content.querySelectorAll('.post').length;
            that.newReplies += that.lastUpdateNewReplies;

            // Notify about new posts on title
            if (document.hasFocus()) {
                that.newReplies = 0;
            } else if (that.newReplies > 0 && document.getElementById('right').classList.contains('thread-page')) {
                document.title = '(' + that.newReplies + ') ' + that.originalDocTitle;
                if (!('listeningForFocus' in document.body.dataset)) {
                    document.body.dataset.listeningForFocus = 'true';
                    window.addEventListener('focus', ThreadAutoUpdater.focusListener, true);
                }
            }

            if (that.lastUpdateNewReplies !== 0) {
                // Reset timers if we got new replies
                that.lastReply = Date.now();
                that.nextLoadDelay = that.minLoadDelay;
            } else if (!runOnce) {
                // Skip inits if there's no new replies
                that.nextRunTimeout = setTimeout(function()
                {
                    that.run(false);
                }, that.nextLoadDelay);

                return;
            }

            // Update backlinks
            let refsAdded = [];
            for (let elm of template.content.querySelectorAll('.ref')) {
                let postId = elm.closest('.post').dataset.id;
                let referredId = elm.dataset.id;
                let referredPost = document.getElementById('no' + referredId);
                if (referredPost === null) {
                    continue;
                }

                if (refsAdded.includes(postId + '-' + referredId)) {
                    continue;
                }

                refsAdded.push(postId + '-' + referredId);

                // Create replies-container if it does not exist
                let repliesElm = referredPost.querySelector('.replies');
                if (repliesElm === null) {
                    repliesElm = document.createElement('div');
                    repliesElm.classList.add('replies');
                    repliesElm.textContent = messages.post.replies + ':';
                    referredPost.appendChild(repliesElm);
                }

                let replyElm = document.createElement('a');
                replyElm.classList.add('ref');
                replyElm.setAttribute('href', '/scripts/redirect.php?id=' + postId);
                replyElm.dataset.id = postId;
                replyElm.textContent = '>>' + postId;

                YBoard.addReflinkEventListener(replyElm);
                repliesElm.appendChild(document.createTextNode(' '));
                repliesElm.appendChild(replyElm);
                Post.updateQuoteSuffixes(replyElm);
            }

            YBoard.initElement(template.content);
            let addedPosts = template.content.querySelectorAll('.post');
            if (addedPosts) {
                let lastPost = addedPosts[addedPosts.length - 1];
                if (lastPost) {
                    thread.dataset.newestReply = lastPost.dataset.id;
                } else {
                    console.warn('Last post not found');
                }
            } else {
                console.warn('No posts found in loaded content');
            }
            thread.querySelector('.answers').append(template.content);

            // Run again
            if (!runOnce) {
                that.nextRunTimeout = setTimeout(function()
                {
                    that.run(false);
                }, that.nextLoadDelay);
            }

        }).onError(function(xhr)
        {
            if (xhr.status === 410) {
                // Thread was deleted
                let thread = document.getElementById('t' + that.threadId);
                if (thread !== null) {
                    Toast.warning(messages.thread.autoUpdater.threadDeleted);
                    thread.remove();
                    YBoard.returnToBoard();
                }
                that.stop();

                return;
            }

            that.restart(5000);
        }).onEnd(function()
        {
            that.nowLoading = false;
        });
    }

    static focusListener(e)
    {
        delete document.body.dataset.listeningForFocus;

        window.threadAutoUpdater.newReplies = 0;
        document.title = window.threadAutoUpdater.originalDocTitle;
        window.removeEventListener('focus', ThreadAutoUpdater.focusListener, true);
    }

    runOnce(thread)
    {
        this.threadId = thread;
        this.run(true);
    }

    start(delay = null)
    {
        if (!document.getElementById('right') || !document.getElementById('right').classList.contains('thread-page')) {
            return false;
        }

        let startDelay = 1000;
        if (delay) {
            startDelay = parseInt(delay);
        }

        this.isActive = true;
        if (this.startDelayTimeout) {
            clearTimeout(this.startDelayTimeout);
        }

        let that = this;
        this.threadId = document.querySelector('.threads .thread').dataset.threadId;
        this.startDelayTimeout = setTimeout(() => {
            that.run(false);
        }, startDelay);

        return true;
    }

    stop()
    {
        this.reset();

        if (!this.isActive) {
            return true;
        }

        if (this.startDelayTimeout) {
            clearTimeout(this.startDelayTimeout);
        }
        this.isActive = false;

        return true;
    }

    restart(delay = 1000)
    {
        this.stop();
        this.start(delay);
    }

    reset()
    {
        this.newReplies = 0;
        this.lastReply = Date.now();
        if (document.title !== this.originalDocTitle) {
            document.title = this.originalDocTitle;
        }

        if (this.nextRunTimeout) {
            clearTimeout(this.nextRunTimeout);
        }
    }
}
