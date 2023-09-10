export class Modal
{
    constructor(options = {})
    {
        let defaultOptions = {};
        if (typeof window.config === 'object' && typeof window.config.modal === 'object') {
            defaultOptions = window.config.modal;
        }

        let that = this;
        if (typeof window.modals === 'undefined') {
            window.modals = [];
        }

        let modal = {};
        modal.options = Object.assign({
            'content': null,
            'title': null,
            'onOpen': null,
            'onClose': null,
        }, defaultOptions, options);

        // Used to allow preventing modal close in onClose()
        modal.preventClose = () => {
            modal.closePrevented = true;
        };

        // This is global, create the modal root if it does not exist
        this.createRootElement();

        modal.close = (skipOnClose = false) => {
            if (skipOnClose === true) {
                modal.options.onClose = null;
            }

            modal.closePrevented = false;
            if (typeof modal.options.onClose === 'function') {
                modal.options.onClose(modal);

                if (modal.closePrevented) {
                    return false;
                }
            }

            modal.elm.remove();

            that.removeRootElement();
            if (that.modalRoot.querySelector('.modal') === null) {
                document.removeEventListener('keydown', Modal.keyDownListener);
                that.modalRoot.remove();
                document.body.classList.remove('modal-open');
            }
        };

        modal.setTitle = (title) => {
            modal.options.title = title;
            modal.titleTextElm.textContent = title;
        };

        modal.setContent = (content) => {
            modal.content.innerHTML = content;
            modal.bindEvents();
        };

        modal.bindEvents = () => {
            for (let elm of modal.content.querySelectorAll('[data-e]')) {
                elm.dataset.e.split(' ').forEach((e) => {
                    switch (e) {
                        case 'modalClose':
                            elm.addEventListener('click', modal.close);
                            break;
                    }
                });
            }
        };

        // Create modal element
        modal.elm = document.createElement('div');
        modal.elm.classList.add('modal');
        this.modalRoot.appendChild(modal.elm);

        // Create close button
        modal.closeButton = document.createElement('button');
        modal.closeButton.classList.add('close', 'icon-cross');
        modal.closeButton.addEventListener('click', modal.close);


        // Create title element, if needed
        modal.titleElm = document.createElement('div');
        modal.titleElm.classList.add('title');
        modal.elm.appendChild(modal.titleElm);
        if (modal.options.title !== null) {
            modal.titleTextElm = document.createElement('span');
            modal.titleTextElm.textContent = modal.options.title;
            modal.titleElm.appendChild(modal.titleTextElm);

        }
        modal.titleElm.appendChild(modal.closeButton);

        // Create element for the content
        modal.content = document.createElement('div');
        modal.content.classList.add('content');
        modal.content.innerHTML = modal.options.content;
        modal.bindEvents();
        modal.elm.appendChild(modal.content);

        if (typeof modal.options.onOpen === 'function') {
            modal.options.onOpen(modal);
        }

        window.modals.push(modal);

        return modal;
    }

    createRootElement()
    {
        // This is global, create the modal root if it does not exist
        this.modalRoot = document.getElementById('modal-root');
        if (this.modalRoot === null) {
            this.modalRoot = document.createElement('div');
            this.modalRoot.id = 'modal-root';

            // Bind closing click to container
            this.modalRoot.addEventListener('click', e => {
                if (e.currentTarget === e.target) {
                    Modal.closeLatest();
                }
            });

            // Bind esc to close, needs the tabindex set before to work at all
            document.addEventListener('keydown', Modal.keyDownListener);

            document.body.classList.add('modal-open');
            Modal.fakeScrollbar();
            document.body.appendChild(this.modalRoot);
        }
    }

    removeRootElement()
    {
        if (this.modalRoot.querySelector('.modal') === null) {
            this.modalRoot.remove();
            document.body.classList.remove('modal-open');
            Modal.fakeScrollbar();
        }
    }

    static fakeScrollbar()
    {
        if (document.body.classList.contains('modal-open')) {
            let testElm = document.createElement("div");
            testElm.style.width = '100px';
            testElm.style.height = '100px';
            testElm.style.overflow = 'scroll';
            testElm.style.position = 'absolute';
            testElm.style.top = '-9999px';

            document.body.appendChild(testElm);
            let scrollbarWidth = testElm.offsetWidth - testElm.clientWidth;
            document.body.removeChild(testElm);

            if (scrollbarWidth !== 0) {
                requestAnimationFrame(() => {
                    document.body.style.overflow = 'hidden';
                    document.body.style.marginRight = scrollbarWidth + "px";
                });
            }
        } else {
            requestAnimationFrame(() => {
                document.body.style.overflow = '';
                document.body.style.marginRight = '';
            });
        }
    }

    static keyDownListener(e)
    {
        if (e.keyCode !== 27) {
            return;
        }

        Modal.closeLatest();
    }

    static closeLatest()
    {
        let latest = window.modals.pop();
        if (typeof latest !== 'undefined') {
            latest.close();
        }
    }
}