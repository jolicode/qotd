import { startStimulusApp } from '@symfony/stimulus-bundle';
import Carousel from 'stimulus-carousel';

const app = startStimulusApp();

app.register('carousel', Carousel);

document.addEventListener("turbo:frame-missing", (event) => {
    if (event.target.dataset.deleteIfMissing) {
        event.target.remove();
        event.preventDefault();
    }
});
