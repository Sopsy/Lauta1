// Detect old incompatible browsers
let root = document.createElement('div');
root.id = 'modal-root';
let elm = document.createElement('div');
elm.classList.add('modal');

let title = document.createElement('div');
title.classList.add('title');
let titleSpan = document.createElement('span');
titleSpan.innerText = messages.oldBrowserWarningTitle;
let closeButton = document.createElement('button');
closeButton.classList.add('close', 'icon-cross');
closeButton.addEventListener('click', e => {
    document.getElementById('modal-root').remove();
});

let content = document.createElement('div');
content.classList.add('content');
let p1 = document.createElement('p');
p1.innerText = messages.oldBrowserWarning;
p1.style.color = '#ff3333';
p1.style.fontSize = '2em';
p1.style.marginBottom = '1em';
let p2 = document.createElement('p');
p2.innerText = messages.oldBrowserWarningSuggestions;

title.appendChild(titleSpan);
title.appendChild(closeButton);
content.appendChild(p1);
content.appendChild(p2);
elm.appendChild(title);
elm.appendChild(content);
root.appendChild(elm);
document.body.appendChild(root);