import {Ajax} from './Library/Ajax.js';
import {Toast} from './Library/Toast.js';
import {Theme} from './Theme.js';

export class PostFile
{
    static getEvents()
    {
        return {
            expand: {
                event: 'click',
                fn: PostFile.expand,
            },
            playMedia: {
                event: 'click',
                fn: PostFile.playMedia,
            },
            stopMedia: {
                event: 'click',
                fn: PostFile.stopAllMedia,
            }
        };
    }

    delete(id)
    {
        if (!confirm(messages.confirmDeleteFile)) {
            return false;
        }

        Ajax.post('/api/post/deletefile', {
            'post_id': id,
            'loadFunction': function()
            {
                this.getElm(id).find('figure').remove();
                Toast.success(messages.fileDeleted);
            },
        });
    }

    static expand(e)
    {
        e.preventDefault();
        let fileData = e.currentTarget.querySelector('.file-data');
        if (typeof e.currentTarget.dataset.expanded === 'undefined') {
            // Expand
            let largeImg = e.currentTarget.getAttribute('href');
            e.currentTarget.dataset.expanded = fileData.getAttribute('src');
            e.currentTarget.dataset.srcset = fileData.getAttribute('srcset');
            requestAnimationFrame(() => {
                fileData.setAttribute('srcset', '');
                PostFile.changeSrc(fileData, largeImg);
                e.target.closest('.post-file').classList.remove('thumb');
            });
        } else {
            // Restore thumbnail
            let smallImg = e.currentTarget.dataset.expanded;
            let srcset = e.currentTarget.dataset.srcset;
            delete e.currentTarget.dataset.expanded;
            delete e.currentTarget.dataset.srcset;
            requestAnimationFrame(() => {
                let elmTop = e.target.getBoundingClientRect().top + window.scrollY;
                let scrollY = window.scrollY;

                fileData.setAttribute('srcset', srcset);
                PostFile.changeSrc(fileData, smallImg);
                e.target.closest('.post-file').classList.add('thumb');

                // Scroll to top of image
                if (elmTop < scrollY) {
                    window.scrollTo(0, elmTop);
                }
            });
        }
    }

    static playMedia(e)
    {
        e.preventDefault();

        if (typeof e.currentTarget.dataset.loading !== 'undefined') {
            return false;
        }

        PostFile.stopAllMedia();

        let target = e.currentTarget;
        let postFile = target.closest('.post-file');
        let postId = target.closest('.answer, .op_post').dataset.id;

        // Create embed container
        let embedContainer = document.createElement('div');
        embedContainer.classList.add('embedcontainer');

        let fileContainer = document.createElement('div');
        fileContainer.classList.add('file-content');
        requestAnimationFrame(() => {
            fileContainer.prepend(embedContainer);
            postFile.prepend(embedContainer);
        });

        target.dataset.loading = 'true';

        let overlay = document.createElement('div');
        overlay.classList.add('overlay', 'center', 'loading');
        overlay.innerHTML = Theme.loadingHtml();

        requestAnimationFrame(() => {
            embedContainer.appendChild(overlay);
        });

        Ajax.post('/scripts/ajax/embedhtml.php', {id: postId}).onLoad(function(xhr)
        {
            embedContainer.innerHTML = xhr.responseText;
            postFile.classList.remove('thumb');

            // Video volume save/restore
            let video = embedContainer.querySelector('video');
            if (video) {
                video.addEventListener('volumechange', (e) => {
                    localStorage.setItem('videoVolume', e.currentTarget.volume);
                });

                let volume = localStorage.getItem('videoVolume');
                if (volume) {
                    video.volume = volume;
                }
            }

            // Create caption if it does not exist
            let figCaption = postFile.querySelector('figcaption');
            if (!figCaption) {
                figCaption = document.createElement('figcaption');
                figCaption.classList.add('embed-hidelink');
                requestAnimationFrame(() => {
                    postFile.append(figCaption);
                });
            }

            let stopButton = document.createElement('button');
            stopButton.classList.add('embed-hidelink', 'linkbutton');
            stopButton.addEventListener('click', PostFile.stopMedia);
            stopButton.textContent = messages.hideEmbed;
            requestAnimationFrame(() => {
                figCaption.append(stopButton);
            });
        }).onEnd(() => {
            for (let elm of target.querySelectorAll('.loading')) {
                requestAnimationFrame(() => {
                    elm.remove();
                });
            }
            delete target.dataset.loading;
        });
    }

    static stopAllMedia(parent = document)
    {
        for (let elm of parent.querySelectorAll('.post')) {
            PostFile.stopMedia(null, elm);
        }
    }

    static stopMedia(e, postElm = null)
    {
        if (e === null && postElm === null) {
            return;
        }
        let elm;
        if (e !== null) {
            elm = e.currentTarget.closest('.post');
        } else {
            elm = postElm;
        }

        let container = elm.querySelector('.embedcontainer');
        if (!container) {
            // Nothing to stop
            return;
        }

        let video = container.querySelector('video');
        requestAnimationFrame(() => {
            elm.querySelector('.post-file').classList.add('thumb');

            if (video) {
                video.pause();
            }
            container.remove();
            elm.querySelector('.embed-hidelink').remove();
        });
    }

    static changeSrc(img, src)
    {
        for (let loadingOverlay of img.parentNode.querySelectorAll('.loading')) {
            loadingOverlay.remove();
        }

        let eventFn = imgOnload;
        function imgOnload(e)
        {
            this.removeEventListener('load', eventFn);
            this.removeEventListener('error', eventFn);
            window.cancelAnimationFrame(rAF);
            let overlay = img.parentNode.querySelector('.overlay.loading');
            requestAnimationFrame(() => {
                if (overlay !== null) {
                        overlay.remove();
                }
                img.src = this.src;
                this.remove();
            });
        }

        let rAF;
        let overlay = document.createElement('div');
        overlay.classList.add('overlay', 'center', 'loading');
        overlay.innerHTML = Theme.loadingHtml();
        rAF = requestAnimationFrame(() => {
            img.parentNode.appendChild(overlay);
        });

        let tmpImg = new Image();
        tmpImg.addEventListener('load', eventFn);
        tmpImg.addEventListener('error', eventFn);
        tmpImg.setAttribute('src', src);
    }
}
