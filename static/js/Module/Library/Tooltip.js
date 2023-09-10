export class Tooltip
{
    constructor(e, options = {})
    {
        let defaultOptions = {};
        if (typeof window.config === 'object' && typeof window.config.tooltip === 'object') {
            defaultOptions = window.config.tooltip;
        }

        this.options = Object.assign({
            openDelay: 100,
            offset: 0,
            content: '',
            onOpen: null,
            onClose: null,
            closeEvent: 'mouseout',
            position: 'bottom',
            tooltipClass: 'tooltip',
        }, defaultOptions, options);

        // Placeholders for tip position
        this.x = 0;
        this.y = 0;
        this.spaceAvailable = {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
        };

        // Other
        this.overflows = false;
        this.event = e;
        this.id = 0;

        this.open();
    }

    open()
    {
        let that = this;

        this.elm = document.createElement('div');
        if (typeof this.options.tooltipClass !== 'object') {
            let array = [];
            array.push(this.options.tooltipClass);
            this.options.tooltipClass = array;
        }

        for (let className of this.options.tooltipClass) {
            this.elm.classList.add(className);
        }

        let lastTip = document.querySelector('.' + this.options.tooltipClass + ':last-of-type');
        if (lastTip !== null) {
            this.id = parseInt(document.querySelector('.' + this.options.tooltipClass + ':last-of-type').dataset.id) + 1;
        }
        this.elm.dataset.id = this.id;

        this.setContent(this.options.content, false);

        let closeEventFn = function()
        {
            that.event.target.removeEventListener(that.options.closeEvent, closeEventFn);
            that.close(that);
        };

        this.event.target.addEventListener(this.options.closeEvent, closeEventFn);
        this.event.target.tooltip = this;

        if (this.options.openDelay !== 0) {
            setTimeout(function() {
                if (that.elm === null) {

                    return;
                }
                that.appendTip();
            }, this.options.openDelay);
        } else {
            this.appendTip();
        }

        // Global state
        window.tooltips = Object.assign({
            [this.id]: this
        }, typeof window.tooltips === 'object' ? window.tooltips : {});
    }

    appendTip()
    {
        document.body.appendChild(this.elm);

        if (typeof this.options.onOpen === 'function') {
            this.options.onOpen(this);
        }

        this.position();
    }

    setContent(content, position = true)
    {
        if (this.elm === null) {
            return;
        }

        this.elm.innerHTML = '<div class="tooltip-content">' + content + '</div>';

        if (position) {
            this.position();
        }
    }

    getContent()
    {
        if (this.elm === null) {
            return;
        }

        return this.elm.querySelector('.tooltip-content').innerHTML;
    }

    close()
    {
        if (typeof this.options.onClose === 'function') {
            this.options.onClose(this);
        }

        this.elm = null;

        let tip = document.querySelector('.tooltip[data-id="' + this.id + '"]');

        if (tip !== null) {
            tip.remove();
        }

        // Remove from global state
        delete window.tooltips[this.id];
    }

    position()
    {
        if (this.elm === null) {
            return;
        }

        this.targetRect = this.event.target.getBoundingClientRect();
        this.tipRect = this.elm.getBoundingClientRect();

        this.spaceAvailable = {
            'top': this.targetRect.top - this.options.offset,
            'right': window.innerWidth - this.targetRect.right - this.options.offset,
            'bottom': window.innerHeight - this.targetRect.bottom - this.options.offset,
            'left': this.targetRect.left - this.options.offset,
        };

        this.calculatePosition(this.options.position);
        this.setPosition(this.x, this.y);
    }

    calculatePosition(position)
    {
        this.options.position = position;

        // Calculate X
        this.x = this.targetRect.right - this.targetRect.width / 2 - this.tipRect.width / 2;

        if (this.x + this.tipRect.width > window.innerWidth) {
            // If tip is wider than space
            this.x = window.innerWidth - this.tipRect.width;
        }

        if (this.x < 0) {
            this.x = 0;
        }

        let style = window.getComputedStyle(this.elm);
        let margins = parseFloat(style.marginTop) + parseFloat(style.marginBottom);
        // Calculate Y
        if (this.tipRect.height < this.spaceAvailable.bottom || this.spaceAvailable.top < this.spaceAvailable.bottom) {
            // Place on bottom of target
            this.elm.style.maxHeight = this.spaceAvailable.bottom - margins + 'px';
            this.y = this.targetRect.bottom + this.options.offset;
        } else {
            // Place on top of target
            this.elm.style.maxHeight = this.spaceAvailable.top - margins + 'px';
            this.tipRect = this.elm.getBoundingClientRect();
            this.y = this.targetRect.top - this.tipRect.height - margins - this.options.offset;
        }
    }

    setPosition(x, y)
    {
        this.elm.style.left = window.scrollX + x + 'px';
        this.elm.style.top = window.scrollY + y + 'px';
    }

    static get(id)
    {
        return window.tooltips[id];
    }

    static closeAll()
    {
        for(let tip of document.querySelector('.tooltip[data-id]')) {
            this.get(tip.dataset.id).close();
        }
    }
}