// https://dev.to/rohanbagchi/how-to-write-a-vanillajs-router-hk3
const app = document.querySelector("main");
const appContent = app.querySelector('#js-loaded-content');
const spinner = app.querySelector('#spinner');
const menu = document.querySelector('aside');
const menuItems = document.querySelectorAll("aside li a[data-router-navigate]");
const mobileNavTriggerEl = document.querySelector('[data-drawer-target="drawer-navigation"]');
const defaultRoute = 'dashboard';

const renderContent = async (page) => {
    if (!menu.hasAttribute('aria-hidden')) {
        // Trigger click event to close mobile nav.
        mobileNavTriggerEl.dispatchEvent(
            new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            })
        );
    }

    // Show loader.
    spinner.classList.remove('hidden');
    spinner.classList.add('flex');
    appContent.classList.add('hidden');

    // Load content.
    const response = await fetch(page + '.html', {cache: 'no-store'});
    appContent.innerHTML = await response.text();
    window.scrollTo(0, 0);

    // Hide loader.
    spinner.classList.remove('flex');
    spinner.classList.add('hidden');
    appContent.classList.remove('hidden');

    app.setAttribute('data-router-current', page);
    // Manage active classes.
    menuItems.forEach(node => {
        node.setAttribute('aria-selected', 'false')
    });
    document.querySelector('aside li a[data-router-navigate="' + page + '"]').setAttribute('aria-selected', 'true');

    // There might be other nav links on the newly loaded page, make sure they are registered.
    const nav = document.querySelectorAll("nav a[data-router-navigate], main a[data-router-navigate]");
    registerNavItems(nav);

    document.dispatchEvent(new CustomEvent('pageWasLoaded', {
        bubbles: true,
        cancelable: false,
    }));
    document.dispatchEvent(new CustomEvent('pageWasLoaded.' + page, {
        bubbles: true,
        cancelable: false,
    }));
};

const registerNavItems = (items) => {
    items.forEach(function (to) {
        to.addEventListener("click", (e) => {
            e.preventDefault();
            const route = to.getAttribute('data-router-navigate');
            const currentRoute = app.getAttribute('data-router-current');
            if (currentRoute === route) {
                // Do not reload the same page.
                return
            }

            renderContent(route);
            window.history.pushState({
                route: route
            }, "", '#' + route);
        });
    });
};

const registerBrowserBackAndForth = () => {
    window.onpopstate = function (e) {
        if (!e.state) {
            return;
        }
        renderContent(e.state.route);
    };
};


(function boot() {
    const route = location.hash.replace('#', '') || defaultRoute;
    registerNavItems(menuItems);
    registerBrowserBackAndForth();
    renderContent(route);
    window.history.replaceState({
        route: route
    }, "", '#'.route);
})();
