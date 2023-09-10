import {Ajax} from './Library/Ajax.js';
import {YBoard} from '../YBoard.js';
import {Theme} from './Theme.js';
import {Modal} from "./Library/Modal.js";

export class LoginForm
{
    static getEvents()
    {
        return {
            createAccount: {
                fn: LoginForm.showAccountCreationForm
            },
            cancelCreateAccount: {
                fn: LoginForm.hideAccountCreationForm
            },
            logout: {
                fn: LoginForm.logout
            },
            forgotPassword: {
                fn: LoginForm.forgotPassword
            },
            submitPasswordResetRequest: {
                event: 'submit',
                fn: LoginForm.submitPasswordResetRequest
            }
        }
    }

    static forgotPassword()
    {
        new Modal({
            'title': messages.resetPassword,
            'content': Theme.loadingHtml(),
            'onOpen': function (modal) {
                modal.elm.style.willChange = 'contents';
                Ajax.post('/scripts/modals/getpasswordresetform.php').onLoad((xhr) => {
                    modal.setContent(xhr.responseText);
                    YBoard.initElement(modal.content);
                    modal.elm.style.willChange = '';
                    document.sendrecoverycode.username.value = document.login.username.value;
                });
            },
        });
    }

    static logout()
    {
        if (!confirm(messages.areYouSure)) {
            return;
        }

        Ajax.post('/scripts/ajax/logout.php').onLoad((xhr) => {
            location.reload();
        });
    }

    static showAccountCreationForm()
    {
        document.login.setAttribute("hidden", true);
        document.register.removeAttribute("hidden");
        document.register.username.value = document.login.username.value;
        document.register.password.value = document.login.password.value;

    }

    static hideAccountCreationForm()
    {
        document.register.setAttribute("hidden", true);
        document.login.removeAttribute("hidden");
    }

    static submitPasswordResetRequest(event)
    {
        event.preventDefault();

        if (!YBoard.renderCaptcha(event)) {
            return;
        }

        let form = event.currentTarget;
        let formData = new FormData(form);

        Ajax.post(form.action, formData);
        document.sendrecoverycode.setAttribute("hidden", true);
        document.resetpassword.removeAttribute("hidden");
        document.resetpassword.username.value = document.sendrecoverycode.username.value;
    }
}