import {Config} from './Config.js';
import {YBoard} from './YBoard.js';
import './PrototypeExtension.js';

/* Prevents iOS web apps from opening links in Safari */
(function (document, navigator, standalone) {
    // prevents links from apps from opening in mobile safari
    // this javascript must be the first script in your <head>
    if ((standalone in navigator) && navigator[standalone]) {
        let curnode, location = document.location, stop = /^(a|html)$/i;
        document.addEventListener('click', function (e) {
            curnode = e.target;
            while (!(stop).test(curnode.nodeName)) {
                curnode = curnode.parentNode;
            }
            // Conditions to do this only on links to your own app
            // if you want all links, use if('href' in curnode) instead.
            if ('href' in curnode && // is a link
                (chref = curnode.href).replace(location.href, '').indexOf('#') && // is not an anchor
                (!(/^[a-z\+\.\-]+:/i).test(chref) ||                       // either does not have a proper scheme (relative links)
                    chref.indexOf(location.protocol + '//' + location.host) === 0) // or is in the same protocol and domain
            ) {
                e.preventDefault();
                location.href = curnode.href;
            }
        }, false);
    }
})(document, window.navigator, 'standalone');

window.config = new Config();
YBoard.initElement(document);

window.benchmark = (fn, iterations) => {
    if (typeof iterations === 'undefined') {
        iterations = 100000;
    }
    let iterationCount = iterations;

    let start = new Date;
    while (iterations--) {
        fn();
    }
    let totalTime = new Date - start;
    let msPerOp = totalTime / iterationCount;
    let opsPerSec = (1000 / msPerOp).toFixed(2);

    return totalTime + ' ms, ' + msPerOp.toFixed(2) + ' ms per op, ' + opsPerSec + ' ops/sec';
};

// Functions from old code I didn't bother to update - besides removing jQuery
// Preferences
(() => {
    let switch_preferences_tab = function(id, pushState)
    {
        if (typeof pushState === 'undefined') {
            pushState = false;
        }

        for (let elm of document.querySelectorAll("#right.preferences #tabchooser li")) {
            elm.classList.remove("cur");
        }
        for (let elm of document.querySelectorAll("#right.preferences div.tab")) {
            elm.style.display = 'none';
        }

        if (!document.querySelector("#right.preferences #tabchooser li[data-tabid='" + id + "']")) {
            return;
        }

        document.querySelector("#right.preferences #tabchooser li[data-tabid='" + id + "']").classList.add("cur");
        document.querySelector("#right.preferences div.tab#" + id).style.display = '';

        if (pushState) {
            history.pushState({id: Date.now()}, '', window.location.href.split('?')[0] + '?' + id);
        }
    };

    if (document.getElementById('right') && document.getElementById('right').classList.contains('preferences')) {
        let currentTab = window.location.search.substring(1).split('&')[0];
        for (let elm of document.querySelectorAll('#right.preferences #tabchooser li.tab')) {
            elm.addEventListener('click', (e) => {
                switch_preferences_tab(e.currentTarget.dataset.tabid, true);
            });
        }

        if (!currentTab || !document.getElementById(currentTab)) {
            switch_preferences_tab(document.querySelector('#tabchooser li.tab').dataset.tabid, false);
        } else {
            switch_preferences_tab(currentTab, false);
        }
    }
})();

// Gold account purchase
(() => {
    if (!document.querySelector('#right.purchaseform button.choose')) {
        return false;
    }

    for (let button of document.querySelectorAll('#right.purchaseform button.choose')) {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('quantity-input').style.display = 'none';
            for (let elm of document.querySelectorAll('button.choose')) {
                elm.style.display = '';
            }

            for (let elm of document.querySelectorAll('#right.purchaseform .product')) {
                elm.classList.remove('selected');
            }
            document.getElementById('product_id').value = e.currentTarget.closest('.product').dataset.product_id;
            e.currentTarget.closest('.product').classList.add('selected');

            document.querySelector('#right.purchaseform .product.selected').appendChild(
                document.getElementById('quantity-input'));
            document.getElementById('quantity-input').style.display = 'inline-block';
            e.currentTarget.style.display = 'none';
        });
    }
})();