import {Ajax} from './Library/Ajax.js';
import {Toast} from './Library/Toast.js';
import {YBoard} from "../YBoard.js";

export class PostForm
{
    static isActive()
    {
        if (typeof PostForm._isActive === 'undefined') {
            PostForm._isActive = false;
        }

        return PostForm._isActive;
    }

    static getEvents()
    {
        return {
            addBbCode: {
                fn: PostForm.insertBbCode
            },
            toggleColorButtons: {
                fn: PostForm.toggleColorButtons
            },
            togglePostOptions: {
                fn: PostForm.togglePostOptions
            },
            postFormShow: {
                fn: PostForm.show
            },
            postSubmit: {
                event: 'submit',
                fn: PostForm.submit
            },
            checkFile: {
                event: 'change',
                fn: PostForm.fileChange
            },
            postFormFocus: {
                fn: PostForm.focusWithScroll
            },
            toggleUsername: {
                event: 'change',
                fn: PostForm.toggleUsername
            }
        }
    }

    static toggleUsername()
    {
        Ajax.post('/scripts/ajax/toggle_name.php', {
            'showUsername': PostForm.getFormElm().querySelector('input[name="show_username"]').checked,
        });
    }

    static fileChange()
    {

        let fileElm = document.getElementById('file');
        if (fileElm.files[0] && fileElm.files[0].size > fileElm.dataset.maxSize) {
            Toast.error(messages.post.fileTooBig);
            fileElm.value = '';
        }
    }

    static focusWithScroll()
    {
        document.getElementById('msg').scrollIntoView();
        document.getElementById('msg').focus();
    }

    static focus()
    {
        PostForm.getMsgElm().focus();
    }

    static show(e)
    {
        document.getElementById('post').classList.toggle('visible');
        e.currentTarget.remove();

        PostForm.focus();
    }

    static insertBbCode(e)
    {
        let code = e.currentTarget.dataset.code;
        PostForm.getMsgElm().insertAtCaret('[' + code + ']', '[/' + code + ']');
    }

    static toggleColorButtons(e)
    {
        PostForm.getFormElm().querySelector('#color-buttons').classList.toggle('visible');
        PostForm.getMsgElm().focus();
    }

    static togglePostOptions(e)
    {
        PostForm.getFormElm().querySelector('#postoptions').classList.toggle('visible');
    }

    static replyingTo(thread, post = null)
    {
        let form = PostForm.getFormElm();
        form.classList.add('replying');

        // Store original thread destination
        let threadDest = form.querySelector('[name="thread"]');
        form.dataset.origThread = threadDest.value;
        threadDest.value = thread.dataset.threadId;

        if (post) {
            post.parentNode.insertBefore(form, post.nextElementSibling);
        } else {
            thread.insertBefore(form, thread.querySelector('.threadbuttons'));
        }

        // Add button for canceling the post
        if (!form.querySelector('#cancel-reply')) {
            let cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancel-reply';
            cancelBtn.classList.add('linkbutton');
            cancelBtn.textContent = messages.cancel;
            cancelBtn.setAttribute('type', 'button');
            cancelBtn.addEventListener('click', PostForm.cancelReply);
            let buttonRow = form.querySelector('#row-buttons');
            buttonRow.insertBefore(cancelBtn, buttonRow.querySelector('button:last-of-type'));
        }
    }

    static cancelReply(e = null)
    {
        if (e) {
            if (!confirm(messages.post.cancel)) {
                return false;
            }
        }
        let form = PostForm.getFormElm();

        let cancelButton = form.querySelector('#cancel-reply');
        if (cancelButton) {
            cancelButton.remove();
        }

        form.classList.remove('replying');
        form.querySelector('#postoptions').classList.remove('visible');
        form.querySelector('[name="thread"]').value = 'origThread' in form.dataset ? form.dataset.origThread : '0';
        form.reset();

        document.getElementById('postform').append(form);
    }

    static getFormElm()
    {
        return document.getElementById('post');
    }

    static getMsgElm()
    {
        return document.getElementById('msg');
    }

    static updateSubmitProgress(submitProgress, status = null)
    {
        if (status !== null) {
            submitProgress.textContent = status;
        } else {
            submitProgress.remove();
        }
    }

    static submit(event)
    {
        event.preventDefault();

        if (!YBoard.renderCaptcha(event)) {
            return;
        }

        let formElm = PostForm.getFormElm();
        let textareaElm = formElm.querySelector('#msg');
        let submitElm = formElm.querySelector('#submit-btn');
        let threadId = formElm.elements.thread.value;

        if ('submitInProgress' in formElm.dataset) {
            return true;
        }

        formElm.dataset.submitInProgress = 'true';
        let fd = new FormData(formElm);

        // Fix for failed posts without a file in some browsers (Edge? Safari?)
        if ('delete' in fd && document.getElementById('file') && document.getElementById('file').value === '') {
            fd.delete('file');
        }

        submitElm.setAttribute('disabled', 'disabled');
        textareaElm.setAttribute('disabled', 'disabled');

        let submitProgress = formElm.querySelector('.submit-progress');
        if (!submitProgress) {
            submitProgress = document.createElement('span');
            submitProgress.classList.add('submit-progress');
            let buttonRow = formElm.querySelector('#row-buttons');
            buttonRow.insertBefore(submitProgress, buttonRow.querySelector('button:last-of-type'));
        }

        PostForm.updateSubmitProgress(submitProgress, messages.post.sending);
        Ajax.post(formElm.getAttribute("action"), fd, {
            timeout: 0,
            'xhr': (xhr) => {
                if (!xhr.upload) {
                    return xhr;
                }

                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        let percentComplete = Math.round((e.loaded / e.total) * 100);
                        if (percentComplete < 0) {
                            percentComplete = 0;
                        } else {
                            if (percentComplete > 100) {
                                percentComplete = 100;
                            }
                        }
                        if (percentComplete !== 100) {
                            PostForm.updateSubmitProgress(submitProgress,
                                messages.post.uploadingFile + ' ' + percentComplete + '%');
                        } else {
                            PostForm.updateSubmitProgress(submitProgress, messages.post.sending);
                        }
                    }
                });

                return xhr;
            }
        }).onLoad((data) => {

            let response = data.responseText;
            if (response.length !== 0 && response.substr(0, 3) !== 'OK:') {
                // An error occurred
                Toast.error(response, messages.error);
                return;
            }

            document.activeElement.blur();
            if (formElm.classList.contains('replying')) {
                PostForm.cancelReply();
            }

            let showUsername = formElm.querySelector('input[name="show_username"]');
            let showUsernameChecked = false;
            if (showUsername && showUsername.checked) {
                showUsernameChecked = true
            }

            formElm.reset();

            if (showUsername) {
                showUsername.checked = showUsernameChecked;
            }

            if (response.substr(0, 3) === 'OK:') {
                window.location = response.substr(3);
                return;
            }

            // Update posts
            if (!'threadAutoUpdater' in window) {
                Toast.warning(messages.autoUpdater.notInitialized);
                return;
            }

            window.threadAutoUpdater.runOnce(threadId);
        }).onEnd(() => {
            PostForm.updateSubmitProgress(submitProgress);
            submitElm.removeAttribute('disabled');
            textareaElm.removeAttribute('disabled');
            delete formElm.dataset.submitInProgress;
        });
    }
}