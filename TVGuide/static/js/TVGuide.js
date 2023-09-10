class TVGuide
{
    constructor()
    {
        let that = this;

        document.body.addEventListener('click', (e) => {
            that.clickEvent(e)
        });

        this.dateButton = document.getElementById("date-button");
        this.dateInput = document.getElementById("date-input");

        if (this.dateButton) {
            this.dateInput.addEventListener('change', (e) => {
                TVGuide.onDateChange(e)
            });
        }

        setInterval(() => {
            TVGuide.update()
        }, 15000)
    }

    getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }


    clickEvent(e)
    {
        let menu = document.getElementById("sidebar-container");
        if (e.target.id === "menu-button") {
            menu.toggleAttribute("hidden");
            return;
        }
        if (e.target.classList.contains('channel-container-link') || e.target.parentElement.classList.contains('channel-container-link')){
            menu.setAttribute("hidden", "")
        }
        if (e.target.id === 'sidebar'){return;}
        if (e.target.classList.contains("group")) {
            let groupId = e.target.dataset.id;
            let programLists = document.querySelectorAll('[data-groups]');
            for(let x = 0; x < programLists.length; x++){
                let groups = programLists[x].dataset.groups.split(',');
                if (groups.includes(groupId)||groupId === '0'){
                    programLists[x].removeAttribute('hidden');
                } else {
                    programLists[x].setAttribute("hidden", "")
                }

            }
            return;
        }
        if (e.target.id === 'sidebar-container') {
            menu.setAttribute("hidden", "")
        }
        if (e.target.classList.contains("description")) {
            e.target.classList.toggle("open");
            return;
        }

        if (e.target.classList.contains("title")) {
            this.openModal(e.target.closest(".program").id);

            return;
        }

        if (e.target.classList.contains("close")) {
            document.getElementsByClassName("modal-container")[0].remove();
            return;
        }
        if (e.target.id === "broadcast-list-button") {
            e.target.setAttribute("hidden", '');
            document.getElementById("broadcast-list").removeAttribute("hidden");
            return;
        }
        if (e.target.id === 'lightswitch'){
            this.lightswitch();
        }
    }

    lightswitch(){
        let disabled = document.getElementById('light').toggleAttribute('disabled');
        if (disabled)
        {
            document.cookie = "light=false; expires=Thu, 18 Dec 2099 12:00:00 UTC; path=/";
        } else {
            document.cookie = "light=true; expires=Thu, 18 Dec 2099 12:00:00 UTC; path=/";
        }

    }

    openModal(id)
    {
        let request = new XMLHttpRequest();

        request.open('GET', '/modal/' + id);
        request.onreadystatechange = () => {
            if (request.readyState === 4) {
                let template = document.createElement('template');
                template.innerHTML = request.responseText;
                document.body.appendChild(template.content);
            }
        };
        request.send()
    }

    static update()
    {
        let now = Date.now() * 0.001;
        for (let program of document.getElementsByClassName("time")) {
            let startTime = parseInt(program.dataset.starttime);
            let endTime = parseInt(program.dataset.endtime);

            if (now > endTime) {
                program.classList.remove("running");
                program.classList.add("ended");
            }

            if (now < endTime && now > startTime) {
                program.classList.add("running");
                let progressbar = program.nextElementSibling.getElementsByClassName("progress")[0];
                let progress = ((now - startTime) / (endTime - startTime)) * 100;
                progressbar.style = "width: " + progress + '%;';
            }
        }
    }

    static onDateChange(e)
    {
        if (window.location.href.includes("/radio/")){
            window.location = '/radio/' + e.target.value;
            return;
        }
        window.location = '/' + e.target.value;
    }
}

window.tvguide = new TVGuide();