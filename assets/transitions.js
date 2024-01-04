import { shouldPerformTransition, performTransition } from "turbo-view-transitions";

let skipNextRenderTransition = false;

document.addEventListener("turbo:before-render", (event) => {
    if (shouldPerformTransition() && !skipNextRenderTransition) {
        event.preventDefault();

        performTransition(document.body, event.detail.newBody, async () => {
            await event.detail.resume();
        });
    }
});

document.addEventListener('turbo:before-frame-render', (event) => {
    if (shouldPerformTransition()) {
        event.preventDefault();

        // workaround for data-turbo-action="advance", which triggers
        // turbo:before-render (and we want THAT to not try to transition)
        skipNextRenderTransition = true;
        setTimeout(() => {
            skipNextRenderTransition = false;
        }, 100);

        performTransition(event.target, event.detail.newFrame, async () => {
            await event.detail.resume();
        });
    }
});

document.addEventListener("turbo:load", () => {
    // View Transitions don't play nicely with Turbo cache
    if (shouldPerformTransition()) {
        Turbo.cache.exemptPageFromCache();
    }
});
