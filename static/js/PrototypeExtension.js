Element.prototype.insertAtCaret = function(before, after = '') {
    if (document.selection) {
        // IE
        let selection = document.selection.createRange();
        selection.text = before + selection.text + after;
        this.focus();
    } else if (this.selectionStart || this.selectionStart === 0) {
        // FF & Chrome
        let selectedText = this.value.substr(this.selectionStart, (this.selectionEnd - this.selectionStart));
        let startPos = this.selectionStart;
        let endPos = this.selectionEnd;
        this.value = this.value.substr(0, startPos) + before + selectedText + after + this.value.substr(endPos,
            this.value.length);

        // Move selection to end of "before" -tag
        this.selectionStart = startPos + before.length;
        this.selectionEnd = startPos + before.length;

        this.focus();
    } else {
        // Nothing selected/not supported, append
        this.value += before + after;
        this.focus();
    }
};