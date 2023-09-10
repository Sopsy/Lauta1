import {Toast} from './Module/Library/Toast.js';

export class Config
{
    constructor() {
        this.ajax = {
            options: {
                // AJAX options
                'timeout': 30000,
                'errorFunction': function (xhr) {
                    let errorMessage = xhr.responseText;
                    let errorTitle = messages.error;
                    if (xhr.responseText.length === 0 && xhr.readyState === 4 && xhr.status === 0) {
                        errorMessage = messages.networkError;
                    } else {
                        if (xhr.responseText === 'timeout') {
                            errorMessage = messages.timeoutWarning;
                        } else {
                            try {
                                let text = JSON.parse(xhr.responseText);
                                errorMessage = text.message;
                                if (typeof text.title !== 'undefined' && text.title !== null && text.title.length !== 0) {
                                    errorTitle = text.title;
                                }
                            } catch (e) {
                                errorMessage = xhr.responseText;
                            }
                        }
                    }

                    if (xhr.status === 410) {
                        Toast.warning(errorMessage);
                    } else {
                        if (xhr.status === 418) {
                            Toast.error(errorMessage);
                        } else {
                            Toast.error(errorMessage, errorTitle);
                        }
                    }
                },
                'timeoutFunction': function (xhr) {
                    Toast.error(messages.timeoutWarning);
                }
            },
            headers: {
                'X-CSRF-Token': typeof user.csrfToken !== 'undefined' ? user.csrfToken : null,
            }
        }
    }
}