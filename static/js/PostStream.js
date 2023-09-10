import {Ajax} from './Module/Library/Ajax.js';
import {YBoard} from "./YBoard.js";

export class PostStream
{
    constructor()
    {
        window.lastPostId = "0";
        this.timeout = 1000;
        this.poststream = document.getElementById("poststream");
        this.button = document.getElementById("stream");
        this.trigger = document.getElementById("trigger");
        let that = this;
        this.button.addEventListener('click', () => {
            if(window.scrollX){
                window.scrollTo(0, 0);
            } else {
                that.togglePause();
            }
        });
        that.getNewPosts();
        var options = {
            root: null,
            rootMargin: '0px',
            threshold: 0
        }
        this.observer = new IntersectionObserver(changes => {that.intersectionCallback(changes)}, options)
        this.observer.observe(this.trigger);
    }

    intersectionCallback(changes)
    {
        let that = this;
        changes.forEach(change => {
            if (change.intersectionRatio > 0) {
                that.play();
            } else {
                that.pause();
            }
        });
    }

    togglePause()
    {
        if (this.interval) {
            this.pause();
        } else {
            this.play();
        }
    }

    pause()
    {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = 0;
            this.button.setAttribute('class', 'icon-play-circle');
        }
    }

    play()
    {
        if (!this.interval){
            this.interval = setInterval(() => {this.getNewPosts()}, this.timeout);
            this.button.setAttribute('class', 'icon-pause-circle');
        }
    }

    getNewPosts()
    {
        let that = this;
        Ajax.post('/mod/actions/poststream.php', {id:window.lastPostId}).onLoad((xhr) => {
            let postStream = document.getElementById("poststream");
            let oldHeight = postStream.offsetHeight;
            let response = JSON.parse(xhr.responseText)
            if (response.html) {
                for(let html of response.html.reverse()) {
                    let template = document.createElement("template");
                    template.innerHTML = html;
                    YBoard.initElement(template.content);
                    postStream.prepend(template.content);
                }
            }
            window.lastPostId = response.id;
            let offset = oldHeight - postStream.offsetHeight;
            requestAnimationFrame(()=>{
                postStream.style.transition = '';
                postStream.style.transform = 'translate(0px,' + offset + 'px)';
                requestAnimationFrame(()=>{
                    postStream.style.transition = 'transform 0.2s ease 0s';
                    postStream.style.transform = 'translate(0px, 0px)';
            });});
            let posts = document.getElementsByClassName("thread");
            while (posts.length > 100){
                posts[posts.length - 1].remove();
            }
        }).onEnd((xhr)=> {
            if(!(xhr.status == 200)){
                that.pause();
            }
        });
    }
}

window.poststream = new PostStream();