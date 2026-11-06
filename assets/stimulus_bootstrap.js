import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();

// Lets UX Inspector read the Stimulus runtime (inactive controllers, default values, missing targets…)
if (app.debug) {
    window.Stimulus = app;
}
