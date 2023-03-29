import { startStimulusApp } from '@symfony/stimulus-bridge';
import Carousel from 'stimulus-carousel';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.register('carousel', Carousel);

document.addEventListener("turbo:frame-missing", (event) => {
    if (event.target.dataset.deleteIfMissing) {
        event.target.remove();
        event.preventDefault();
    }
});
