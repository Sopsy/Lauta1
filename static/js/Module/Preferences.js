import {Toast} from './Library/Toast.js';
import {Ajax} from './Library/Ajax.js';
import {YBoard} from '../YBoard.js';
import {Theme} from './Theme.js';

export class Preferences
{

    static getEvents()
    {
        return {
            goldKeyCopy: {
                fn: Preferences.goldKeyCopy,
            },
            goldKeyActivate: {
                fn: Preferences.goldKeyActivate,
            },
            checkUsername: {
                event: 'keyup',
                fn: Preferences.checkUsername,
            },
            checkPassword: {
                event: 'keyup',
                fn: Preferences.checkPassword,
            },
            userChangePassword: {
                event: 'submit',
                fn: Preferences.userChangePassword,
            },
            changeUsername: {
                fn: Preferences.changeUsername,
            },
            changeUserEmail: {
                fn: Preferences.changeUserEmail,
            },
            changeUserInfo: {
                event: 'submit',
                fn: Preferences.changeUserInfo,
            },
            userDeleteAccount: {
                event: 'submit',
                fn: Preferences.userDeleteAccount,
            },
            userDeleteData: {
                event: 'submit',
                fn: Preferences.userDeleteData,
            },
            deleteSession: {
                fn: Preferences.deleteSession,
            },
            deleteSessions: {
                fn: Preferences.deleteSessions,
            },
            prefsToggleBoards: {
                fn: Preferences.toggleBoards,
            },
            displayCustomCss: {
                fn: Preferences.displayCustomCss,
            }
        };
    }

    static displayCustomCss(event)
    {
        event.target.remove();
        document.getElementById('custom_css').removeAttribute('hidden');
    }

    static toggleBoards(event)
    {
        let state = document.querySelector('.board_hide_table input[type=checkbox]').checked;
        for (let elm of document.querySelectorAll('.board_hide_table input[type=checkbox]')) {
            elm.checked =! state;
        }
    }

    static deleteSession(event)
    {
        let session_id = event.currentTarget.getAttribute("session_id");
        Ajax.post(
            '/scripts/managesessions.php',
            {
                destroy: session_id,
                csrf_token: window.user.csrfToken
            }
        ).onLoad(
            function(xhr){
                location.reload();
            }
        );
    }

    static deleteSessions()
    {
        Ajax.post(
            '/scripts/managesessions.php',
            {
                destroy: 'all',
                csrf_token: window.user.csrfToken
            }

        ).onLoad(
            function(xhr){
                location.reload();
            }
        );
    }

    static goldKeyActivate(e)
    {
        let button = e.currentTarget;
        let container = button.closest('.gold-key');
        let keyInput = container.querySelector('input');

        if (keyInput.value.length === 0) {
            Toast.error(messages.goldKey.invalid);
            return false;
        }

        if (!confirm(messages.goldKey.activateConfirm)) {
            return false;
        }

        button.setAttribute('disabled', true);

        let loading = setTimeout(() => {
            container.append(Theme.loadingHtml(true));
        }, 250);

        Ajax.post('/scripts/ajax/activategold.php', {'code': keyInput.value}).onLoad((data) => {
            if (data.responseText.length !== 0) {
                Toast.error(data.responseText);
                return false;
            }

            keyInput.value = '';
            Toast.success(messages.goldKey.activated);
            setTimeout(() => {
                YBoard.pageReload();
            }, 2000);
        }).onEnd(() => {
            clearTimeout(loading);
            if (container.querySelector('.loading')) {
                container.querySelector('.loading').remove();
            }
            button.removeAttribute('disabled');
        });
    }

    static goldKeyCopy(e)
    {
        let container = e.currentTarget.closest('.gold-key');
        let keyInput = container.querySelector('input');

        keyInput.focus();
        keyInput.select();
        document.execCommand('copy');
        keyInput.blur();

        Toast.success(messages.copySuccess);
    }

    static checkUsername(e)
    {
        let input = e.currentTarget;
        if ('checking' in input.dataset) {
            return;
        }

        input.dataset.checking = 'true';
        let username = input.value;

        let statusElm;
        if (input.nextElementSibling) {
            statusElm = input.nextElementSibling;
        } else {
            statusElm = document.createElement('span');
            input.parentElement.append(statusElm);
        }

        Ajax.post('/scripts/ajax/checkname.php', {name: username}).onLoad((data) => {
            delete input.dataset.checking;

            if (data.responseText.length === 0) {
                statusElm.textContent = '';
                return;
            }

            statusElm.classList.remove('green');
            statusElm.classList.add('red');
            statusElm.textContent = data.responseText;
        });
    }

    static checkPassword(e)
    {
        let form = document.changepassword
        if (form.newpassword === form.confirmpassword) {
            form.confirmpassword.setCustomValidity(messages.password.noMatch);
        } else {
            form.confirmpassword.setCustomValidity('');
        }
    }

    static userChangePassword(e)
    {
        e.preventDefault();

        let currentInput = document.getElementById('currentpassword');
        let currentPassword = currentInput.value;
        let input = document.getElementById('newpassword');
        let newpassword = input.value;
        let confirmInput = document.getElementById('confirmpassword');
        let confirmPassword = confirmInput.value;

        if (newpassword.length < 6) {
            Toast.error(messages.password.tooWeak);
            return false;
        }
        if (newpassword !== confirmPassword) {
            Toast.error(messages.password.noMatch);
            return false;
        }
        if (currentPassword.length === 0) {
            Toast.error(messages.password.currentEmpty);
            return false;
        }

        Theme.startLoading(e.target);

        Ajax.post('/scripts/ajax/changepassword.php', {
            'password': newpassword,
            'passwordconfirm': confirmPassword,
            'current': currentPassword
        }).onLoad((data) => {
            if (data.responseText.length !== 0) {
                Toast.error(data.responseText);
                return false;
            }

            Toast.success(messages.password.success);
            setTimeout(() => {
                YBoard.pageReload();
            }, 2000);
        }).onEnd(() => {
            Theme.stopLoading(e.target);
        });
    }

    static changeUsername(e)
    {
        e.currentTarget.remove();
        document.userinfo.username.removeAttribute('disabled');
    }

    static changeUserEmail(e)
    {
        let form = e.target.closest('form');
        let button = document.createElement('button');
        button.textContent = messages.remove;
        button.type = 'button';
        button.classList.add('linkbutton');

        button.addEventListener('click', (e) => {
            Theme.startLoading(form);
            Ajax.post('/scripts/ajax/removeemail.php', {
                'password': document.getElementById('password').value
            }).onLoad((data) => {
                if (data.responseText.length !== 0) {
                    Toast.error(data.responseText);
                    return false;
                }

                Toast.success(messages.emailRemoved);
                setTimeout(() => {
                    YBoard.pageReload();
                }, 2000);
            }).onEnd(() => {
                Theme.stopLoading(form);
            });
        });

        e.currentTarget.parentNode.appendChild(button);

        e.currentTarget.remove();
        document.userinfo.email.removeAttribute('disabled');
    }

    static changeUserInfo(e)
    {
        e.preventDefault();
        Theme.startLoading(e.target);

        Ajax.post('/scripts/ajax/saveloginname.php', {
            username: document.userinfo.username.value,
            email: document.userinfo.email.value,
            password: document.userinfo.password.value
        }).onLoad((data) => {
            if (data.responseText.length !== 0) {
                Toast.error(data.responseText);
                return false;
            }

            Toast.success(messages.userinfoChanged);
            setTimeout(() => {
                YBoard.pageReload();
            }, 2000);
        }).onEnd(() => {
            Theme.stopLoading(e.target);
        });
    }

    static userDeleteAccount(e)
    {
        e.preventDefault();

        let confirmed = document.getElementById('confirmdelete').checked;
        let alsoPosts = document.getElementById('alsoposts').checked;
        let password = document.getElementById('deletionpassword').value;

        if (!confirmed || !confirm(messages.deleteAccount.confirm)) {
            Toast.warning(messages.deleteAccount.canceled);
            return false;
        }

        Theme.startLoading(e.target);

        Ajax.post('/scripts/ajax/deleteprofile.php', {
            confirm: 'on',
            alsoPosts: alsoPosts,
            password: password
        }).onLoad((data) => {
            if (data.responseText.length !== 0) {
                Toast.error(data.responseText);
                return false;
            }

            Toast.success(messages.deleteAccount.success);
            setTimeout(() => {
                YBoard.pageReload();
            }, 2000);
        }).onEnd(() => {
            Theme.stopLoading(e.target);
        });
    }

    static userDeleteData(e)
    {
        e.preventDefault();

        let replies = document.getElementById('delete-replies').checked;
        let threads = document.getElementById('delete-threads').checked;
        let upvotes = document.getElementById('delete-upvotes').checked;
        let password = document.getElementById('deletionpassword').value;

        if (!confirm(messages.deleteData.confirm)) {
            Toast.warning(messages.deleteData.canceled);
            return false;
        }

        Theme.startLoading(e.target);

        Ajax.post('/scripts/ajax/deletedata.php', {
            replies: replies,
            threads: threads,
            upvotes: upvotes,
            password: password
        }).onLoad((data) => {
            if (data.responseText.length !== 0) {
                Toast.error(data.responseText);
                return false;
            }

            Toast.success(messages.deleteData.success);
            setTimeout(() => {
                YBoard.pageReload();
            }, 2000);
        }).onEnd(() => {
            Theme.stopLoading(e.target);
        });
    }
}