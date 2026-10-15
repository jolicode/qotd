import { startStimulusApp } from '@symfony/stimulus-bundle';
import Carousel from 'stimulus-carousel';

const app = startStimulusApp();
app.register('carousel', Carousel);

// Lets UX Inspector read the Stimulus runtime (inactive controllers, default values, missing targets…)
if (app.debug) {
    window.Stimulus = app;
}
